<?php

namespace Tarosky\Common\UI;


use Tarosky\Common\Pattern\Singleton;

/**
 * 広告ポジションを司るクラス
 *
 * 利用するときは、sk_ad_positionsフィルターをテーマ側で適用し、
 * 'slug' => [ 'label' => 'ラベル', 'description' => '説明文（表示される場所など）' ] の連想配列を返すこと。
 *
 * @package Tarosky\Common\UI
 * @property-read array $positions 広告ポジション
 */
class AdPositionManager extends Singleton {

	/**
	 * @var bool 現在広告は抑制中か否か
	 */
	protected $suppressed = false;

	/**
	 * @var string 投稿タイプ
	 */
	public $post_type = 'ad-content';

	/**
	 * @var string タクソノミー
	 */
	public $taxonomy = 'ad-position';

	/**
	 * コンストラクタ
	 *
	 * @param array $settings
	 */
	protected function __construct( array $settings ) {
		add_action( 'init', [ $this, 'register_ad_post_type' ] );
		add_action( 'template_redirect', [ $this, 'template_redirect' ] );
		add_filter( "manage_{$this->post_type}_posts_columns", [ $this, 'admin_columns' ] );
		add_action( "manage_{$this->post_type}_posts_custom_column", [ $this, 'do_admin_column' ], 10, 2 );
		add_action( 'admin_notices', [ $this, 'admin_notice' ] );
		add_filter( "manage_edit-{$this->taxonomy}_columns", [ $this, 'tax_columns' ] );
		add_filter( "manage_{$this->taxonomy}_custom_column", [ $this, 'do_tax_columns' ], 10, 3 );
		add_action( $this->taxonomy . '_edit_form_fields', [ $this, 'form_fields' ] );
	}

	/**
	 * 投稿タイプを登録
	 *
	 * @return void
	 */
	public function register_ad_post_type() {
		// Register post type
		$post_type_args = apply_filters( 'sk_ad_post_type_args', [
			'labels'          => [ 'name' => '広告ブロック' ],
			'public'          => false,
			'show_ui'         => true,
			'capability_type' => 'post',
			'hierarchical'    => false,
			'menu_position'   => 60,
			'menu_icon'       => 'dashicons-megaphone',
			'taxonomies'      => array( 'ad-position' ),
			'supports'        => [ 'title', 'author' ],
		] );
		register_post_type( 'ad-content', $post_type_args );

		/* カスタムタクソノミーを定義 */
		$taxonomy_args = apply_filters( 'sk_ad_taxonomy_args', [
			'label'             => 'ポジション',
			'labels'            => [
				'name'          => '広告ポジション',
				'singular_name' => '広告ポジション',
				'search_items'  => '広告ポジションを検索',
				'popular_items' => 'よく使われている広告ポジション',
				'all_items'     => 'すべての広告ポジション',
				'parent_item'   => '広告ポジション',
				'edit_item'     => '広告ポジションの編集',
				'update_item'   => '広告ポジションを更新',
				'add_new_item'  => '新規広告ポジションを追加',
				'new_item_name' => '新しい広告ポジション',
			],
			'show_admin_column' => true,
			'hierarchical'      => false,
			'meta_box_cb'       => [ $this, 'metabox_cb' ],
		] );
		register_taxonomy( 'ad-position', 'ad-content', $taxonomy_args );
	}

	/**
	 * 投稿にカラムを追加
	 *
	 * @param string[] $columns カラム名
	 * @return string[]
	 */
	public function admin_columns( $columns ) {
		$columns['ad_limit'] = '配信期限';
		return $columns;
	}

	/**
	 * 投稿のカラムを表示する
	 *
	 * @param string $column_name から無名
	 * @return void
	 */
	public function do_admin_column( $column_name, $post_id ) {
		switch ( $column_name ) {
			case 'ad_limit':
				if ( $limit = get_post_meta( $post_id, '_post_expires', true ) ) {
					echo mysql2date( get_option( 'date_format' ) . ' H:i', $limit );
				} else {
					echo '<span style="color: grey;">---</span>';
				}
				break;
			default:
				// Do nothing.
				break;
		}
	}

	/**
	 * タクソノミーにカラムを追加
	 *
	 * @param string[] $columns カラム名
	 * @return array
	 */
	public function tax_columns( $columns ) {
		$columns['registered'] = '登録済み';
		return $columns;
	}

	/**
	 * タクソノミーにカラムを追加
	 *
	 * @param string $value
	 * @param string $column
	 * @param int    $term_id
	 *
	 * @return string
	 */
	public function do_tax_columns ( $value, $column, $term_id ) {
		switch ( $column ) {
			case 'registered':
				if ( $this->is_registered( $term_id ) ) {
					return '<span class="dashicons dashicons-thumbs-up" style="color: #4b9b6d;"></span>';
				} else {
					return '<span class="dashicons dashicons-thumbs-down" style="color: darkgrey;"></span>';
				}
			default:
				return $value;
		}
	}

	/**
	 * 管理画面に警告を表示
	 *
	 * @return void
	 */
	public function admin_notice() {
		$screen = get_current_screen();
		if ( $screen->id !== 'edit-ad-position' ) {
			return;
		}
		$should_register = $this->should_register_count();
		if ( 0 < $should_register ) {
			$this->register_defaults();
		}
		?>
		<div class="error">
			<p>
				<strong>※広告ポジションの注意点</strong> 利用できるポジションはテーマ側から決定および登録されています。
				スラッグの変更や新規追加を行わないでください。
			</p>
		</div>
		<?php
	}

	/**
	 * 投稿を取得する
	 *
	 * @param string $slug カテゴリースラッグ
	 *
	 * @return string
	 */
	public function get( $slug ) {
		// 広告が抑制中なら何もしない
		if ( $this->suppressed ) {
			return '';
		}
		foreach ( get_posts( [
			'post_type'      => $this->post_type,
		    'post_status'    => 'publish',
		    'posts_per_page' => 1,
		    'orderby' => [
			    'date' => 'DESC',
		    ],
		    'tax_query' => [
			    [
				    'taxonomy' => $this->taxonomy,
				    'field'    => 'slug',
				    'terms'    => $slug,
                ],
		    ],
		] ) as $ad ) {
			return (string) get_post_meta( $ad->ID, '_ad_content', true );
		}
		return '';
	}

	/**
	 * 該当する広告をすべて取得する
	 *
	 * @param string $slug
	 *
	 * @return array
	 */
	public function get_all( $slug ) {
		$ads = [];
		// 広告が抑制中なら何もしない
		if ( $this->suppressed ) {
			return $ads;
		}
		foreach ( get_posts( [
			'post_type' => $this->post_type,
			'post_status' => 'publish',
			'posts_per_page' => -1,
			'orderby' => [
				'date' => 'DESC',
			],
			'tax_query' => [
				[
					'taxonomy' => $this->taxonomy,
					'field'    => 'slug',
					'terms'    => $slug,
				],
			],
		] ) as $ad ) {
			$ads[] = (string) get_post_meta( $ad->ID, '_ad_content', true );
		}
		return $ads;
	}

	/**
	 * タクソノミーを登録すべきか
	 *
	 * @return int
	 */
	public function should_register_count() {
		$should = count( array_keys( $this->positions ) );
		$terms  = get_terms( [
			'taxonomy'   => $this->taxonomy,
			'hide_empty' => false,
		] );
		foreach ( $terms as $term ) {
			if ( isset( $this->positions[ $term->slug ]) ) {
				$should--;
			}
		}
		return $should;
	}

	/**
	 * デフォルトのタクソノミーを登録する
	 *
	 * @return int
	 */
	public function register_defaults() {
		$registered = 0;
		foreach ( $this->positions as $slug => $position ) {
			$term = get_term( $slug, $this->taxonomy );
			if ( is_a( $term, 'WP_Term' ) ) {
				$result = wp_update_term( $term->term_id, $this->taxonomy, [
					'term'        => $position['label'],
					'description' => $position['description'],
				] );
			} else {
				$result = wp_insert_term( $position['label'], $this->taxonomy, [
					'slug'        => $slug,
					'description' => $position['description'],
				] );
			}
			if ( ! is_wp_error( $result ) ) {
				$registered ++;
			}
		}

		return $registered;
	}

	/**
	 * メタボックスを表示する
	 *
	 * @param \WP_Post $post
	 * @param \WP_Screen $screen
	 */
	public function metabox_cb( $post, $screen ) {
		$terms = get_the_terms( $post, 'ad-position' );
		$tags  = [];
		if ( $terms && ! is_wp_error( $terms ) ) {
			foreach ( $terms as $term ) {
				$tags[] = $term->name;
			}
		}
		$all_terms = get_terms( 'ad-position', [ 'hide_empty' => false ] );
		if ( ! is_wp_error( $all_terms ) ) {
			$all_terms = array_filter( $all_terms, function ( $term ) {
				return $this->is_registered( $term );
			} );
		}
		?>
		<input type="hidden" name="tax_input[ad-position]" id="ad-position-saver"
		       value="<?= esc_attr( implode( ',', $tags ) ) ?>"/>
		<script>
			(function(){
				jQuery(document).ready(function($){
					$('.adPosition__check').click(function(){
						var value = [];
						$('.adPosition__check:checked').each(function(index, input){
							value.push($(input).val());
						});
						$('#ad-position-saver').val(value.join(','));
					});
				});
			})();
		</script>
		<?php if ( empty( $all_terms ) ) : ?>
			<p style="color: red;">広告ポジションが追加されていないようです。管理者に問い合わせてください。</p>
		<?php else : ?>
			<div class="adPosition">
				<?php foreach ( $all_terms as $term ) : ?>
					<div class="adPosition__item">
						<label class="adPosition__label">
							<input type="checkbox" class="adPosition__check" value="<?= esc_attr( $term->name ) ?>" <?php checked( has_term( $term->term_id, $term->taxonomy, $post ) ) ?>/>
							<?= esc_html( $term->name ) ?>
						</label>
						<p class="adPosition__description">
							<?= esc_html( $term->description ) ?>
						</p>
					</div>
				<?php endforeach; ?>
				<div style="clear: left;"></div>
				<hr/>
				<p class="adPosition__info">
					複数の場所にチェックすると、同じタグが複数の箇所に表示されます。
				</p>
			</div>
		<?php endif;
	}

	/**
	 * 指定された広告ポジションが登録済のものか
	 *
	 * @param string|int|\WP_Term $term タームスラッグまたはid
	 *
	 * @return bool
	 */
	public function is_registered( $term ) {
		if ( is_string( $term ) && ! is_numeric( $term ) ) {
			$term = get_term_by( 'slug', $term, 'ad-position' );
		} else {
			$term = get_term( $term, 'ad-position' );
		}
		if ( ! is_a( $term, 'WP_Term' ) ) {
			return false;
		}

		return isset( $this->positions[ $term->slug ] );
	}

	/**
	 * 特定のページで広告の表示・非表示を切り替える
	 */
	public function template_redirect() {
		if ( is_singular() ) {
			$meta = get_post_meta( get_post()->ID, '_ad_block', true );
			if ( $meta ) {
				$this->suppressed = true;
			}
		}
	}

/**
	 * ターム編集画面にフィールドを追加
	 *
	 * @param \WP_Term $term
	 */
	public function form_fields( $term ) {
		?>
		<tr>
			<th>登録済</th>
			<td>
				<?php if ( $this->is_registered( $term ) ) : ?>
					<p style="color: #4b9b6d;">
						<span class="dashicons dashicons-thumbs-up"></span>
						このポジションはテーマから登録されており、利用可能です。
					</p>
				<?php else : ?>
					<p style="color: #d93d2e;">
						<span class="dashicons dashicons-thumbs-down"></span>
						このポジションはテーマから登録されていません！ このままでは表示されません。
					</p>
				<?php endif; ?>
			</td>
		</tr>
		<?php
	}

	/**
	 * ゲッター
	 *
	 * @param string $name プロパティ名
	 *
	 * @return void
	 */
	public function __get( $name ) {
		switch( $name ) {
			case 'positions':
				return apply_filters( 'sk_ad_positions', [] );
			default:
				return null;
		}
	}
}
