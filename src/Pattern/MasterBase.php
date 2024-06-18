<?php

namespace Tarosky\Common\Pattern;

/**
 * Master Pattern to hold static data
 *
 * @package Tarosky\Common\Pattern
 */
abstract class MasterBase {

	protected static $masters = [];

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
		if ( isset( static::$masters[$key] ) ) {
			return static::$masters[$key];
		} else {
			return null;
		}
	}
}
