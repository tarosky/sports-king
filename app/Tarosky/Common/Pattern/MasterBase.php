<?php

namespace Tarosky\Common\Pattern;

/**
 * Master Pattern to hold static data
 *
 * @package Tarosky\Common\Pattern
 */
abstract class MasterBase {

	/**
	 * @see static::inject()
	 * @var array[] $masters マスターデータ。各種リーグの名称やIDを保持する
	 */
	protected static $masters = array();

	/**
	 * Do not make instance
	 */
	final private function __construct() {}

	/**
	 * Get value
	 *
	 * @param string $key
	 *
	 * @return mixed|string|array
	 */
	public static function get( $key ) {
		if ( isset( static::$masters[ $key ] ) ) {
			return static::$masters[ $key ];
		} else {
			return null;
		}
	}

	/**
	 * 依存性注入
	 *
	 * @param array $dependencies マスターに設定するデータ
	 * @return void
	 */
	public static function inject( $dependencies ) {
		static::$masters = array_merge( static::$masters, $dependencies );
	}
}
