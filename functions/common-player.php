<?php
/**
 * 選手関連の関数
 */

/**
 * プレイヤーをDataStadiumのIDで返す
 *
 * @param string $player_id
 *
 * @return null|WP_Post
 */
function sk_get_player_by_id( $player_id ) {
	if ( ! $player_id ) {
		return null;
	}
	foreach ( get_posts( [
		'post_type'      => 'player',
		'posts_per_page' => 1,
		'meta_query'     => [
			[
				'key'   => '_player_id',
				'value' => $player_id,
			],
		],
	] ) as $post ) {
		return $post;
	}

	return null;
}

/**
 * プレイヤーが所属するチームを返す
 *
 * @param null|int|WP_Post $post
 *
 * @return null|WP_Post
 */
function sk_players_team( $post = null ) {
	$post = get_post( $post );
	if ( ! $post->post_parent ) {
		return null;
	}
	return get_post( $post->post_parent );
}

/**
 * プレイヤーに関連するニュースを返す
 *
 * @param int              $limit 件数
 * @param null|int|WP_Post $post 投稿オブジェクト
 *
 * @return array
 */
function sk_get_player_news( $limit = 10, $post = null ) {
	$post = get_post( $post );
	return \Tarosky\Common\Models\ObjectRelationships::instance()->get_siblings( 'player', $post->ID, $limit, 0, ['logbook'] );
}

/**
 * プレイヤーの関連リンクを取得する
 *
 * @param null|int|WP_Post $post
 *
 * @return array
 */
function sk_players_links( $post = null ) {
	$post = get_post( $post );
	$links = [];
	for ( $i = 1; $i <= 3; $i++ ) {
		if ( $url = sk_meta( '_player_link_url_'.$i, $post ) ) {
			$title = sk_meta( '_player_link_title_'.$i, $post );
			if ( ! $title ) {
				// URLからドメインを抽出する
				$title = preg_replace( '#https?://([^/]+).*$#', '$1', $url );
			}
			$links[] = compact( 'title', 'url' );
		}
	}
	return $links;
}


/**
 * プレイヤーの得意なポジションを配列にして返す
 *
 * @param null|int|WP_Post $post
 *
 * @return array
 */
function sk_players_positions( $post = null ) {
	$post      = get_post( $post );
	$best      = sk_meta( '_player_best_position', $post );
	$possible  = array_filter( explode( ',', sk_meta( '_player_better_position', $post ) ), function ( $pos ) {
		return is_numeric( $pos );
	} );
	$positions = sk_position_labels();
	$actuals   = [];
	foreach ( $positions as $index => $position ) {
		$actuals[ $index ] = 0;
		if ( false !== array_search( $index, $possible ) ) {
			$actuals[ $index ] = 1;
		}
		if ( $best && ( $best == $index ) ) {
			$actuals[ $index ] = 2;
		}
	}

	return $actuals;
}


/**
 * ポジションのラベルを取得する
 *
 * @return array
 */
function sk_position_labels() {
	return [
		'1'  => 'GK',
		'2'  => '右SB',
		'3'  => 'CB',
		'4'  => '左SB',
		'5'  => 'DMF',
		'6'  => '右MF',
		'7'  => '左MF',
		'8'  => 'CMF',
		'9'  => '右WG',
		'10' => 'CF',
		'11' => '左WG',
	];
}

/**
 * ポジションの名称を取得する
 *
 * @param string $position
 *
 * @return string
 */
function sk_position_label( $position ) {
	$positions = sk_position_labels();
	if ( $positions[ $position ] ) {
		return $positions[ $position ];
	} else {
		return '-';
	}
}

/**
 * プレイヤーを名前で取得する
 *
 * @param string $name
 *
 * @return bool|WP_Post
 */
function sk_get_player_by_name ( $name ) {
	$name = \Tarosky\Common\Models\Replacements::instance()->normalize( 'player', sk_hankaku($name) );
	foreach ( get_posts([
		'post_type' => 'player',
		'post_status' => 'publish',
		'posts_per_page' => 1,
		's' => $name,
	]) as $post ) {
		return $post;
	}
	return false;
}


/**
 * プレイヤーをタグで取得する
 *
 * @param WP_Term $term Post tag.
 * @return WP_Post|null
 */
function sk_get_player_by_tag( $term ) {
	foreach ( get_posts([
		'post_type'      => 'player',
		'post_status'    => 'publish',
		'posts_per_page' => 1,
		'tax_query'      => [
			[
				'taxonomy' => 'post_tag',
				'terms'    => $term->term_id,
				'field'    => 'term_id',
			],
		],
	]) as $post ) {
		return $post;
	}
	return null;
}

/**
 * ダミーイメージを返す
 *
 * @return string
 */
function sk_get_player_best_member_image_dummy(){
	return get_template_directory_uri() . '/assets/images/dummy/mystery-man.png';
}

/**
 * プレイヤーの画像を返す
 *
 * @param null|int|WP_Post $player プレイヤー投稿
 * @param string           $size   画像サイズ
 *
 * @return string
 */
function sk_get_player_best_member_image_src( $player, $size = 'thumbnail' ){
	$attachment_src = false;
	$image_id       = sk_tscfp( '_player_best_member_image', $player );
	if ( $image_id ) {
		$attachment_src = wp_get_attachment_image_url( $image_id, $size );
	}
	return $attachment_src ?: sk_get_player_best_member_image_dummy();
}

