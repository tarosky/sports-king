<?php

namespace Tarosky\Common\Tarosky\Common\UI;

use Tarosky\Common\Tarosky\Common\Pattern\Singleton;
use Tarosky\Common\Tarosky\Common\Utility\Input;
use function Tarosky\Common\UI\add_action;
use function Tarosky\Common\UI\admin_url;
use function Tarosky\Common\UI\esc_attr;
use function Tarosky\Common\UI\get_post_meta;
use function Tarosky\Common\UI\get_posts;
use function Tarosky\Common\UI\is_admin;
use function Tarosky\Common\UI\update_post_meta;
use function Tarosky\Common\UI\wp_enqueue_script;
use function Tarosky\Common\UI\wp_is_post_autosave;
use function Tarosky\Common\UI\wp_is_post_revision;
use function Tarosky\Common\UI\wp_nonce_field;
use function Tarosky\Common\UI\wp_send_json;
use const Tarosky\Common\UI\DOING_AJAX;

/**
 * Recommend manager
 * @package Tarosky\Common\UI
 * @property-read Input $input
 */
class BestManager extends Singleton {

	/**
	 * @var array 表示する投稿タイプ
	 */
	protected $post_types = [ 'bk_best_member' ];

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
					add_meta_box( 'bk-bestmember-list', 'ベストメンバー選択', [ $this, 'add_meta_box' ], $post_type, 'normal', 'high' );
				}
			});
			// Save
			add_action( 'save_post', [ $this, 'save_post' ], 10, 2 );
			if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
				add_action( 'wp_ajax_sk_player_search', [ $this, 'ajax' ] );
			}
		}
	}

	/**
	 * Parse Ajax request.
	 */
	public function ajax() {

		$args = [
			'post_type'      => [ 'player' ],
			'post_status'    => [ 'publish' ],
			's'              => $this->input->get( 'term' ),
			'posts_per_page' => 10,
		    'suppress_filters' => false,
		];

		wp_send_json( array_map( function ( $post ) {
			$team = sk_players_team( $post );
			$team_name = $team ? $team->post_title : 'チーム無し' ;
			$image_tag = '<span>No Image</span>';
			if( $image_src = sk_get_player_best_member_image_src( $post ) ) {
				$image_tag = sprintf('<img src="%s" />', $image_src);
			}

			$pos = '';
			if( $position_list = sk_meta( '_player_position', $post ) ) {
				$pos = $position_list;
			}
/*
			$player_posistions = sk_players_positions( $post );
			if( $player_posistions ) {
				$role_list = sk_position_role( );
				foreach ( $player_posistions as $pos_key => $pos_val ){
					if( $pos_val && false === in_array( $role_list[$pos_key], $pos ) ) {
						$pos[] = $role_list[$pos_key];
					}
				}
			}
 *
 */

			$omit_title = '省略名無し';
			if( $omit = sk_tscfp('_player_best_member_omit', $post) ) {
				$omit_title = $omit;
			}
			return [
				'id' => $post->ID,
				'image_tag' => $image_tag,
				'value' => $post->post_title,
				'label' => sprintf( '%s{ %s }( %s )【 %s 】', $post->post_title, $omit_title, $pos, $team_name ),
				'position' => sprintf( '( %s )', $pos ),
				'team' => sprintf( '【 %s 】', $team_name ),
			];
		}, get_posts( $args ) ) );
	}


	/**
	 * メタボックスを表示
	 *
	 * @param \WP_Post $post
	 */
	public function add_meta_box( \WP_Post $post ) {
			wp_enqueue_script( 'sk-best-member-helper', get_template_directory_uri() . '/assets/js/admin/best-member-helper.js', [ 'jquery-ui-autocomplete', 'jquery-effects-highlight' ], sk_theme_version(), true );


			$args = [
				'post_type'      => [ 'player' ],
				'post_status'    => [ 'publish' ],
				'posts_per_page' => -1,
				'include' => -1,
				'suppress_filters' => false,
			];

			$includes = (array)get_post_meta( $post->ID, '_best_member_selects', true );
			if( current($includes) ) {
				$args['include'] = implode( ', ', $includes );
			}
			if( $list = get_posts( $args ) ){
//				$role_list = sk_position_role( );
				foreach( $list as $player ) {
					$team = sk_players_team( $player );
					$team_name = $team ? $team->post_title : 'チーム無し' ;
					$image_tag = '<span>No Image</span>';
					if( $image_src = sk_get_player_best_member_image_src( $player ) ) {
						$image_tag = sprintf('<img src="%s" />', $image_src);
					}

					$pos = '';
					if( $position_list = sk_meta( '_player_position', $player ) ) {
						$pos = $position_list;
					}
/*
					$player_posistions = sk_players_positions( $player );
					$pos = [];
					if( $player_posistions ) {
						foreach ( $player_posistions as $pos_key => $pos_val ){
							if( $pos_val && false === in_array( $role_list[$pos_key], $pos ) ) {
								$pos[] = $role_list[$pos_key];
							}
						}
					}
 *
 */

					$omit_title = '省略名無し';
					if( $omit = sk_tscfp('_player_best_member_omit', $player) ) {
						$omit_title = $omit;
					}

					$best_list[] = [
						'id' => $player->ID,
						'image' => $image_tag,
						'title' => $player->post_title,
						'omit_title' => sprintf( '{ %s }', $omit_title ),
						'position' => sprintf( '( %s )', $pos),
						'team' => sprintf( '【 %s 】', $team_name ),
					];
				}
			}

			$best_list[] = [
				'id' => 0,
				'image' => '',
				'title' => '',
				'omit_title' => '',
				'position' => '',
				'team' => '',
			    'script' => true,
			];

			?>
			<div class="sk_best_members">
				<div class="sk_best_members__controller">
					<input class="sk_best_members__search" type="text" placeholder="検索して追加 ex."
					       data-endpoint="<?= admin_url('admin-ajax.php?action=sk_player_search') ?>" />
				</div>
				<ul class="sk_best_members__list<?php if( !$best_list ) echo ' sk_best_members__list--empty'; ?>" data-max="10">
					<?php $counter = 0; foreach ( $best_list as $member ) : $is_script = isset($member['script']); ?>
						<?php if ( $is_script ) :?>
							<script type="text/template" class="sk_best_members__tpl">
						<?php endif; ?>

						<li class="sk_best_members__row">
							<div class="sk_best_members__data">
								<input class="sk_best_members__id" type="hidden" name="sk_best_members_id[<?= esc_attr($counter) ?>]"
								       value="<?= esc_attr($member['id']) ?>" />
								<div class="sk_best_members__image"><?= $member['image']; ?></div>
								<div class="sk_best_members__title"><?= $member['title']; ?></div>
								<div class="sk_best_members__omit_title"><?= $member['omit_title']; ?></div>
								<div class="sk_best_members__position"><?= $member['position']; ?></div>
								<div class="sk_best_members__team"><?= $member['team']; ?></div>
							</div>
							<div class="sk_best_members__action">
								<a class="button sk_best_members__delete" href="#">削除</a>
							</div>
							<div style="clear: left;">

							</div>
						</li>

					<?php if ( $is_script ) :?>
						</script>
					<?php endif; ?>
					<?php $counter++; endforeach; ?>
				</ul>
				<p class="sk_best_members__empty">
					プレイヤーが登録されていません。
				</p>
			</div>
			<?php
			wp_nonce_field( 'update_bests', '_bestnonce', false );
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

		if ( $this->input->verify_nonce( 'update_bests', '_bestnonce' ) ) {
			$links    = [];
			$ids     = (array) $this->input->post( 'sk_best_members_id' );
			update_post_meta( $post_id, '_best_member_selects', $ids );
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
