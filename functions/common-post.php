<?php
/**
 * 投稿に関連する関数
 *
 *
 * @package sports-king
 */

/**
 * メタ情報を取得する
 *
 * @param string $key
 * @param null|int|WP_Post $post
 *
 * @return mixed
 */
function sk_meta( $key, $post = null ) {
	$post = get_post( $post );

	return get_post_meta( $post->ID, $key, true );
}

/**
 * ライターを取得する
 *
 * @param null $post
 *
 * @return null|WP_Post
 */
function sk_get_writer( $post = null ) {
	$post = get_post( $post );
	if ( ! ( $writer_id = get_post_meta( $post->ID, 'writer_id', true ) ) ) {
		return null;
	}

	return get_post( $writer_id );
}

/**
 * 投稿に関係する選手を取得する
 *
 * @param null|int|WP_Post $post
 *
 * @return array
 */
function sk_related_players( $post = null ) {
	$post = get_post( $post );
	$players = \Tarosky\Common\Models\ObjectRelationships::instance()->get_relation( 'player', $post->ID, ['logbook'] );
	return array_filter( $players, function( $player ) {
		return 'publish' == $player->post_status;
	} );
}


/**
 * 投稿に関連するチームを出力する
 *
 * @param null|int|WP_Post $post
 *
 * @return array
 */
function sk_related_teams( $post = null ) {
	$post = get_post( $post );

	return \Tarosky\Common\Models\ObjectRelationships::instance()->get_relation( 'team', $post->ID, ['logbook'] );
}


/**
 * 時間前を出す
 *
 * @param string $format
 * @param null|int|WP_Post $post
 */
function sk_the_time( $format = 'Y.m.d', $post = null ) {
	$post     = get_post( $post );
	if ( is_object( $post ) ) {
		$timezone = new DateTimeZone( 'Asia/Tokyo' );
		$now      = new DateTime( 'now', $timezone );
		$date     = new DateTime( $post->post_date, $timezone );
		$interval = $now->diff( $date );
		if ( $interval->days < 1 ) {
			if ( $interval->h < 1 ) {
				printf( '%d分前', $interval->format( '%i' ) );
			} else {
				printf( '%d時間前', $interval->format( '%h' ) );
			}
		} else {
			echo get_the_time( $format, $post );
		}
	}
}

/**
 * 自由入稿枠を表示する
 *
 * @param int $index
 * @param null|WP_Term|WP_Post $object
 */
function sk_render_free_area( $index, $object = null ) {
	if ( get_option( 'is_display_blocks' ) ) {
		$free_area_target = sprintf( '記事自由入稿%s', $index );
		if( $object ) {
			if ( is_a( $object, 'WP_Term' ) ) {
				$free_area_target = sprintf( 'カテゴリ自由入稿%s', $index );
			} else {
				$free_area_target = sprintf( '優先度順自由入稿%s', $index );
			}
		}

		echo <<<EOM
<div class="ad-freearea-display">
	{$free_area_target}
</div>
EOM;
		return;
	}

	$data = sk_free_area( $index, $object );
	if ( $data ) {
		$file = '';
		foreach ( [ get_template_directory(), get_stylesheet_directory() ] as $dir ) {
			$path = "{$dir}/templates/free-area/{$data['type']}.php";
			if ( file_exists( $path ) ) {
				$file = $path;
			}
		}
		if ( $file ) {
			include $file;
		}
	}
}

/**
 * @see tscfp()
 */
function sk_tscfp( $key, $post ){
	$ret = null;
	if ( function_exists( 'tscfp' ) ) {
		$ret = tscfp( $key, $post );
	}
	return $ret;
}

/**
 * @see tscf_repeat_field()
 */
function sk_tscf_repeat_field( $key, $post ){
	$ret = null;
	if ( function_exists( 'tscf_repeat_field' ) ) {
		$ret = tscf_repeat_field( $key, $post );
	}
	return $ret;
}

/**
 * 投稿に紐付いた関連記事を取得する
 *
 * @param bool $thumbnail サムネイルが必要ならtrue
 * @param null|int|\WP_Post $post
 *
 * @return array
 */
function sk_related_links( $thumbnail = false, $post = null, $is_yahoo_related_image = false ) {

	$post    = get_post( $post );
	$content = explode( '【関連記事】', $post->post_content );
	$links   = [];

	if( !$is_yahoo_related_image ) {
		if ( count( $content ) > 1 ) {
			// 関連記事が本文に書いてある古い形式
			// TODO: いつかなくす
			$old_urls = $content[1];
			if ( preg_match_all( '#<a[^>]*?href="([^"]*)"[^>]*?>([^>]*)</a>#u', $content[1], $matches ) ) {
				foreach ( $matches[0] as $index => $all ) {
					$links[] = [
						'title' => preg_replace( '#^●#', '', $matches[2][ $index ] ),
						'url'   => $matches[1][ $index ],
					];
				}
			}
		} else {
			// 新しい形式
			$links = (array) ( get_post_meta( $post->ID, '_recommend_links', true ) ?: [] );
		}
	}
	else {
		$links = (array) ( get_post_meta( $post->ID, '_recommend_image_links', true ) ?: [] );
	}

	/**
	 * sk_recommend_links
	 *
	 * @param array $links
	 *
	 * @return array
	 */
	$links = apply_filters( 'sk_recommend_links', $links );

	// リンクを生成する
	$links = array_map( function ( $link ) use ( $thumbnail ) {
		$link['thumbnail'] = '';
		$link['type']      = 'other';
		$link['id']        = 0;
		if ( ! ( $post_id = url_to_postid( $link['url'] ) ) ) {
			if ( preg_match( '#^http://opinion\.sk#', $link['url'] ) ) {
				$link['thumbnail'] = get_template_directory_uri() . '/assets/images/feed/opinion.png';
			}

			return $link;
		}
		$link['type'] = get_post_type( $post_id );
		$link['id']   = $post_id;
		if ( $thumbnail ) {
			if ( ! ( $thumbnail = get_post_thumbnail_id( $post_id ) ) ) {
				return $link;
			}
			if ( $image = wp_get_attachment_image_src( $thumbnail, '500n-post-thumbnail' ) ) {
				$link['thumbnail'] = $image[0];
			}
		}

		return $link;
	}, $links );

	return $links;
}

/**
 * 自由入稿枠のデータを取得する
 *
 * @param int $index
 * @param null $object
 *
 * @return array
 */
function sk_free_area( $index, $object = null ) {
	if ( is_null( $object ) ) {
		$object = get_queried_object();
	}
	$data = [];
	if ( is_a( $object, 'WP_Post' ) ) {
		$class_name = sprintf( "\Tarosky\Common\UI\FreeInput\Post%d", $index );
		if( class_exists( $class_name ) ) {
			$data = $class_name::get_raw_data( $object );
		}
		/*
				switch ( $index ) {

					case 1:
						$data = \Tarosky\Common\UI\FreeInput\Post1::get_raw_data( $object );
						break;
					case 2:
						$data = \Tarosky\Common\UI\FreeInput\Post2::get_raw_data( $object );
						break;
					case 3:
						$data = \Tarosky\Common\UI\FreeInput\Post3::get_raw_data( $object );
						break;
					case 4:
						$data = \Tarosky\Common\UI\FreeInput\Post4::get_raw_data( $object );
						break;
					default:
						// Do nothing.
						break;
				}
		*/
	} elseif ( is_a( $object, 'WP_Term' ) ) {
		$data = [ ];
		foreach ( sk_get_term_ancestors( $object, $object->taxonomy, true ) as $term ) {
			$value = get_term_meta( $term->term_id, '_free' . $index, true );
			if ( ! $value ) {
				continue;
			}
			if ( isset( $value['active'] ) ) {
				foreach ( $value[ $value['active'] ] as $k => $v ) {
					if ( $v ) {
						$data = $value;
						break 1;
					}
				}
			}
		}
	}
	if ( $data ) {
		$type         = $data['active'];
		$data         = $data[ $type ];
		$data['type'] = $type;
	}
	if ( isset( $data['id'] ) ) {
		$data['image'] = get_post( $data['id'] );
	}

	return $data;
}

/**
 * きょうだい画像を取得
 *
 * @param null|int|WP_Post $post 指定しなければ現在の投稿
 * @return WP_Post[] 添付ファイルの投稿オブジェクト配列
 */
function sk_get_siblings( $post = null ) {
	$post = get_post( $post );
	if ( ! $post || ! wp_attachment_is_image( $post ) ) {
		return [];
	}
	$parent = get_post_parent( $post );
	if ( ! $parent ) {
		return [];
	}
	$siblings = new WP_Query( [
		'post_type'       => 'attachment',
		'post_parent'     => $parent->ID,
		'post_status'     => 'any',
		'post_mime_type'  => 'image',
		'posts_per_page'  => 200, // 50件以上画像を持つ記事もあるため余裕を持って200件を上限に指定
		'orderby'         => [ 'ID' => 'ASC' ],
		'post__not_in'    => [ get_post_thumbnail_id( $parent->ID ) ], // サムネイルを除外
	] );
	return $siblings->posts;
}

/**
 * attachmentページのタイトルを取得
 *
 * @param null|int|WP_Post $post 指定しなければ現在の投稿
 * @return string|false 【写真・X枚目】親記事タイトル の形式
 */
function sk_get_attachment_page_title( $post = null ) {
	$post = get_post( $post );
	$siblings = sk_get_siblings( $post );
	if ( ! $siblings ) {
		return false;
	}
	$title = get_the_title( get_post_parent( $post ) );
	$ids = array_column( $siblings, 'ID' );
	$num = array_search( $post->ID, $ids );
	if ( $num !== false ) {
		$num++;
		$title = "【写真・" . $num . "枚目】" . $title;
	}
	return $title;
}
