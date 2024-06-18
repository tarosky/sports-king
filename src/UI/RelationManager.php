<?php

namespace Tarosky\Common\UI;


use Tarosky\BasketBallKing\Statics\Leagues;
use Tarosky\Common\Models\MatchRelationships;
use Tarosky\Common\Models\ObjectRelationships;
use Tarosky\Common\Pattern\Singleton;
use Tarosky\Common\Utility\Input;

/**
 * Class DeliveryManager
 * @package Tarosky\Common\UI
 * @property-read Input $input
 * @property-read ObjectRelationships $relation
 * @property-read MatchRelationships $matches
 */
class RelationManager extends Singleton {
	/**
	 * @var array Types
	 */
	protected $types = [
		'team'   => '関係するチーム',
		'player' => '関係する選手',
	];

	protected $post_types = [ 'post', 'picked-stats', 'bk_best_member', 'video'  ];

	protected $form_types = [
		'post' => ['match', 'team', 'player'],
		'picked-stats' => ['match'],
		'bk_best_member' => ['match', 'team', 'player'],
        'video' => ['match', 'team', 'player'],
	];

	/**
	 * RelationManager constructor.
	 *
	 * @param array $settings
	 */
	protected function __construct( array $settings = [] ) {
		if ( is_admin() ) {
			// Add title
			add_action( 'edit_form_after_title', [ $this, 'after_title' ] );
			// Save
			add_action( 'save_post', [ $this, 'save_post' ], 10, 2 );
			if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
				add_action( 'wp_ajax_sk_relation_search', [ $this, 'ajax' ] );
				add_action( 'wp_ajax_sk_match_search', [ $this, 'matchjax' ] );
			}
		}
		add_action( 'bcn_after_fill', [ $this, 'bcn_primary' ] );
	}

	/**
	 * Parse Ajax request.
	 */
	public function ajax() {
		wp_send_json( array_map( function ( $post ) {
			$name = $post->post_title;
			$icon = '';
			if( $this->input->get( 'type' ) == 'player' ) {
				if( $image = wp_get_attachment_image_src(get_post_thumbnail_id($post->ID) ) ){
					$icon = $image[0];
				}
				if( $team = sk_players_team($post->ID) ){
					$name .= sprintf( '(%s)', $team->post_title );
				}
			}

			return [
				'id'   => $post->ID,
				'name' => $name,
				'icon' => $icon,
			];
		}, get_posts( [
			'post_type'      => $this->input->get( 'type' ),
			'post_status'    => [ 'publish', 'future' ],
			's'              => $this->input->get( 'q' ),
			'posts_per_page' => 10,
		] ) ) );
	}

	/**
	 * 検索してJSON返す
	 */
	public function matchjax(){
		$result = $this->matches->search( $this->input->get('s'), $this->input->get('abroad'), $this->input->get('year'), $this->input->get('month'), $this->input->get('day'), $this->input->get('league'), 20 );
		wp_send_json($result);
	}

	/**
	 * タイトル直下に配信ステータスを出す
	 *
	 * @param \WP_Post $post
	 */
	public function after_title( \WP_Post $post ) {
		if ( false !== array_search( $post->post_type, $this->post_types ) ) {
			wp_enqueue_script( 'sk-relation-helper', get_template_directory_uri() . '/assets/js/admin/relation-helper.js', [ 'jquery-tokeninput', 'jquery-ui-dialog' ], sk_theme_version(), true );
			$relation = $this->matches->get_match( $post->ID );
			$relation_id = $relation ? $relation['id'] : 'new';
			$rel_type = $relation ? $relation['type'] : 'prompt';
			?>

			<?php if( false !== array_search( 'match', $this->form_types[$post->post_type] ) ) : ?>
			<div class="sk_match">
				<div class="sk_match__title">このニュースが関係する試合</div>
				<div class="sk_match__search">
					<input type="hidden" value="<?= esc_attr( $relation_id )  ?>" name="match-rel-id">
					<input type="hidden" value="<?= $relation ? $relation['match_id'] : '' ?>" name="match-block-id">
					<input type="hidden" value="<?= $relation ? $relation['abroad'] : '' ?>" name="match-abroad">
					<input type="hidden" value="<?= $relation ? $relation['league'] : '' ?>" name="match-league-id">
					<input type="hidden" value="" name="match-should-delete" />

					<span class="sk_match__status">
					<?php if ( $relation ) : ?>
						<span class="sk_match__name"><?= esc_html( $relation['label'] ) ?><a href="#">&times;</a></span>
					<?php else : ?>
						<span class="sk_match__name--no">指定なし</span>
					<?php endif; ?>
					</span>
					<a class="button sk_match__open" href="#sk-match-dialog">変更</a>

					<label class="sk_match__type">
						記事タイプ
						<select name="match-rel-type">
							<?php foreach (
								[
									'preview'   => 'プレビュー',
									'prompt'    => '速報',
									'interview' => 'インタビュー',
									'review'    => 'レビュー',
								] as $value => $label
							) : ?>
								<option
									value="<?= esc_attr( $value ) ?>" <?php selected( $value == $rel_type ) ?>><?= esc_html( $label ) ?></option>
							<?php endforeach; ?>
						</select>
					</label>

					<div style="display: none;">
						<div id="sk-match-dialog" title="試合検索フォーム" data-endpoint="<?= admin_url('admin-ajax.php') ?>">
							<p class="sk-search__paragraph">
								<label class="sk-search__label">
									<select name="sk-search-year">
										<option value="">指定しない</option>
										<?php for ( $i = date_i18n( 'Y' ) + 2; $i >= 2010; $i -- ) : ?>
											<option
												value="<?= $i ?>" <?php selected( $i == date_i18n( 'Y' ) ) ?>><?= $i ?>年
											</option>
										<?php endfor; ?>
									</select>
								</label>
								<label class="sk-search__label">
									<select name="sk-search-month">
										<option value="">指定しない</option>
										<?php for ( $i = 1; $i <= 12; $i ++ ) : ?>
											<option
												value="<?= $i ?>" <?php selected( $i == date_i18n( 'n' ) ) ?>><?= $i ?>月
											</option>
										<?php endfor; ?>
									</select>
								</label>
								<label class="sk-search__label">
									<select name="sk-search-day">
										<option value="" selected>指定しない</option>
										<?php for ( $i = 1; $i <= 31; $i ++ ) : ?>
											<option value="<?= $i ?>"><?= $i ?>日</option>
										<?php endfor; ?>
									</select>
								</label>
							</p>
							<p class="sk-search__paragraph">
								<label class="sk-search__label--block">
									チーム名
									<small class="sk-search__small">部分一致</small>
									<br/>
									<input type="text" name="sk-search-text" class="regular-text"/>
								</label>
							</p>
							<p class="sk-search__paragraph">
								<label class="sk-search__radio">
									<input type="radio" name="sk-search-abroad" value="0" checked/> 国内
								</label>
								<label class="sk-search__radio">
									<input type="radio" name="sk-search-abroad" value="1"/> 海外
								</label>
							</p>
							<?php $all_legues = Leagues::LEAGUES; ?>
							<p class="sk-search__paragraph league-japan">
								<label class="sk-search__radio">
									<input type="radio" name="sk-search-league" value="" checked /> All
								</label>
								<?php foreach ( $all_legues[0] as $id => $league ) : ?>
									<label class="sk-search__radio">
										<input type="radio" name="sk-search-league" value="<?= $id; ?>" /> <?= $league['label']; ?>
									</label>
								<?php endforeach; ?>
							</p>
							<p class="sk-search__paragraph league-world hidden">
								<label class="sk-search__radio">
									<input type="radio" name="sk-search-league" value="" /> All
								</label>
								<?php foreach ( $all_legues[1] as $id => $league ) : ?>
									<label class="sk-search__radio">
										<input type="radio" name="sk-search-league" value="<?= $id; ?>" /> <?= $league['label']; ?>
									</label>
								<?php endforeach; ?>
							</p>
							<ul class="sk-search__result">
							</ul>
						</div><!-- //#sk-match-dialog -->
					</div><!-- hidden warpper -->
				</div>
			</div>
			<?php endif; ?>

			<script type="text/javascript">
				window.SkRelation = {};
			</script>
			<div class="sk_relation">
				<?php $checked = get_post_meta( $post->ID, 'sk_relation_is_disable', true) ? 'checked="checkd"' : '' ; ?>
				<?php $trs_checked = get_post_meta( $post->ID, 'sk_trs_relation_is_disable', true) ? 'checked="checkd"' : '' ; ?>
				<div class="sk_relation__block">
					<label for="sk_trs_relation_is_disable"><input id="sk_trs_relation_is_disable" type="checkbox" name="sk_trs_relation_is_disable" <?php echo $trs_checked; ?>>この記事では自動リンクをさせない</label>
					<br>
					<label for="sk_relation_is_disable"><input id="sk_relation_is_disable" type="checkbox" name="sk_relation_is_disable" <?php echo $checked; ?>>この記事では関連チーム・関連選手の本文内リンクさせない</label>
				</div>
				<?php foreach ( $this->types as $type => $label ) : ?>
					<?php if( false !== array_search( $type, $this->form_types[$post->post_type] ) ) : ?>
					<div class="sk_relation__field">
						<label class="sk_relation__label"
						       for="sk-relation-<?= esc_attr( $type ) ?>"><?= esc_html( $label ) ?></label>
						<input type="text" name="sk_relation_<?= $type ?>" class="sk_relation__input"
						       data-type="<?= esc_html( $type ) ?>"
						       id="sk-relation-<?= esc_attr( $type ) ?>" value=""
						       data-endpoint="<?= admin_url( 'admin-ajax.php?action=sk_relation_search&type=' . $type ) ?>"/>
						<script type="text/javascript">
							<?php
							$json = [];
							foreach ( $this->relation->get_relation( $type, $post->ID, ['logbook'] ) as $p ) {
								$json[] = [
									'id'   => $p->ID,
									'name' => $p->post_title,
								];
							}
							printf( 'window.SkRelation.%s = %s;', $type, json_encode( $json ) );
							?>
						</script>
					</div>
					<?php endif; ?>
				<?php endforeach; ?>
				<div style="clear: left"></div>
			</div>
			<div class="sk_content-structure">
				<header class="sk_content-structure-header">
					<h3>記事の構造（パンくずリスト）</h3>
					<p id="sk-breadcrumb-preview" class="sk_content-structure-breadcrumb"></p>
					<p><button class="button" id="sk-breadcrumb-changer">変更する</button></p>
				</header>
				<div class="sk_content-structure-body">
					<div class="sk_content-structure-item">
						<label>プライマリ・リーグ</label>
						<?php
						$selected = $this->get_primary_league( $post->ID, 'int' );
						wp_dropdown_categories( [
							'show_option_none' => '指定されていません',
							'taxonomy'         => 'league',
							'hide_if_empty'    => false,
							'hierarchical'     => true,
							'name'             => 'primary-league',
							'selected'         => $selected,
						] );
						?>
					</div>
					<?php
					foreach ( [
						'team'   => 'チーム',
						'player' => '選手',
					] as $key => $label ) :
						$selected = $this->get_primary( $post->ID, $key );
						?>
						<div class="sk_content-structure-item">
							<label for="primary-<?php echo esc_attr( $key ); ?>">プライマリ・<?php echo esc_html( $label ); ?></label>
							<select id="primary-<?php echo esc_attr( $key ); ?>" name="primary-<?php echo esc_attr( $key ); ?>">
								<?php if ( $selected ) : ?>
									<option class="no-primary" value="0">指定しない</option>
									<option selected value="<?php echo esc_attr( $selected->ID ); ?>"><?php echo esc_html( $selected->post_title ) ?></option>
								<?php else : ?>
									<option class="no-primary" selected value="0">指定しない</option>
								<?php endif; ?>
							</select>
						</div>
					<?php endforeach; ?>
				</div>
			</div><!-- .sk_content-structure -->
			<?php
			wp_nonce_field( 'update_relation', '_newsrelationnonce', false );
		}
	}

	/**
	 * プレイヤーを取得する
	 *
	 * @param int    $post_id   Post ID
	 * @param string $post_type player or team
	 * @param string $format    Default object.
	 *
	 * @return \WP_Post|int|null
	 */
	public function get_primary( $post_id, $post_type, $format = 'OBJECT' ) {
		$primary_id = get_post_meta( $post_id, '_primary_' . $post_type, true );
		if ( ! $primary_id ) {
			return ( OBJECT === $format ) ? null : 0;
		}
		$primary = get_post( $primary_id );
		if ( ! $primary || $post_type !== $primary->post_type ) {
			return ( OBJECT === $format ) ? null : 0;
		}
		return ( OBJECT === $format ) ? $primary : $primary->ID;
	}

	/**
	 * 投稿にプライマリーリーグが設定されているか
	 *
	 * @param int    $post_id 投稿ID
	 * @param string $foramt object, or int
	 * @return \WP_Term|null|int
	 */
	public function get_primary_league( $post_id, $format = 'OBJECT' ) {
		$term_id = get_post_meta( $post_id, '_primary_league', true );
		if ( ! $term_id ) {
			return ( OBJECT === $format ) ? null : 0;
		}
		$term = get_term_by( 'id', $term_id, 'league' );
		if ( ! $term || is_wp_error( $term ) ) {
			return ( OBJECT === $format ) ? null : 0;
		}
		return ( OBJECT === $format ) ? $term : $term->term_id;
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
		if ( $this->input->verify_nonce( 'update_relation', '_newsrelationnonce' ) ) {
			update_post_meta($post_id, 'sk_relation_is_disable', $this->input->post('sk_relation_is_disable') );
			update_post_meta($post_id, 'sk_trs_relation_is_disable', $this->input->post('sk_trs_relation_is_disable') );
			// 選手とチームを登録
			foreach ( $this->types as $type => $label ) {
				$object_ids = array_filter( explode( ',', $this->input->post( 'sk_relation_' . $type ) ), function ( $var ) {
					return is_numeric( $var );
				} );
				$this->relation->set_relation( $type, $post_id, $object_ids );
			}
			// 試合を登録
			$match_id = $this->input->post('match-rel-id');
			if( 'del' === $this->input->post('match-should-delete') ){
				// 削除
				$this->matches->delete_post($post_id);
			}elseif( 'new' === $match_id ){
				// 新規作成前に事前のデータを削除
				$this->matches->delete_post($post_id);
				// 新規追加
				$this->matches->add_rel(
					$this->input->post('match-abroad'),
					$this->input->post('match-rel-type'),
					$post_id,
					$this->input->post('match-block-id')
				);
			}else{
				// 更新
				$this->matches->modify($match_id, $this->input->post('match-abroad'), $this->input->post('match-rel-type'), $this->input->post('match-block-id'));
			}
			// プライマリーリーグを登録
			foreach ( [ 'player', 'league', 'team' ] as $key ) {
				update_post_meta( $post_id, '_primary_' . $key, filter_input( INPUT_POST, 'primary-' . $key ) );
			}
		}
	}

	/**
	 * パンクズを変更する
	 *
	 * @param \bcn_breadcrumb_trail $bcn
	 * @return void
	 */
	public function bcn_primary( \bcn_breadcrumb_trail $bcn ) {
		if ( ! is_singular( 'post' ) ) {
			// ニュースページ以外では何もしない
			return;
		}
		$trails = $this->get_breadcumbs( get_queried_object() );
		if ( empty( $trails ) ) {
			// 設定されていない。
			return;
		}
		$new_trails = [
			$bcn->trail[ count( $bcn->trail ) - 1 ],
		];
		foreach ( $trails as $trail ) {
			$type = [ 'item-' . $trail['type'] ];
			if ( $trail['current'] ) {
				$type[] = 'current-item';
			}
			array_unshift( $new_trails, new \bcn_breadcrumb( $trail['label'], null, $type, $trail['url'], $trail['type'], ! $trail['current'] ) );
		}
		$bcn->trail = $new_trails;
	}

	/**
	 * パンクズで指定されたものがある場合は入力する。
	 *
	 * @param int|null|\WP_Post $post 現在の投稿
	 * @return array[]
	 */
	public function get_breadcumbs( $post = null ) {
		$trails = [];
		$post = get_post( $post );
		if ( ! $post || 'post' !== $post->post_type ) {
			return $trails;
		}
		$league = $this->get_primary_league( $post->ID );
		if ( $league ) {
			$trails[] = [
				'label'   => $league->name,
				'url'     => geT_term_link( $league ),
				'type'    => 'league',
				'current' => false,
			];
		}
		foreach ( [ 'team', 'player' ] as $type ) {
			$primary = $this->get_primary( $post->ID, $type );
			if ( $primary ) {
				$trails[] = [
					'label'   => get_the_title( $primary ),
					'url'     => get_permalink( $primary ),
					'current' => false,
					'type'    => $type,
				];
			}
		}
		if ( empty( $trails ) ) {
			return $trails;
		}
		$trails[] = [
			'label'   => get_the_title( $post ),
			'url'     => get_permalink( $post ),
			'current' => true,
			'type'    => 'post',
		];
		return $trails;
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
			case 'relation':
				return ObjectRelationships::instance();
				break;
			case 'matches':
				return MatchRelationships::instance();
				break;
			default:
				return null;
				break;
		}
	}

}
