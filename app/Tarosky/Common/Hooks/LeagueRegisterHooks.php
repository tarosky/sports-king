<?php

namespace Tarosky\Common\Hooks;


use Tarosky\Common\Pattern\HookPattern;

/**
 * タクソノミー「リーグ」を登録する
 */
class LeagueRegisterHooks extends HookPattern {

	/**
	 * @var string リーグのクエリで優先すべきもの
	 */
	protected static $prior_post_type = 'post';

	/**
	 * {@inheritDoc}
	 */
	protected function register_hooks(): void {
		add_action( 'init', [ $this, 'register_league' ] );

		add_action( 'pre_get_posts', [ $this, 'pre_get_posts'] );
	}

	/**
	 * リーグを登録する
	 *
	 * @return void
	 */
	public function register_league() {
		$supported_post_types = [ 'team', 'post' ];
		$args = apply_filters( 'sk_taxonomy_default_args', [
			'label'             => 'リーグ',
			'hierarchical'      => true,
			'show_admin_column' => true,
			'show_in_rest'      => true,
			'rewrite'           => [
				'slug'       => 'league',
				'with_front' => false,
			],
		], 'league' );
		register_taxonomy( 'league', $supported_post_types, $args );
	}

	/**
	 * リーグのクエリで優先すべきものを設定する
	 *
	 * @param string $post_type
	 * @return void
	 */
	public static function set_prior_post_type( $post_type ) {
		if ( in_array( $post_type, [ 'post', 'team' ], true ) ) {
			self::$prior_post_type = $post_type;
		}
	}

	/**
	 * リーグのクエリを変更する
	 *
	 * @param \WP_Query $wp_query
	 * @return void
	 */
	public function pre_get_posts( &$wp_query ) {
		if ( $wp_query->is_main_query() && $wp_query->is_tax( 'league' ) ) {
			// スタッツを除外
			if ( sk_is_stat() ) {
				return;
			}
			switch ( self::$prior_post_type ) {
				case 'team':
					// postが入ってしまっていると、全件出てしまうので絞る。
					$wp_query->set( 'post_type', 'team' );
					// 親カテゴリーがある場合（ヨーロッパ > プレミアリーグ）は全件表示
					$tax = get_queried_object();
					if ( $tax->parent ) {
						$wp_query->set( 'posts_per_page', - 1 );
					}
					// 公開日の古い順で表示
					$wp_query->set( 'orderby', [
						'date' => 'ASC',
					] );
					break;
				case 'post':
				default:
					// teamを除外する
					$wp_query->set( 'post_type', 'post' );
					break;
			}
		}
	}
}
