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
     * @var bool FTP接続かどうか
     */
	protected $is_ftp = false;

	/**
	 * @var array 接続情報 'host', 'user', 'pass'を持つ
	 */
	protected $credential = array();

	/**
	 * @var string FTPのルートディレクトリ
	 */
	protected $root_dir = '/';

    /**
	 * コンストラクタで接続を行う
	 *
	 * @param int $port ポート番号。FTPの場合はデフォルトは21。SFTPの場合はデフォルトは22。
	 * @param int $timeout タイムアウト秒数。デフォルトは15。
	 */
	public function __construct( $port = 0, $timeout = 15 ) {
		if ( get_option( 'sk_use_ftp' ) ) {
			$this->use_ftp();
		}

		//  基本ポートはFTP・SFTPで21/22に分ける
		if ( 0 === $port ) {
			$port = 22;
			if ( $this->is_ftp ) {
				$port = 21;
			}
		}
		$this->adapter->connect( $port, $timeout );
	}

	/**
	 * ftpを使用して接続を行う
	 *
	 * @param $file
	 */
	protected function use_ftp() {
		$this->is_ftp = true;
	}

	/**
	 * 指定されたファイルが拡張子を持っているか否か
	 *
	 * @param string $file
	 *
	 * @return bool
	 */
	protected function has_extension( $file ) {
		return $this->adapter->has_extension( $file );
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
		return $this->adapter->move( $dir, $absolute );
	}

	/**
	 * ファイルリストを取得する
	 *
	 * @param bool $recursive Defautl false
	 *
	 * @return array
	 */
	public function get_root_list( $recursive = true ) {
		return $this->adapter->get_root_list( $recursive );
	}

	/**
	 * 指定されたディレクトリを取得する
	 *
	 * @param string $dir
	 *
	 * @return array
	 */
	public function get_list( $dir ) {
		return $this->adapter->get_list( $dir );
	}


	/**
	 * ファイルリストを取得する
	 *
	 * @param string $dir
	 *
	 * @return array
	 */
	protected function recursive_list( $dir ) {
		return $this->adapter->recursive_list( $dir );
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
		return $this->adapter->download( $local, $remote, $mode );
	}

	/**
	 * ファイルの最終更新時刻を取得する
	 *
	 * @param string $file
	 *
	 * @return int Unixタイムスタンプか、失敗なら-1
	 */
	public function get_modified_time( $file ) {
		return $this->adapter->get_modified_time( $file );
	}


	/**
	 * エラーがあるか確かめる
	 *
	 * @return bool
	 */
	public function has_error() {
		return $this->adapter->has_error();
	}

	/**
	 * エラー文字列を出力する
	 *
	 * @return string
	 */
	public function error_message() {
		return $this->adapter->error_message();
	}

	/**
	 * 接続を終了する
	 */
	public function close() {
		return $this->adapter->close();
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
			case 'adapter':
				$set_values = [
					'credential' => $this->credential,
					'root_dir' => $this->root_dir
				];
				if ( $this->is_ftp ) {
					//  FTP
					$adapter = DataStadiumAdapterFtp::instance( $set_values );
				} else {
					//  SFTP
					$adapter = DataStadiumAdapterSftp::instance( $set_values );
				}
				return $adapter;
				break;
			default:
				return null;
				break;
		}
	}
}
