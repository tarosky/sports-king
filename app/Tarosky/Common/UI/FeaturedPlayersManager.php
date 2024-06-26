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
class FeaturedPlayersManager extends Singleton {

	/**
	 * @var string
	 */
	const META_KEY = '_players_id';

	/**
	 * コンストラクタ
	 *
	 * @param array $settings
	 */
	public function __construct( array $settings = [] ) {
		add_action( 'add_meta_boxes', function($post_type) {
			if ( 'featured-players' == $post_type ) {
				add_meta_box( 'players-list', '選手リスト', [ $this, 'render_meta_box' ], 'featured-players', 'normal', 'default' );
			}
		} );
		if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
			add_action( 'wp_ajax_sk_player_search', [ $this, 'ajax' ] );
		}
		add_action('save_post', [$this, 'save_post'], 10, 2);
	}

	/**
	 * AJAXで検索
	 *
	 * @return void
	 */
	public function ajax() {
		wp_send_json( array_map( function($post){
			return [
				'id'   => $post->ID,
				'name' => get_the_title( $post ),
			    'team' => get_the_title( $post->post_parent ),
			    'link' => get_permalink( $post ),
			];
		}, get_posts( [
			'post_type' => 'player',
		    'post_status' => 'publish',
		    'posts_per_page' => 10,
		    's' => $this->input->get('term'),
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
		if ( $this->input->verify_nonce( 'featured_player', '_featuredplayernonce' ) ) {
			update_post_meta( $post_id, self::META_KEY, implode( ',', (array) $this->input->post( 'featured_player_id' ) ) );
		}
	}

	/**
	 * メタボックスを表示する
	 *
	 * @param \WP_Post $post
	 */
	public function render_meta_box( \WP_Post $post ) {
		wp_enqueue_script( 'featured-player', get_template_directory_uri().'/assets/js/admin/featured-player-helper.js', [
			'jquery-ui-autocomplete', 'jquery-effects-highlight', 'jquery-ui-sortable',
		], sk_theme_version(), true );
		wp_nonce_field( 'featured_player', '_featuredplayernonce', false );
		$players = self::get_players( $post->ID );
		?>
		<div class="sk_featrued_players">

			<table class="form-table">
				<tr>
					<th>選手検索</th>
					<td>
						<input type="text" class="regular-text" id="sk-featured-player-search" data-href="<?= admin_url('admin-ajax.php?action=sk_player_search') ?>" placeholder="入力して選手を検索">
					</td>
				</tr>
			</table>
			<ul class="sk_featured_players__list">
				<?php foreach ( $players as $player ) : ?>
					<li class="sk_featured_players__item">
						<input type="hidden" name="featured_player_id[]" value="<?= $player->ID ?>" />
						<a href="<?= get_permalink( $player ) ?>" class="sk_featured_players__name" target="_blank">
							<?= esc_html( get_the_title( $player ) ) ?>
						</a>
						<small class="sk_featured_players__team">
							<?= esc_html( get_the_title( $player->post_parent ) ) ?>
						</small>
						<a href="#" class="close">
							<span class="dashicons dashicons-no"></span>
						</a>
						<a href="#" class="drag">
							<span class="dashicons dashicons-menu"></span>
						</a>
					</li>
				<?php endforeach; ?>
			</ul>
			<script type="text/template" class="sk_featured_players__template">
				<li class="sk_featured_players__item">
					<input type="hidden" name="featured_player_id[]" value="" />
					<a href="" class="sk_featured_players__name" target="_blank"></a>
					<small class="sk_featured_players__team"></small>
					<a href="#" class="close">
						<span class="dashicons dashicons-no"></span>
					</a>
					<a href="#" class="drag">
						<span class="dashicons dashicons-menu"></span>
					</a>
				</li>
			</script>
		</div><!-- //.sk_featured_players -->
		<?php
	}

	/**
	 * プレイヤーを取得する
	 *
	 * @param int $post_id
	 *
	 * @return array
	 */
	public static function get_players( $post_id ) {
		$post_ids = array_filter( explode( ',', get_post_meta( $post_id, self::META_KEY, true ) ), 'is_numeric' );
		if ( $post_ids ) {
			$posts = get_posts([
				'post_type' => 'player',
			    'post__in' => $post_ids,
			    'post_status' => 'publish',
			]);
			$result = [];
			foreach ( $post_ids as $p ) {
				foreach ( $posts as $post ) {
					if ( $p == $post->ID ) {
						$result[] = $post;
						break;
					}
				}
			}
			return $result;
		} else {
			return [];
		}
	}

	/**
	 * 取得する
	 *
	 * @param string $name
	 *
	 * @return null|static
	 */
	public function __get( $name ){
		switch($name){
			case 'input':
				return Input::instance();
				break;
			default:
				return null;
				break;
		}
	}

}
