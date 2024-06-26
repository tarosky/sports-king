<?php

namespace Tarosky\Common\Tarosky\Common\Utility;
use Tarosky\Common\Tarosky\Common\Pattern\Singleton;


/**
 * String utility
 *
 * @package WPametu\String
 */
class StringHelper extends Singleton {
	/**
	 * Return hyphenated letter to snake case
	 *
	 * @param string $string String to be snake case.
	 *
	 * @return string
	 */
	public function hungarize( $string ) {
		return str_replace( '-', '_', $string );
	}

	/**
	 * Make hyphenated string to camel case
	 *
	 * @param string $string String to hyphenate.
	 * @param bool   $upper_first Returns Uppercased first letter if true. Defalt false.
	 * @param string $separator Default '_'.
	 *
	 * @return string
	 */
	public function camelize( $string, $upper_first = false, $separator = '_' ) {
		$str = preg_replace_callback( '/'.$separator.'(.)/u', function ( $match ) {
			return strtoupper( $match[1] );
		}, strtolower( $string ) );
		if ( $upper_first ) {
			$str = ucfirst( $str );
		}
		return $str;
	}

	/**
	 * Make camel case to hyphenated string
	 *
	 * @param string $string String to be decamelize.
	 * @param string $separator Default '_'.
	 *
	 * @return string
	 */
	public function decamelize( $string, $separator = '_' ) {
		return strtolower( preg_replace_callback( '/(?<!^)([A-Z]+)/u', function ( $match ) use ($separator) {
			return $separator . strtolower( $match[1] );
		}, (string) $string ) );
	}

	/**
	 * Detect if string is MySQL Date
	 *
	 * @param string $string DATETIME string.
	 *
	 * @return bool
	 */
	public function is_date( $string ) {
		return (bool) preg_match( '/^[0-9]{4}-[0-9]{2}-[0-9]{2}$/u', $string );
	}

	/**
	 * 全角を半角に直す
	 *
	 * @param string $str
	 *
	 * @return string
	 */
	public function to_hankaku( $str ) {
		return mb_convert_kana( mb_convert_kana( $str, 'a', 'UTF-8' ), 's', 'UTF-8' );
	}

	/**
	 * Trim string
	 *
	 * @param string $string String to be trimmed.
	 * @param int    $limit Default 80.
	 * @param string $suffix Default &hellip;.
	 *
	 * @return string
	 */
	public function trim( $string, $limit = 80, $suffix = '&hellip;' ) {
		if ( $limit < mb_strlen( $string, 'utf-8' ) ) {
			$string = mb_substr( $string, 0, $limit - 1 ) . $suffix;
		}

		return $string;
	}
}
