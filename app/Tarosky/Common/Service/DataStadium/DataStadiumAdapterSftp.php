<?php

namespace Tarosky\Common\Service\DataStadium;

use phpseclib3\Net\SFTP;
/**
 * DataStadiumのSFTP接続
 *
 * @package Tarosky\Common\Service\DataStadium
 *
 */
class DataStadiumAdapterSftp extends DataStadiumAdapterInterface {

    /**
     * コンストラクタ。接続情報を受け継ぐ。
     *
     * @param $set_values
     */
	public function __construct( $set_values ) {
		$this->credential = $set_values['credential'];
		$this->root_dir = $set_values['root_dir'];
	}

    /**
     * 接続
     *
     * @param $port
     * @param $timeout
     */
	public function connect( $port, $timeout ) {
		try {
			$this->conn = new SFTP( $this->credential['host'], $port, $timeout );
			if ( ! $this->conn ) {
				throw new \Exception( 'SFTP接続を開始できませんでした。', 500 );
			}
			$logged_in = $this->conn->login( $this->credential['user'], $this->credential['pass'] );
			if ( ! $logged_in ) {
				throw new \Exception( 'SFTPへのログインに失敗しました。', 401 );
			}
			// ルートに移動
			$this->conn->chdir( $this->root_dir );
		} catch ( \Exception $e ) {
			$this->error = $e;
		}
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
		if ( $this->conn->chdir( $this->root_dir ) ) {
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
	 * ファイルリストを取得する
	 *
	 * @param string $dir
	 *
	 * @return array
	 */
	protected function recursive_list( $dir ) {
		$files = array();
		$list  = $this->conn->nlist( $dir );
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
	 * 指定されたディレクトリを取得する
	 *
	 * @param string $dir
	 *
	 * @return array
	 */
	public function get_list( $dir ) {
		$this->move( $dir );
		return $this->conn->nlist( '.' );
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
			$this->conn->chdir( $this->root_dir );
		}

		return $this->conn->chdir( $dir );
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
		$this->conn->chdir( $this->root_dir );
		return $this->conn->get( $remote, $local );
	}

	/**
	 * ファイルの最終更新時刻を取得する
	 *
	 * @param string $file
	 *
	 * @return int Unixタイムスタンプか、失敗なら-1
	 */
	public function get_modified_time( $file ) {
		$this->conn->chdir( $this->root_dir );
		return $this->conn->filemtime( $file );
	}

	/**
	 * 接続を終了する
	 */
	public function close() {
		if ( $this->conn ) {
			$this->conn->disconnect();
		}
	}
}
