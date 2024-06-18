<?php

namespace Tarosky\Common\UI;


use Tarosky\Common\Pattern\Singleton;
use Tarosky\Common\Utility\Input;

/**
 * オススメ選手のリスト
 *
 * @package Tarosky\Common\UI
 * @property Input $input
 */
class RelatedMediaManager extends Singleton {

	/**
	 * @var string
	 */
	const META_KEY = '_related_media';

	/**
	 * コンストラクタ
	 *
	 * @param array $settings
	 */
	public function __construct( array $settings = [] ) {
		add_action( 'add_meta_boxes', function($post_type) {
			if ( 'post' == $post_type ) {
				add_meta_box( 'related-media', '関連媒体', [ $this, 'render_meta_box' ], 'post', 'advanced', 'low' );
			}
		} );
		if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
			add_action( 'wp_ajax_sk_media_search', [ $this, 'ajax' ] );
		}
		add_action( 'save_post', [$this, 'save_post'], 10, 2 );
	}

	/**
	 * AJAXで検索
	 *
	 * @return void
	 */
	public function ajax() {
		wp_send_json( array_map( function($post){
			$terms = get_the_terms( $post, 'media_cat' );
			return [
				'id'   => $post->ID,
				'name' => get_the_title( $post ),
			    'media' => is_array($terms) ? implode(', ', array_map(function($term){
				    return $term->name;
			    }, $terms) ) : '',
			    'image' => has_post_thumbnail( $post ) ? wp_get_attachment_image(get_post_thumbnail_id($post), 'thumbnail') : '',
			];
		}, get_posts( [
			'post_type' => 'media',
		    'post_status' => 'publish',
		    'posts_per_page' => 10,
		    's' => $this->input->get( 'term' ),
		] ) ) );
	}

	/**
	 * 保存する
	 *
	 * @param int $post_id
	 * @param \WP_Post $post
	 */
	public function save_post( $post_id, $post ) {
		if ( wp_is_post_autosave( $post ) || wp_is_post_revision( $post ) ) {
			return;
		}
		if ( $this->input->verify_nonce( 'related_media', '_relatedmedianonce' ) ) {
			update_post_meta( $post_id, self::META_KEY, $this->input->post( 'related_media_id' ) );
		}
	}

	/**
	 * メタボックスを表示する
	 *
	 * @param \WP_Post $post
	 */
	public function render_meta_box( \WP_Post $post ) {
		wp_enqueue_script( 'related-media', get_template_directory_uri().'/assets/js/admin/related-media-helper.js', [
			'jquery-ui-autocomplete', 'jquery-effects-highlight',
		], sk_theme_version(), true );
		wp_nonce_field( 'related_media', '_relatedmedianonce', false );
		$media = self::get_media( $post->ID );
		?>
		<div class="sk_related_media">

			<label>
				<strong>媒体検索</strong><br/>
				<input type="text" id="sk-related-media-search"
				       data-href="<?= admin_url( 'admin-ajax.php?action=sk_media_search' ) ?>" placeholder="入力して媒体を検索">
			</label>
			<div class="sk_related_media__display">
				<input type="hidden" name="related_media_id" value="<?= $media ? $media->ID : '' ?>">
				<div class="sk_related_media__image">
					<?php if( $media && has_post_thumbnail($media) ) : ?>
						<?= wp_get_attachment_image(get_post_thumbnail_id($media), 'thumbnail') ?>
					<?php endif; ?>
				</div>
				<div class="sk_related_media__title"><?= $media ? get_the_title($media) : '' ?></div>
				<div class="sk_related_media__category">
					<?php if ( $media ) : ?>
						<?php $terms = get_the_terms($media, 'media_cat'); ?>
						<?php if ( is_array($terms) ) : ?>
						<?= implode( ', ', array_map(function($term){
							return $term->name;
						}, $terms ) ); ?>
						<?php endif; ?>
					<?php endif; ?>
				</div>
				<a class="button" href="#" <?= $media ? '' : ' disabled' ?>>削除</a>
				<div style="clear:left"></div>
			</div>
		</div><!-- //.sk_featured_players -->
		<?php
	}

	/**
	 * メディアを取得する
	 *
	 * @param int $post_id
	 *
	 * @return \WP_Post
	 */
	public static function get_media( $post_id ) {
		$media_id = get_post_meta( $post_id, self::META_KEY, true );
		if ( ! $media_id || ! ( $media = get_post($media_id) ) || 'media' !== $media->post_type || 'publish' !== $media->post_status ) {
			return null;
		} else {
			return $media;
		}
	}

	/**
	 * 最新のメディア関連ニュースを表示
	 *
	 * @param \WP_Term $term
	 * @param int $limit
	 * @param int $offset
	 *
	 * @return array|null|object
	 */
	public static function get_media_news( $term, $limit = 10, $offset = 0 ) {
		global $wpdb;
		$query = <<<SQL
			SELECT DISTINCT p.* FROM {$wpdb->postmeta} AS pm
			INNER JOIN {$wpdb->posts} AS p
			ON p.ID = pm.post_id AND p.post_status = 'publish'
			INNER JOIN {$wpdb->posts} AS media
			ON media.ID = pm.meta_value AND media.post_type = 'media'
			INNER JOIN {$wpdb->term_relationships} AS tr
			ON media.ID = tr.object_id
			WHERE pm.meta_key = %s
			  AND tr.term_taxonomy_id = %d
			ORDER BY p.post_date DESC
			LIMIT %d, %d
SQL;
		return $wpdb->get_results( $wpdb->prepare( $query, self::META_KEY, $term->term_taxonomy_id, $offset, $limit ) );
	}

	/**
	 * Getter
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
