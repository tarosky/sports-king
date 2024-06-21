<?php
/**
 * ターム、カテゴリー関連
 */


/**
 * 特定のタームの祖先を取得して配列で返す
 *
 * @param int|string|WP_Term $term id, name, term object.
 * @param string $taxonomy Default 'category'.
 * @param bool $include_myself Default true.
 *
 * @return array
 */
function sk_get_term_ancestors( $term, $taxonomy = 'category', $include_myself = true ) {
	$term = get_term( $term, $taxonomy );
	if ( ! $term || is_wp_error( $term ) ) {
		return [ ];
	}
	$terms = [ ];
	if ( $include_myself ) {
		$terms[] = $term;
	}
	while ( $term = sk_get_parent_term( $term ) ) {
		array_unshift( $terms, $term );
	}

	return $terms;
}

/**
 * ルートにあるカテゴリーを返す
 *
 * @param null|int|WP_Post $post
 *
 * @return null|WP_Term
 */
function sk_get_category_root( $post = null ) {
	$post      = get_post( $post );
	if ( ! $post ) {
		return null;
	}
	$terms     = get_the_category( $post->ID );
	if ( ! $terms ) {
		return null;
	}
	$ancestors = null;
	foreach ( $terms as $term ) {
		return sk_traverse_term( $term );
	}

	return null;
}

/**
 * 親タームを返す
 *
 * @param WP_Term $term
 *
 * @return WP_Term
 */
function sk_traverse_term( $term ) {
	if ( ! $term->parent ) {
		return $term;
	} else {
		$parent = get_term( $term->parent, $term->taxonomy );
		if ( is_wp_error( $parent ) || ! $parent ) {
			return $term;
		}

		return sk_traverse_term( $parent );
	}
}

/**
 * Get terms parent.
 *
 * @param WP_Term $term Term object.
 *
 * @return mixed|null
 */
function sk_get_parent_term( $term ) {
	if ( $term->parent ) {
		return get_term( $term->parent, $term->taxonomy );
	} else {
		return null;
	}
}

/**
 * Get post's term ancestors.
 *
 * @param null|int|WP_Post $post Post object.
 * @param string $taxonomy Default 'category'.
 *
 * @return array
 */
function sk_get_term_tree( $post = null, $taxonomy = 'category' ) {
	$post = get_post( $post );
	if ( ! $post ) {
		return [ ];
	}
	$terms = get_the_terms( $post->ID, $taxonomy );
	if ( ! $terms || is_wp_error( $terms ) ) {
		return [ ];
	}
	usort( $terms, 'sk_sort_term' );
	$term = $terms[0];

	$priority_cat = [];
	$best_priority = 0;
	foreach ( $terms as $category ) {
		$priority = get_term_meta( $category->term_id, 'cat_priority', true );
		$priority = $priority ? $priority : '1' ;
		if( $priority > $best_priority ) {
			$priority_cat = [$category];
			$best_priority = $priority;
		}
	}
	$term = $priority_cat[0];
	return sk_get_term_ancestors( $term, $term->taxonomy );
}

/**
 * 投稿に設定されたタクソノミーのメタ情報を取得する
 *
 * @param string $meta_key Meta key name.
 * @param null|int|WP_Post $post Post object.
 * @param string $taxonomy Default 'category'.
 *
 * @return bool|mixed
 */
function sk_get_term_value( $meta_key, $post = null, $taxonomy = 'category' ) {
//	$terms = sk_get_term_tree( $post, $taxonomy );

	$terms = [];
	$post = get_post( $post );
	if( $post && $terms = get_the_terms( $post->ID, 'category' ) ) {
		usort( $terms, 'sk_sort_term' );
	}
	return sk_cascading_term_meta( $terms, $meta_key );
}

/**
 *  特定のタームメタを祖先から上書きする形で取得する
 *
 * @param string|int|WP_Term $term          Term object.
 * @param string             $meta_key      meta key.
 * @param string             $taxonomy      Required if $term is not WP_Term.
 * @param bool              $include_myself Default true.
 *
 * @return bool|mixed
 */
function sk_term_cascading_meta( $term, $meta_key, $taxonomy = '', $include_myself = true ) {
	$ancestors = sk_get_term_ancestors( $term, $taxonomy, $include_myself );

	return sk_cascading_term_meta( $ancestors, $meta_key );
}

/**
 * Get cascading value
 *
 * @ignore
 *
 * @param array $terms An array of WP_Term.
 * @param string $meta_key Meta key name.
 *
 * @return bool|mixed
 */
function sk_cascading_term_meta( $terms, $meta_key ) {
	$fixed = false;
	if ( $terms ) {
		foreach ( $terms as $term ) {
			if ( $value = get_term_meta( $term->term_id, $meta_key, true ) ) {
				$fixed = $value;
			}
		}
	}

	return $fixed;
}

/**
 * カテゴリーのジャンルを返す
 *
 * @return string[]
 */
function sk_get_category_genres() {
	return apply_filters( 'sk_get_category_genres', [
		'japan'    => '国内バスケット',
//		'next'     => '高校・大学＆ジュニア',
		'world'    => '海外バスケット',
		'national' => '日本代表',
		''         => 'その他'
	] );
}

/**
 * タームをID順位並び替える
 * @internal
 * @param WP_Term $term
 * @param WP_Term $another
 * @return int
 */
function sk_sort_term( $term, $another ) {
	if ( $term->term_id > $another->term_id )
		return 1;
	elseif ( $term->term_id < $another->term_id )
		return -1;
	else
		return 0;
}


/**
 * ジャンルに属するカテゴリーを取得する
 *
 * @param string $genre
 *
 * @return array|null|object
 */
function sk_category_groups( $genre ) {
	global $wpdb;
	$query = <<<SQL
		SELECT t.*, tt.* FROM {$wpdb->terms} t
		INNER JOIN {$wpdb->termmeta} AS tm
		ON tm.meta_key = 'cat_genre' AND t.term_id = tm.term_id
		INNER JOIN {$wpdb->term_taxonomy} AS tt
		ON t.term_id = tt.term_id
		WHERE tm.meta_value = %s
		ORDER BY t.term_id ASC
SQL;

	return $wpdb->get_results( $wpdb->prepare( $query, $genre ) );
}



/**
 * カテゴリーを優先度順に1つだけ取得する
 *
 * @param string $type
 * @param null|int|\WP_Post $post
 *
 * @return string|WP_Term
 */
function sk_single_category( $type = 'name', $post = null ) {
	$post       = get_post( $post );
	$categories = get_the_category( $post->ID );

	$priority_cat = [];
	$best_priority = 0;
	foreach ( $categories as $category ) {
		$priority = get_term_meta( $category->term_id, 'cat_priority', true );
		$priority = $priority ? $priority : '1' ;
		if( $priority > $best_priority ) {
			$priority_cat = [$category];
			$best_priority = $priority;
		}
	}

	foreach ( $priority_cat as $category ) {
		return 'name' == $type ? $category->name : $category;
	}

	return '';
}

/**
 * カテゴリーの色を取得する
 *
 * @param string $default Default color.
 * @param null|int|WP_Post $post Post object.
 * @param string $taxonomy Default category.
 *
 * @return string
 */
function sk_category_color( $default = '#fee920', $post = null, $taxonomy = 'category' ) {
	return sk_get_term_value( 'color', $post, $taxonomy ) ?: $default;
}


/**
 * リッチコンテンツがあるならそれを返す
 *
 * @param null|int|WP_Term $term
 *
 * @return mixed|string
 */
function sk_rich_content( $term = null ) {
	$term = $term ? get_term( $term ) : get_queried_object();
	if ( ! is_a( $term, 'WP_Term' ) ) {
		return '';
	}

	return get_term_meta( $term->term_id, 'rich_contents', true );
}



/**
 * カテゴリー画像を取得する
 *
 * @param null|WP_Term $term
 *
 * @return WP_Post|null
 */
function sk_term_image( $term = null ) {
	if ( is_null( $term ) ) {
		$term = get_queried_object();
		if ( ! $term || ! is_a( $term, 'WP_Term' ) ) {
			return null;
		}
	}
	$image = sk_term_cascading_meta( $term, 'cat_image', $term->taxonomy );
	if ( ! $image || ! ( $attachment = get_post( $image ) ) || ( 'attachment' !== $attachment->post_type ) ) {
		return null;
	} else {
		return $attachment;
	}
}

