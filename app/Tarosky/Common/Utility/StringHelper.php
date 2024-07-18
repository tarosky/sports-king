<?php

namespace Tarosky\Common\Utility;
use Tarosky\Common\Pattern\Singleton;


/**
 * String utility
 *
 * @package WPametu\String
 */
class StringHelper extends Singleton {
	/**
	 * Return hyphenated letter to snake case
	 *
	 * @param string $text String to be snake case.
	 *
	 * @return string
	 */
	public function hungarize( $text ) {
		return str_replace( '-', '_', $text );
	}

	/**
	 * Make hyphenated string to camel case
	 *
	 * @param string $text String to hyphenate.
	 * @param bool   $upper_first Returns Uppercased first letter if true. Defalt false.
	 * @param string $separator Default '_'.
	 *
	 * @return string
	 */
	public function camelize( $text, $upper_first = false, $separator = '_' ) {
		$str = preg_replace_callback( '/' . $separator . '(.)/u', function ( $matches ) {
			return strtoupper( $matches[1] );
		}, strtolower( $text ) );
		if ( $upper_first ) {
			$str = ucfirst( $str );
		}
		return $str;
	}

	/**
	 * Make camel case to hyphenated string
	 *
	 * @param string $text String to be decamelize.
	 * @param string $separator Default '_'.
	 *
	 * @return string
	 */
	public function decamelize( $text, $separator = '_' ) {
		return strtolower( preg_replace_callback( '/(?<!^)([A-Z]+)/u', function ( $matches ) use ( $separator ) {
			return $separator . strtolower( $matches[1] );
		}, (string) $text ) );
	}

	/**
	 * Detect if string is MySQL Date
	 *
	 * @param string $text DATETIME string.
	 *
	 * @return bool
	 */
	public function is_date( $text ) {
		return (bool) preg_match( '/^[0-9]{4}-[0-9]{2}-[0-9]{2}$/u', $text );
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
	 * @param string $text String to be trimmed.
	 * @param int    $limit Default 80.
	 * @param string $suffix Default &hellip;.
	 *
	 * @return string
	 */
	public function trim( $text, $limit = 80, $suffix = '&hellip;' ) {
		if ( $limit < mb_strlen( $text, 'utf-8' ) ) {
			$text = mb_substr( $text, 0, $limit - 1 ) . $suffix;
		}

		return $text;
	}
}
