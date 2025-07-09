<?php

namespace Tarosky\Common\Service\DataStadium;

use Tarosky\Common\Pattern\Singleton;

/**
 * FTP/SFTP接続用のインターフェイス
 *
 * @package Tarosky\Common\Service\DataStadium
 */
abstract class DataStadiumAdapterInterface extends Singleton {
	/**
	 * @var resource 接続情報
	 */
	protected $conn = null;

	/**
	 * @var null|\Exception
	 */
	protected $error = null;

	/**
	 * @var array 接続情報 'host', 'user', 'pass'を持つ
	 */
	protected $credential = array();

	/**
	 * @var string FTPのルートディレクトリ
	 */
	protected $root_dir = '/';

	abstract function connect( $port, $timeout );
	abstract function get_list( $dir );
	abstract function get_root_list( $recursive = true );
	abstract function move( $dir, $absolute = true );
	abstract function download( $local, $remote, $mode = FTP_ASCII );
    abstract function get_modified_time( $file );
	abstract function close();

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
}
