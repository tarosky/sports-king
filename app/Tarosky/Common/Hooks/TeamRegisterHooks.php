<?php

namespace Tarosky\Common\Hooks;


use Tarosky\Common\Pattern\HookPattern;

/**
 * 投稿タイプ「チーム」を登録するフック
 */
class TeamRegisterHooks extends HookPattern {

	/**
	 * @var string $post_type 投稿タイプ名
	 */
	protected $post_type = 'team';

	/**
	 * {@inheritDoc}
	 */
	protected function register_hooks(): void {
		add_action( 'init', array( $this, 'register_team_post_type' ) );
		add_filter( "manage_{$this->post_type}_posts_columns", array( $this, 'admin_column_header' ) );
		add_action( "manage_{$this->post_type}_posts_custom_column", array( $this, 'admin_column_content' ), 10, 2 );
		add_filter( 'admin_post_thumbnail_html', array( $this, 'post_thumbnail_html' ), 10, 2 );
		add_action( 'post_submitbox_misc_actions', array( $this, 'submit_box_misc_actions' ) );
		add_action( 'pre_get_posts', array( $this, 'pre_get_posts' ) );
	}

	/**
	 * 投稿タイプチームを作成する
	 * @return void
	 */
	public function register_team_post_type() {
		$args = apply_filters( 'sk_post_type_default_args', array(
			'label'           => 'チーム',
			'labels'          => array(
				'name'                  => 'チーム',
				'singular_name'         => 'チーム',
				'add_new'               => 'チームを追加',
				'add_new_item'          => 'チームを追加',
				'edit_item'             => 'チームを編集',
				'new_item'              => '新しいチーム',
				'view_item'             => 'チームを編集',
				'search_items'          => 'チームを探す',
				'not_found'             => 'チームはありません',
				'not_found_in_trash'    => 'ゴミ箱にチームはありません',
				'parent_item_colon'     => '',
				'featured_image'        => 'チームフラッグ',
				'set_featured_image'    => 'チームフラッグを設定',
				'remove_featured_image' => 'チームフラッグを削除',
				'use_featured_image'    => 'チームフラッグとして利用',
			),
			'public'          => true,
			'rewrite'         => array(
				'slug'       => 'team',
				'with_front' => false,
			),
			'capability_type' => 'post',
			'menu_position'   => 10,
			'menu_icon'       => 'dashicons-groups',
			'has_archive'     => true,
			'taxonomies'      => array( 'league' ),
			'supports'        => array( 'title', 'editor', 'author', 'thumbnail' ),
		), $this->post_type );
		register_post_type( 'team', $args );
	}

	/**
	 * カラムのヘッダーを追加
	 *
	 * @param string[] $columns
	 * @return string[]
	 */
	public function admin_column_header( $columns ) {
		$new_columns = array();
		foreach ( $columns as $key => $val ) {
			$new_columns[ $key ] = $val;
			if ( 'taxonomy-league' == $key ) {
				$new_columns['players'] = '登録人数';
			}
		}
		return $new_columns;
	}

	/**
	 * 投稿一覧にコンテンツを追加
	 *
	 * @param string $column  カラム名
	 * @param int    $post_id 投稿ID
	 *
	 * @return void
	 */
	public function admin_column_content( $column, $post_id ) {
		switch ( $column ) {
			case 'players':
				printf(
					'<a href="%s">%s</a>',
					esc_url( admin_url( 'edit.php?post_type=player&team_id=' . $post_id ) ),
					number_format_i18n( \Tarosky\Common\Models\Players::instance()->get_player_count( $post_id ) ) . '人'
				);
				break;
			default:
				// Do nothing.
				break;
		}
	}

	/**
	 * アイキャッチに注意書きを追加
	 *
	 * @param string $content HTML.
	 * @param int    $post_id 投稿ID
	 *
	 * @return string
	 */
	public function post_thumbnail_html( $content, $post_id ) {
		if ( 'team' === get_post_type( $post_id ) ) {
			$content .= sprintf(
				'<p class="description">チームフラッグのサイズは%sにしてください。</p>',
				apply_filters( 'sk_post_thumbnail_description', '幅160px・高さ160px以上の透過の正方形', $this->post_type )
			);
		}
		return $content;
	}

	/**
	 * 並び順について記載
	 *
	 * @param \WP_Post $post 投稿オブジェクト
	 *
	 * @return void
	 */
	public function submit_box_misc_actions( $post ) {
		if ( 'team' === $post->post_type ) {
			?>
			<div class="misc-pub-section">
				<p class="description">
					チームは一覧ページで<strong>公開日時の古い順</strong>に並びます。
				</p>
			</div>
			<?php
		}
	}

	/**
	 * チームの一覧では、並び順を変更する
	 *
	 * @param \WP_Query $wp_query クエリオブジェクト
	 * @return void
	 */
	public function pre_get_posts( \WP_Query &$wp_query ) {
		if ( $wp_query->is_main_query() && $wp_query->is_post_type_archive( 'team' ) ) {
			$wp_query->set( 'orderby', array(
				'date' => 'ASC',
			) );
		}
	}
}
