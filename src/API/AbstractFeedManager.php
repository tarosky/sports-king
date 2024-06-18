<?php

namespace Tarosky\Common\API;


use Tarosky\Common\Pattern\FeedBase;
use Tarosky\Common\Pattern\Singleton;

/**
 * フィードの配信に責任を持つクラス
 *
 * @package Tarosky\Common\API
 * @property-read string[] $partners
 */
abstract class AbstractFeedManager extends Singleton {


	/**
	 * パートナーのリスト
	 *
	 * @return string[]
	 */
    abstract public function get_partners();

    /**
	 * Constructor
	 *
	 * @param array $settings
	 */
	public function __construct( array $settings = [] ) {
		add_action( 'parse_query', [ $this, 'parse_query' ] );
		add_filter( 'rewrite_rules_array', [ $this, 'rewrite_rules' ] );
		add_filter( 'query_vars', [ $this, 'query_vars' ] );
		add_action( 'pre_get_posts', [ $this, 'pre_get_posts' ] );
	}

	/**
	 * クエリバーを追加
	 *
	 * @param array $vars
	 *
	 * @return array
	 */
	public function query_vars( $vars ) {
		$vars[] = 'delivery_partner';
		$vars[] = 'delivery_type';
		$vars[] = 'json';
		return $vars;
	}

	/**
	 * リライトルールを追加
	 *
	 * @param array $rules
	 *
	 * @return array
	 */
	public function rewrite_rules( $rules ) {
		$new_rules = $this->get_rewrite_rules();
		return array_merge( $new_rules, $rules );
	}

	/**
	 * リライトルールのリストを返す
	 *
	 * @return string[]
	 */
	abstract protected function get_rewrite_rules();

	/**
	 * WP標準フィードを停止
	 *
	 * @author soccerking
	 */
	public function parse_query( $wp_query ) {
		if ( $wp_query->is_feed  ) {
			if ( $wp_query->get( 'delivery_partner' ) ) {
				if ( ! array_key_exists( $wp_query->get( 'delivery_partner' ), $this->partners ) ) {
					// 上記の条件に当てはまらないにも関わらずフィードなら
					// 強制停止
					wp_die(sprintf('このRSSフィードは配信停止中です。<a href="%s">%sへ戻る</a>', home_url(), get_bloginfo('name')), get_bloginfo('name'), [
						'response' => 404,
					]);
				}
			} else {
				// フィードの種類が許可されているか
				// see: https://github.com/tarosky/taro-exclusive-sitemap
				$allowed_feeds = apply_filters( 'sk_allowed_feeds', [ 'rss', 'rss2', 'atom', 'exclusive-sitemap' ] );
				foreach ( $allowed_feeds as $feed ) {
					if ( is_feed( $feed ) ) {
						return;
					}
				}
				// 上記の条件に当てはまらないにも関わらずフィードなら強制停止
				wp_die(sprintf('このRSSフィードは配信停止中です。<a href="%s">%sへ戻る</a>', home_url(), get_bloginfo('name')), get_bloginfo('name'), [
					'response' => 404,
				]);
			}
		}
	}

	/**
	 * クエリの上書き
	 *
	 * @param \WP_Query $wp_query
	 */
	public function pre_get_posts( &$wp_query ) {
		if ( $wp_query->is_main_query() && $wp_query->is_feed() && ( $partner = $wp_query->get( 'delivery_partner' ) ) ) {
			if ( array_key_exists( $partner, $this->partners ) ) {
				$instance = $this->get_partner_class( $partner );
				if ( $instance ) {
					$instance->pre_get_posts( $wp_query );
				}
			} else {
				$wp_query->set_404();
			}
		}
	}

	/**
	 * パートナークラスを取得
	 *
	 * @param string $partner パートナー名
	 * @return FeedBase|null
	 */
	abstract public function get_partner_class( $partner );

	/**
	 * ゲッター
	 *
	 * @param string $name
	 * @return mixed
	 */
	public function __get( $name ) {
		switch ( $name ) {
			case 'partners':
				return $this->get_partners();
			default:
				return null;
		}
	}
}
