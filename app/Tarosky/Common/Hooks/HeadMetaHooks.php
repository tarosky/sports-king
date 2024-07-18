<?php

namespace Tarosky\Common\Hooks;


use Tarosky\Common\Pattern\HookPattern;

/**
 * headのタグでデフォルトのものを外す
 */
class HeadMetaHooks extends HookPattern {

	/**
	 * {@inheritDoc}
	 */
	protected function register_hooks(): void {
		$this->prioritize_canonical();
		$this->avoid_robots_txt_generation();
		// 不要なフックを削除
		foreach ( $this->hooks_to_remove() as list( $callback, $priority ) ) {
			remove_action( 'wp_head', $callback, $priority );
		}
		// デフォルトのサイトマップをなくす
		add_filter( 'wp_sitemaps_enabled', '__return_false' );
		// meta=robotsをカスタマイズ
		add_filter( 'wp_robots', array( $this, 'robots_meta' ), 10, 1 );
		add_action( 'wp_head', array( $this, 'archive_canonical' ), 2 );
	}

	/**
	 * 削除すべきフックを返す
	 *
	 * @return array[] 関数名と優先度からなる配列の配列。
	 */
	protected function hooks_to_remove() {
		return apply_filters( 'sk_hooks_to_remove_in_head', array(
			array( 'feed_links_extra', 3 ),
			array( 'feed_links', 2 ),
			array( 'rsd_link', 10 ),
			array( 'wlwmanifest_link', 10 ),
			array( 'index_rel_link', 10 ),
			array( 'parent_post_rel_link', 10 ),
			array( 'start_post_rel_link', 10 ),
			array( 'adjacent_posts_rel_link_wp_head', 10 ),
			array( 'wp_generator', 10 ),
			array( 'wp_shortlink_wp_head', 10 ),
		) );
	}

	/**
	 * カノニカルリンクを優先度1で登録
	 *
	 * @return void
	 */
	protected function prioritize_canonical() {
		remove_action( 'wp_head', 'rel_canonical' );
		add_action( 'wp_head', 'rel_canonical', 1 );
	}

	/**
	 * WP All Import Export Liteがrobots.txtを勝手に生成するのを防ぐ
	 *
	 * @see https://wordpress.org/support/topic/plugin-is-creating-robots-txt-file/
	 * @return void
	 */
	protected function avoid_robots_txt_generation() {
		if ( class_exists( 'wpie\core\WPIE_General' ) ) {
			remove_action( 'admin_init', array( 'wpie\core\WPIE_General', 'update_file_security' ) );
		}
	}

	/**
	 * meta=robotsをカスタマイズ
	 *
	 * @param array $robots robots.txt
	 * @return array
	 */
	public function robots_meta( $robots ) {
		// attachmentのnoindexをどうするか決定
		$no_index_attachment = apply_filters( 'sk_attachment_noindex', true );
		if ( is_search() || $this->noindex_archive_max() <= (int) get_query_var( 'paged' ) ) {
			$robots['noindex']  = true;
			$robots['nofollow'] = true;
			if ( isset( $robots['follow'] ) ) {
				unset( $robots['follow'] );
			}
		} elseif ( is_404() ) {
			// 404ページはnoindex
			$robots['noindex'] = true;
		} elseif ( is_attachment() && $no_index_attachment ) {
			// 添付ファイルかつnoindex指定ならnoindex
			$robots['noindex'] = true;
		} elseif ( function_exists( 'tps_get_search_engine_page' ) && is_page( tps_get_search_engine_page() ) ) {
			// Google検索プラグインの場合
			// see: https://github.com/tarosky/taro-programmable-search
			$robots['noindex'] = true;
		}
		return $robots;
	}

	/**
	 * これ以上のアーカイブページはインデックスしない
	 *
	 * @return int
	 */
	public function noindex_archive_max() {
		return (int) apply_filters( 'sk_archive_no_index_paged', 6 );
	}

	/**
	 * アーカイブページでのcanonicalリンクを出力
	 *
	 * @return void
	 */
	public function archive_canonical() {
		if ( ! is_category() && ! is_tag() && ! is_tax() ) {
			return;
		}
		$paged = (int) get_query_var( 'paged' );
		if ( $this->noindex_archive_max() <= $paged ) {
			// 6ページ目以降はnoindexなので、canonicalは不要。
			return;
		}
		// canonicalを出すべき重要なURL
		$canonical_taxonomies = apply_filters( 'sk_canonical_taxonomies', array( 'category', 'league' ) );
		if ( ! in_array( get_queried_object()->taxonomy, $canonical_taxonomies, true ) ) {
			return;
		}
		$canonical = get_term_link( get_queried_object() );
		if ( 1 < $paged ) {
			// 2ページ目以降は自己参照
			$canonical = sprintf( '%s/page/%d', untrailingslashit( $canonical ), $paged );
		}
		$canonical = apply_filters( 'sk_archive_canonical_url', $canonical, get_queried_object() );
		if ( ! $canonical ) {
			return;
		}
		printf( '<link rel="canonical" href="%s" />' . "\n", esc_url( $canonical ) );
	}
}
