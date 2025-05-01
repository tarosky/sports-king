<?php
/**
 * チームに関連する関数
 */



/**
 * リーグIDからリーグを取得する
 *
 * @param string $id
 * @param int $abroad
 *
 * @return bool|WP_Term
 */
function sk_get_league_by_id( $id, $abroad = 0 ) {
	$id = $abroad ? 'abroad_' : $id;
	$terms = get_terms( [
		'taxonomy'   => 'league',
		'hide_empty' => false,
		'meta_query' => [
			[
				'key' => 'league_id',
				'value' => $id,
			]
		]
	] );
	if ( ! $terms || is_wp_error( $terms ) ) {
		return false;
	} else {
		return current( $terms );
	}
}

/**
 * 統計ファイルのパスを取得する
 *
 * @param string $file
 * @param string $type 'player' or 'team'
 * @param string $year
 * @return string
 */
function sk_get_total_file_path( $file, $type = 'player', $year = ''  ) {
	if ( ! $year ) {
		$year = date_i18n( 'Y' );
		if ( 9 > date_i18n( 'n' ) ) {
			$year--;
		}
	}
	$dir = wp_upload_dir();
	return "{$dir['basedir']}/data/ds/{$year}/total/{$type}/$file";
}

/**
 * 統計ファイルを取得する
 *
 * @param $file
 * @param string $type
 * @param string $year
 * @return mixed|null|SimpleXMLElement
 */
function sk_get_total_file( $file, $type = 'player', $year = ''  ) {
	static $cache = [];
	$path = sk_get_total_file_path( $file, $type, $year );
	if ( isset( $cache[$path] ) ) {
		return $cache[$path];
	}
	if ( ! file_exists( $path ) ) {
		return null;
	} else {
		$xml = simplexml_load_file( $path );
		$cache[$path] = $xml;
		return $xml;
	}
}


/**
 * Get home and away team
 *
 * @param stdClass $match
 *
 * @return array List of home and away.
 */
function sk_match_teams( $match ) {
	static $cache = [];
	if ( isset( $cache[ $match->game_id ] ) ) {
		return $cache[ $match->game_id ];
	}
	$teams = [ sk_get_team_by_id( $match->h_team_id ), sk_get_team_by_id( $match->a_team_id ) ];
	$cache[ $match->game_id ] = $teams;
	return $teams;
}


/**
 * Get team by ID
 *
 * @param string $id
 *
 * @return null|WP_Post
 */
function sk_get_team_by_id( $id ) {
	$posts = get_posts( [
		'post_type' => 'team',
		'posts_per_page' => 1,
		'meta_query' => [
			[
				'key' => '_team_id',
				'value' => $id,
			],
		],
	] );
	if ( $posts ) {
		return $posts[0];
	} else {
		return null;
	}
}
