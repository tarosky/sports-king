<?php
/**
 * テンプレート周りの関数
 *
 * @package sports-king
 */


/**
 * get_template_partとほぼおなじだが、第三引数のargsを展開できる
 *
 * @param string $name
 * @param string $path
 * @param array  $args
 */
function sk_get_template( $name, $path = '', $args = [] ) {
	global $posts, $post, $wp_did_header, $wp_query, $wp_rewrite, $wpdb, $wp_version, $wp, $id, $comment, $user_ID;
	$files = [ $name ];
	if ( $path ) {
		$files[] = $name.'-'.$path;
	}
	$paths = [];
	foreach ( $files as $file ) {
		foreach ( [ get_template_directory(), get_stylesheet_directory() ] as $dir ) {
			$paths[] = "{$dir}/{$file}.php";
		}
	}
	for ( $i = count( $paths ); $i > 0; $i-- ) {
		if ( file_exists( $paths[ $i - 1 ] ) ) {
			if ( $args ) {
				extract( $args );
			}
			include $paths[ $i - 1 ];
			break;
		}
	}
}

/**
 * 全角英数字を半角英数字にする
 *
 * @param $string
 *
 * @return string
 */
function sk_hankaku( $string ) {
	return str_replace('－', '-', mb_convert_kana( (string) $string, 'rn', 'utf-8' ) );
}

/**
 * 文字数を切り詰めて返す
 *
 * @param string $string
 * @param int $limit
 * @param string $suffix
 *
 * @return string
 */
function sk_trim( $string, $limit, $suffix = '&hellip;' ) {
	if ( mb_strlen( $string, 'utf-8' ) >= $limit ) {
		return mb_substr( $string, 0, $limit - 1, 'utf-8' ).$suffix;
	} else {
		return $string;
	}
}

/**
 * 古い形式の日付を直す
 *
 * @param string $string
 * @param string $format
 *
 * @return mixed
 */
function sk_beautify_date( $string, $format = '$1年$2月$3日 $4:$5') {
	return preg_replace( '#(\\d{4})(\\d{2})(\\d{2})(\\d{2})(\\d{2})\\d{2}#', $format , $string );
}

/**
 * パーセントの値を取得する
 *
 * @param int|float $value
 * @param int|float $total
 * @param string $suffix
 * @return string
 */
function sk_percentile( $value, $total, $suffix = '%' ) {
	if ( 0 == $total ) {
		return '0%';
	}
	return number_format_i18n( ( $value / $total ) * 100 ) . '%';
}

