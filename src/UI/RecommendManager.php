<?php

namespace Tarosky\Common\UI;

use Tarosky\Common\Pattern\Singleton;
use Tarosky\Common\Utility\Input;

/**
 * Recommend manager
 * @package Tarosky\Common\UI
 * @property-read Input $input
 */
class RecommendManager extends Singleton {

	/**
	 * @var array 表示する投稿タイプ
	 */
	protected $post_types = [ 'post', 'sk_blog', 'pnews', 'bk_best_member' ];
	protected $yahoo_post_types = [ 'post', 'sk_blog', 'pnews' ];

	/**
	 * コンストラクタ
	 *
	 * @param array $settings
	 */
	protected function __construct( array $settings = [] ) {
		if ( is_admin() ) {
			// エディターの後に表示
			add_action('add_meta_boxes', function($post_type){
				if ( false !== array_search( $post_type, $this->post_types ) ) {
					add_meta_box( 'sk-recommend-links', 'あわせて読みたい', [ $this, 'add_meta_box' ], $post_type, 'normal', 'high' );
				}
				if ( false !== array_search( $post_type, $this->yahoo_post_types ) ) {
					add_meta_box( 'sk-recommend-image-links', 'yahoo関連画像リンク', [ $this, 'add_meta_box_ril' ], $post_type, 'normal', 'high' );
				}
			});
			// Save
			add_action( 'save_post', [ $this, 'save_post' ], 10, 2 );
			if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
				add_action( 'wp_ajax_sk_link_search', [ $this, 'ajax' ] );
				add_action( 'wp_ajax_sk_recommend_auto', [$this, 'ajax_recommend'] );
				add_action( 'wp_ajax_sk_recommend_image_auto', [$this, 'ajax_recommend_image'] );
			}
		}
	}

	/**
	 * Parse Ajax request.
	 */
	public function ajax() {
		$args = [
			'post_type'      => [ 'post', 'sk_blog', 'bk_best_member' ],
			'post_status'    => [ 'publish', 'future' ],
			's'              => $this->input->get( 'term' ),
			'order'          => [
				'date' => 'DESC',
			],
			'posts_per_page' => 10,
		    'suppress_filters' => false,
		];
		wp_send_json( array_map( function ( $post ) {
			return [
				'value' => $post->post_title,
				'label' => sprintf( '【%s】%s %s', get_post_type_object( $post->post_type )->labels->name, $post->post_title, mysql2date( 'Y.m.d', $post->post_date ) ),
			    'url'  => get_permalink( $post ),
			];
		}, get_posts( $args ) ) );
	}

	public function ajax_recommend(){
		wp_send_json( array_map( function ( $post ) {
			return [
				'title' => $post->post_title,
				'url'  => get_permalink($post),
			];
		}, get_posts( [
			'post_type'      => [ 'post', 'bk_best_member' ],
			'post_status'    => [ 'publish', 'future' ],
			'order' => [
				'date' => 'DESC',
			],
			'posts_per_page' => 5,
		] ) ) );
	}
	public function ajax_recommend_image(){
		wp_send_json( array_map( function ( $post ) {
			return [
				'title' => $post->post_title,
				'url'  => get_permalink($post),
			];
		}, get_posts( [
			'post_type'      => [ 'post' ],
			'post_status'    => [ 'publish', 'future' ],
			'order' => [
				'date' => 'DESC',
			],
			'posts_per_page' => 1,			
		] ) ) );
	}

	/**
	 * メタボックスを表示
	 *
	 * @param \WP_Post $post
	 */
	public function add_meta_box( \WP_Post $post ) {
			wp_enqueue_script( 'sk-link-helper', get_template_directory_uri() . '/assets/js/admin/link-helper.js', [ 'jquery-ui-autocomplete', 'jquery-effects-highlight' ], sk_theme_version(), true );
			$links = array_filter( (array) get_post_meta( $post->ID, '_recommend_links', true ), function($link){
				return ! empty( $link );
			} );
			$links[] = [
				'title' => '',
			    'url'   => '',
			    'external' => false,
			    'script' => true,
			];
			?>
			<div class="sk_recommends">
				<div class="sk_recommends__controller">
					<a class="button sk_recommends__add" href="#">追加</a>
					<a class="button sk_recommends__auto" href="#" data-endpoint="<?= admin_url('admin-ajax.php') ?>">自動候補</a>
					<input class="sk_recommends__search" type="text" placeholder="検索して追加 ex. 田中 アルバルク"
					       data-endpoint="<?= admin_url('admin-ajax.php?action=sk_link_search') ?>" />
				</div>
				<ul class="sk_recommends__list<?php if( !$links ) echo ' sk_recommends__list--empty'; ?>" data-max="10">
					<?php $counter = 0; foreach ( $links as $link ) : $is_script = isset($link['script']); ?>
						<?php if ( $is_script ) :?>
						<script type="text/template" class="sk_recommends__tpl">
						<?php endif; ?>
						<li class="sk_recommends__row">
							<div class="sk_recommends__data">
								<input class="sk_recommends__title" type="text" name="sk_recommend_title[<?= esc_attr($counter) ?>]"
								       value="<?= esc_attr($link['title']) ?>" placeholder="テキスト" />
								<input class="sk_recommends__url" type="text" name="sk_recommend_url[<?= esc_attr($counter) ?>]"
								       value="<?= esc_attr($link['url']) ?>" placeholder="URL" />
								<label>
									<input type="checkbox" class="sk_recommends__external"
									       name="sk_recommend_external[<?= esc_attr( $counter ) ?>]"
									       value="1" <?php checked( $link['external'] ) ?> />
									別窓
								</label>
							</div>
							<div class="sk_recommends__action">
								<a class="sk_recommends__up" href="#">▲</a>
								<a class="button sk_recommends__delete" href="#">削除</a>
								<a class="sk_recommends__down" href="#">▼</a>
							</div>
							<div style="clear: left;">

							</div>
						</li>
					<?php if ( $is_script ) :?>
						</script>
					<?php endif; ?>
					<?php $counter++; endforeach; ?>
				</ul>
				<p class="sk_recommends__empty">
					リンクが登録されていません。
				</p>
				<p class="sk_recommends__description">リンクはいくつでも追加できますが、5つまでしか表示されません。</p>
			</div>
			<?php
			wp_nonce_field( 'update_recommends', '_recommendsnonce', false );
	}

	/**
	 * yahoo関連画像リンクメタボックスを表示
	 *
	 * @param \WP_Post $post
	 */
	public function add_meta_box_ril( \WP_Post $post ) {
			wp_enqueue_script( 'sk-link-helper', get_template_directory_uri() . '/assets/js/admin/link-helper.js', [ 'jquery-ui-autocomplete', 'jquery-effects-highlight' ], sk_theme_version(), true );
			$links = array_filter( (array) get_post_meta( $post->ID, '_recommend_image_links', true ), function($link){
				return ! empty( $link );
			} );
			$links[] = [
				'title' => '',
			    'url'   => '',
			    'script' => true,
			];
			?>
			<div class="sk_recommends_image">
				<div class="sk_recommends_image__controller">
					<a class="button sk_recommends_image__add" href="#">追加</a>
					<a class="button sk_recommends_image__auto" href="#" data-endpoint="<?= admin_url('admin-ajax.php') ?>">自動候補</a>
					<input class="sk_recommends_image__search" type="text" placeholder="検索して追加 ex. 香川 アーセナル"
					       data-endpoint="<?= admin_url('admin-ajax.php?action=sk_link_search') ?>" />
				</div>
				<ul class="sk_recommends_image__list<?php if( !$links ) echo ' sk_recommends_image__list--empty'; ?>" data-max="10">
					<?php $counter = 0; foreach ( $links as $link ) : $is_script = isset($link['script']); ?>
						<?php if ( $is_script ) :?>
						<script type="text/template" class="sk_recommends_image__tpl">
						<?php endif; ?>
						<li class="sk_recommends_image__row">
							<div class="sk_recommends_image__data">
								<input class="sk_recommends_image__title" type="text" name="sk_recommend_image_title[<?= esc_attr($counter) ?>]"
								       value="<?= esc_attr($link['title']) ?>" placeholder="テキスト" />
								<input class="sk_recommends_image__url" type="text" name="sk_recommend_image_url[<?= esc_attr($counter) ?>]"
								       value="<?= esc_attr($link['url']) ?>" placeholder="URL" />
							</div>
							<div class="sk_recommends_image__action">
								<a class="sk_recommends_image__up" href="#">▲</a>
								<a class="button sk_recommends_image__delete" href="#">削除</a>
								<a class="sk_recommends_image__down" href="#">▼</a>
							</div>
							<div style="clear: left;">
							</div>
						</li>
					<?php if ( $is_script ) :?>
						</script>
					<?php endif; ?>
					<?php $counter++; endforeach; ?>
				</ul>
				<p class="sk_recommends_image__empty">
					リンクが登録されていません。
				</p>
				<p class="sk_recommends_image__description">
					yahoo記事の関連写真リンクに設定するための項目です。<br />
					リンクはいくつでも追加できますが、1つまでしか表示されません。
				</p>
			</div>
			<?php
			wp_nonce_field( 'update_recommends_image', '_recommendimagesnonce', false );
	}
	
	/**
	 * 保存
	 *
	 * @param int $post_id
	 * @param \WP_Post $post
	 */
	public function save_post( $post_id, $post ) {
		if ( wp_is_post_revision( $post ) || wp_is_post_autosave( $post ) ) {
			return;
		}

		if ( $this->input->verify_nonce( 'update_recommends', '_recommendsnonce' ) ) {
			$links    = [];
			$urls     = (array) $this->input->post( 'sk_recommend_url' );
			$titles   = (array) $this->input->post( 'sk_recommend_title' );
			$external = (array) $this->input->post( 'sk_recommend_external' );
			foreach ( $urls as $index => $url ) {
				$link = [
					'title'    => isset( $titles[ $index ] ) ? $titles[ $index ] : '',
					'url'      => $url,
					'external' => isset( $external[ $index ] ) && $external[ $index ],
				];
				if ( $link['title'] && $link['url'] ) {
					$links[] = $link;
				}
			}
			update_post_meta( $post_id, '_recommend_links', $links );
		}
		
		if ( $this->input->verify_nonce( 'update_recommends_image', '_recommendimagesnonce' ) ) {
			$links    = [];
			$urls     = (array) $this->input->post( 'sk_recommend_image_url' );
			$titles   = (array) $this->input->post( 'sk_recommend_image_title' );
			foreach ( $urls as $index => $url ) {
				$link = [
					'title'    => isset( $titles[ $index ] ) ? $titles[ $index ] : '',
					'url'      => $url,
				];
				if ( $link['title'] && $link['url'] ) {
					$links[] = $link;
				}
			}
			update_post_meta( $post_id, '_recommend_image_links', $links );
		}
	}

	/**
	 * マジックメソッド
	 *
	 * @param string $name
	 *
	 * @return null|static
	 */
	public function __get( $name ) {
		switch ( $name ) {
			case 'input':
				return Input::instance();
				break;
			default:
				return null;
				break;
		}
	}

}
