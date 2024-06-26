<?php

namespace Tarosky\Common\Pattern;

/**
 * フィードクラスの元になる抽象クラス
 * @package Tarosky\Common\Pattern
 */
abstract class FeedBase extends Singleton {

	protected $per_page = 20;

	/**
	 * Parse query
	 *
	 * @param \WP_Query $wp_query
	 *
	 * @return void
	 */
	abstract public function pre_get_posts( \WP_Query &$wp_query );

	/**
	 * {@inheritDoc}
	 */
	protected function __construct( $settings = [] ) {
		$this->on_construct();
	}

	/**
	 * Do something here.
	 *
	 * @return void
	 */
	protected function on_construct() {
		// Do something here.
	}

	/**
	 * Get writer's name
	 *
	 * @param null|int|\WP_Post $post
	 *
	 * @return array|null|string|void|\WP_Post
	 */
	protected function get_writer( $post = null ) {
		$post      = get_post( $post );
		$writer_id = get_post_meta( $post->ID, 'writer_id', true );
		if ( $writer_id && ( $writer = get_post( $writer_id ) ) && ( 'writer' == $writer->post_type ) ) {
			return $writer->post_title;
		} else {
			// TODO: 古いデータなので、いつかなくす
			$old_name = strip_tags( get_post_meta( $post->ID, 'file8', true ) );
			if ( $old_name ) {
				return $old_name;
			}
		}

		return get_bloginfo( 'name' );
	}

	/**
	 * 関連記事を削除したコンテンツを返す
	 *
	 * @param null $post
	 *
	 * @return string
	 */
	protected function get_raw_content( $post = null, $is_strip_sc = false, $is_filter_content_through = false, $is_blockquote_through = true ) {
		$post = get_post( $post );

		$content = $post->post_content;
		if ( $is_strip_sc ) {
            $content = strip_shortcodes( $content );
        }
		$content = apply_filters( 'the_content', trim( explode('【関連記事】', $content )[0] ), $is_filter_content_through );

		// twitterやinstagramのブロック引用を消す
        if ( $is_blockquote_through === true ) {
            $content = preg_replace_callback( '#<blockquote([^>]*?)>(.*?)</blockquote>#us', function($matches) {
                if ( false !== strpos( $matches[1], 'twitter' ) ) {
                    return '';
                } elseif ( false !== strpos( $matches[1], 'instagram-media' ) ) {
                    return '';
                } else {
                    return $matches[0];
                }
            }, $content );
        }

		return $content;
	}

	/**
	 * ショートコードが削除されたフィードを返す
	 *
	 * @param null $post
	 *
	 * @return string
	 */
	protected function striped_content( $post = null ) {
		$content = $this->get_raw_content( $post, true );
		// captionを消す
		$content = preg_replace( '#\[caption[^\]]*?](.*?)\[/caption\]#u', '', $content );
		// OembedになりそうなURLだけの行を消す
		$content = implode( "\n", array_filter( explode( "\r\n", $content ), function( $row ) {
			return ! preg_match( '#^https?://[a-zA-Z0-9\.\-\?=_/]+$#', $row );
		} ) );
		// 3行空白が続いたら圧縮
		$content = preg_replace( '/\\n{3,}/', "\n\n", $content );
		return $content;
	}

	/**
	 * 関連記事を取得する
	 *
	 * @param null|int|\WP_Post $post
	 *
	 * @return array
	 */
	protected function grab_related_links( $post = null ) {
		return sk_related_links( true, $post );
	}

	/**
	 * Add where
	 *
	 */
	protected function avoid_ad() {
		add_filter( 'posts_join', [ $this, 'posts_join' ], 10, 2 );
		add_filter( 'posts_where', [ $this, 'posts_where' ], 10, 2 );
	}

	/**
	 * 広告記事を除外
	 *
	 * @param string $join
	 * @param \WP_Query $wp_query
	 *
	 * @return string
	 */
	public function posts_join( $join, $wp_query ) {
		global $wpdb;
		$join .= <<<SQL
		LEFT JOIN {$wpdb->postmeta} AS isAd
		ON ({$wpdb->posts}.ID = isAd.post_id) AND (isAd.meta_key = '_is_ad')
SQL;
//		LEFT JOIN {$wpdb->postmeta} AS nofeed
//		ON ({$wpdb->posts}.ID = nofeed.post_id) AND (nofeed.meta_key = 'nofeed')
		remove_filter( 'posts_join', [ $this, 'posts_join' ], 10 );
		return $join;
	}

	/**
	 * 条件を追加
	 *
	 * @param string $where
	 * @param \WP_Query $wp_query
	 *
	 * @return string
	 */
	public function posts_where( $where, $wp_query ) {
		global $wpdb;
		$where .= <<<SQL
			AND ( isAd.meta_value != '1' OR isAd.meta_value IS NULL )
SQL;
		remove_filter( 'posts_where', [ $this, 'posts_where' ], 10 );
		return $where;
	}

	/**
	 * Escape string
	 *
	 * @param string $string
	 * @return string
	 */
	protected function escape( $string ) {
		return htmlspecialchars( $string, ENT_XML1, 'utf-8' );
	}

	/**
	 * GMTの日付をローカルタイムに直す
	 *
	 * @param string $string
	 * @param string $format
	 *
	 * @return string
	 */
	protected function to_local_time( $string, $format ) {
		$date = new \DateTime( $string, new \DateTimeZone( 'Asia/Tokyo' ) );
		return $date->format( $format );
	}

	/**
	 * XMLヘッダーを吐く
	 *
	 * @param int $hours デフォルトは1時間
	 */
	protected function xml_header( $hours = 1 ) {
		if ( $hours ) {
			$this->expires_header( $hours );
		}
		header( 'Content-Type: text/xml; charset=utf-8', true );
		echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
	}

	/**
	 * Expiresヘッダーを吐く
	 *
	 * @param int $hours
	 */
	protected function expires_header( $hours = 1 ) {
		$time = current_time( 'timestamp', true ) + 60 * 60 * $hours;
		header( 'Expires: ' . gmdate( 'D, d M Y H:i:s', $time ) . ' GMT' );
	}
}
