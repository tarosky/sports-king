<?php

namespace Tarosky\Common\Service\DataStadium;


use Tarosky\Common\Models\Matches;
use Tarosky\Common\Models\Replacements;
use Tarosky\Common\Models\TeamMaster;

/**
 * DataStadiumの接続インターフェース
 *
 * @package Tarosky\Common\Service\DataStadium
 *
 * @property-read TeamMaster $team_master
 * @property-read Matches $matches
 * @property-read Replacements $replacer
 */
abstract class DataStadiumBase {

	/**
	 * @var resource FTP接続
	 */
	private $conn = null;

	/**
	 * @var null|\Exception
	 */
	private $error = null;

	/**
	 * @var array 接続情報 'host', 'user', 'pass'を持つ
	 */
	protected $credential = array();

	/**
	 * @var string FTPのルートディレクトリ
	 */
	protected $root_dir = '/';

	/**
	 * コンストラクタでFTP接続を行う
	 *
	 * @param int $port ポート番号。デフォルトは21。
	 * @param int $timeout タイムアウト秒数。デフォルトは15。
	 */
	public function __construct( $port = 21, $timeout = 15 ) {
		try {
			$this->conn = ftp_connect( $this->credential['host'], $port, $timeout );
			if ( ! $this->conn ) {
				throw new \Exception( 'FTP接続を開始できませんでした。', 500 );
			}
			$logged_in = ftp_login( $this->conn, $this->credential['user'], $this->credential['pass'] );
			if ( ! $logged_in ) {
				throw new \Exception( 'FTPへのログインに失敗しました。', 401 );
			}
			// PASVモードにする
			$this->set_pasv( true );
			// ルートに移動
			ftp_chdir( $this->conn, $this->root_dir );
		} catch ( \Exception $e ) {
			$this->error = $e;
		}
	}

	/**
	 * 指定されたファイルが拡張子を持っているか否か
	 *
	 * @param string $file
	 *
	 * @return bool
	 */
	protected function has_extension( $file ) {
		return (bool) preg_match( '#\.[0-9a-zA-Z]+$#', $file );
	}

	/**
	 * 指定したディレクトリに移動する
	 *
	 * @param string $dir
	 * @param bool $absolute 初期値true。falseの場合は現在のディレクトリを保つ。
	 *
	 * @return bool
	 */
	public function move( $dir, $absolute = true ) {
		if ( $absolute ) {
			ftp_chdir( $this->conn, $this->root_dir );
		}

		return ftp_chdir( $this->conn, $dir );
	}

	/**
	 * ファイルリストを取得する
	 *
	 * @param bool $recursive Defautl false
	 *
	 * @return array
	 */
	public function get_root_list( $recursive = true ) {
		// ルートに移動
		if ( ftp_chdir( $this->conn, $this->root_dir ) ) {
			// ファイルリストを取得
			if ( $recursive ) {
				return $this->recursive_list( '.' );
			} else {
				return $this->get_list( '.' );
			}
		} else {
			return new \WP_Error( 500, 'ディレクトリに移動できませんでした。' );
		}
	}

	/**
	 * 指定されたディレクトリを取得する
	 *
	 * @param string $dir
	 *
	 * @return array
	 */
	public function get_list( $dir ) {
		$this->move( $dir );
		return ftp_nlist( $this->conn, '.' );
	}


	/**
	 * ファイルリストを取得する
	 *
	 * @param string $dir
	 *
	 * @return array
	 */
	protected function recursive_list( $dir ) {
		$files = array();
		$list  = ftp_nlist( $this->conn, $dir );
		if ( $list ) {
			foreach ( $list as $file ) {
				if ( $this->has_extension( $file ) ) {
					$files[] = $file;
				} else {
					$child_list = $this->recursive_list( $dir . '/' . $file );
					if ( $child_list ) {
						$files[] = $child_list;
					} else {
						$files[] = $file;
					}
				}
			}
		}

		return $files;
	}

	/**
	 * XMLを取得して返す
	 *
	 * @param string $file
	 * @param int    $ignore
	 *
	 * @return \SimpleXMLElement|\WP_Error
	 */
	public function get_xml( $file, $ignore = 0 ) {
		$xml_str = $this->get_contents( $file, $ignore );
		if ( is_wp_error( $xml_str ) ) {
			return $xml_str;
		}
		$xml_str = str_replace( 'encoding="Shift_JIS"', ' encoding="UTF-8"', mb_convert_encoding( $xml_str, 'utf-8', 'sjis-win' ) );
		if ( $xml = simplexml_load_string( $xml_str ) ) {
			$path = untrailingslashit( $this->get_path_base() ) . '/' . ltrim( $file, '/' );
			$dir  = dirname( $path );
			if ( ! is_dir( $dir ) ) {
				mkdir( $dir, 0755, true );
			}
			file_put_contents( $path, $xml_str );
			return $xml;
		} else {
			return new \WP_Error( 500, 'XMLの変換に失敗しました' );
		}
	}

	/**
	 * 保存先のパスを返す
	 *
	 * @param bool|int $year
	 *
	 * @return string
	 */
	protected function get_path_base( $year = false ) {
		if ( ! $year ) {
			$year = date_i18n( 'Y' );
		}
		return ABSPATH . "NK/data/{$year}/";
	}

	/**
	 * ファイルを取得する
	 *
	 * @param string $file
	 * @param int    $ignore
	 * @param int $mode
	 *
	 * @return string|\WP_Error
	 */
	public function get_contents( $file, $ignore = 0, $mode = FTP_ASCII ) {
		if ( $ignore ) {
			$remote = $this->get_modified_time( $file );
			if ( $remote > 0 && $remote + $ignore < current_time( 'timestamp', true ) ) {
				return new \WP_Error( 304, 'ファイルは更新されていません。' );
			}
		}
		$local = tempnam( sys_get_temp_dir(), 'bk-tmp' );
		if ( ! $this->download( $local, ltrim( $file, '/' ), $mode ) ) {
			return new \WP_Error( 500, 'ダウンロード出来ませんでした' );
		}
		$content = file_get_contents( $local );
		// 消す
		unlink( $local );
		return $content;
	}

	/**
	 * ファイルを指定したパスにダウンロードする
	 *
	 * @param string g$local
	 * @param string $remote
	 * @param int $mode
	 *
	 * @return bool
	 */
	public function download( $local, $remote, $mode = FTP_ASCII ) {
		ftp_chdir( $this->conn, $this->root_dir );
		return ftp_get( $this->conn, $local, $remote, $mode );
	}

	/**
	 * ファイルの最終更新時刻を取得する
	 *
	 * @param string $file
	 *
	 * @return int Unixタイムスタンプか、失敗なら-1
	 */
	public function get_modified_time( $file ) {
		ftp_chdir( $this->conn, $this->root_dir );
		return ftp_mdtm( $this->conn, $file );
	}

	/**
	 * PASVモードの設定を行う
	 *
	 * @param bool $mode
	 *
	 * @return bool
	 */
	public function set_pasv( $mode = true ) {
		return ftp_pasv( $this->conn, $mode );
	}

	/**
	 * エラーがあるか確かめる
	 *
	 * @return bool
	 */
	public function has_error() {
		return ! is_null( $this->error );
	}

	/**
	 * エラー文字列を出力する
	 *
	 * @return string
	 */
	public function error_message() {
		return $this->has_error() ? $this->error->getMessage() : '';
	}

	/**
	 * 接続を終了する
	 */
	public function close() {
		if ( $this->conn ) {
			ftp_close( $this->conn );
		}
	}

	/**
	 * ゲッター
	 *
	 * @param string $name
	 *
	 * @return null|static
	 */
	function __get( $name ) {
		switch ( $name ) {
			case 'team_master':
				return TeamMaster::instance();
				break;
			case 'matches':
				return Matches::instance();
				break;
			case 'replacer':
				return Replacements::instance();
				break;
			default:
				return null;
				break;
		}
	}
}
