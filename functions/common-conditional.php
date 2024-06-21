<?php
/**
 * 広告関連の関数
 */


/**
 * 広告記事かどうか
 *
 * @param null|int|WP_Post $post
 *
 * @return bool
 */
function sk_is_ad( $post = null ) {
	$post = get_post( $post );
	return (bool) sk_meta( '_is_ad', $post );
}

/**
 * 日程・結果ページか否か
 *
 * @param string $type
 *
 * @return bool
 */
function sk_is_stat( $type = '' ) {
	if ( $type ) {
		return $type === get_query_var( 'stats' );
	} else {
		return (bool) get_query_var( 'stats' );
	}
}

/**
 * 日程・結果ページの詳細か否か
 *
 * @return bool
 */
function sk_is_match() {
	return sk_is_stat( 'match' );
}

/**
 * チームが海外のチームか否か
 *
 * @param null|int|WP_Post $post
 *
 * @return bool|int
 */
function sk_is_abroad( $post = null ) {
	$post = get_post( $post );
	if ( 'team' != $post->post_type ) {
		return 0;
	}
	$leagues = get_the_terms( $post, 'league' );
	if ( ! $leagues || is_wp_error( $leagues ) ) {
		return 0;
	}
	$league    = current( $leagues );
	$league_id = get_term_meta( $league->term_id, 'league_id', true );
	if ( ! $league_id || ! preg_match( '#(abroad_)?([0-9]{1,2})#', $league_id, $matches ) ) {
		return 0;
	}
	$league_id = end( $matches );

	return count( $matches ) > 1;
}

/**
 * タームがツリー上に収まっているか確認する
 *
 * @param WP_Term $term
 * @param null $post
 *
 * @return bool
 */
function sk_term_in( $term, $post = null ) {
	if( !empty( $term->taxonomy ) ) {
		$tree = sk_get_term_tree( $post, $term->taxonomy );
		foreach ( $tree as $node ) {
			if ( $node->term_id == $term->term_id ) {
				return true;
			}
		}
	}
	return false;
}
