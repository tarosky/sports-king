<?php

namespace Tarosky\Common\Hooks;

use Tarosky\Common\Pattern\HookPattern;

/**
 * 投稿タイプ「選手」を登録するフック
 */
class PlayerRegisterHooks extends HookPattern {

	/**
	 * @var string $post_type 投稿タイプ名
	 */
	protected $post_type = 'player';

	/**
	 * {@inheritDoc}
	 */
	protected function register_hooks(): void {
		add_action( 'init', [ $this, 'register_player' ] );
		add_action( 'edit_form_after_title', [ $this, 'add_team_selector' ] );
		add_filter( 'query_vars', [ $this, 'add_query_vars' ] );
		add_filter( "manage_{$this->post_type}_posts_columns", [ $this, 'admin_column_header' ] );
		add_action( "manage_{$this->post_type}_posts_custom_column", [ $this, 'admin_column_content' ], 10, 2 );
		add_action( 'pre_get_posts', [ $this, 'pre_get_posts' ] );
	}

	/**
	 * 投稿タイプ「選手」を登録
	 *
	 * @return void
	 */
	public function register_player() {
		$args = apply_filters( 'sk_post_type_default_args', [
			'label'           => 'プレーヤー',
			'labels'          => [
				'name'               => 'プレーヤー',
				'singular_name'      => 'プレーヤー',
				'add_new'            => 'プレーヤーを追加',
				'add_new_item'       => 'プレーヤーを追加',
				'edit_item'          => 'プレーヤーを編集',
				'new_item'           => '新しい更新履歴',
				'view_item'          => 'プレーヤーを編集',
				'search_items'       => 'プレーヤーを探す',
				'not_found'          => 'プレーヤーはありません',
				'not_found_in_trash' => 'ゴミ箱にプレーヤーはありません',
				'parent_item_colon'  => '',
			],
			'public'          => true,
			'rewrite'         => [
				'slug'       => 'player',
				'with_front' => false,
			],
			'capability_type' => 'post',
			'menu_position'   => 10,
			'menu_icon'       => 'dashicons-id-alt',
			'has_archive'     => true,
			'taxonomies'      => [ 'post_tag' ],
			'supports'        => [ 'title', 'editor', 'author', 'thumbnail' ],
		], $this->post_type );
		register_post_type( $this->post_type, $args );
	}

	/**
	 * プレイヤーの所属チームを選ぶプルダウンを追加
	 *
	 * @param \WP_Post $post 投稿オブジェクト
	 * @return void
	 */
	public function add_team_selector( $post ) {
		if ( $this->post_type !== $post->post_type ) {
			return;
		}
		// 代表を除外
		// アルゴリズムが変更したら変わる
		$query_args = apply_filters( 'sk_team_pulldown_query_args', [
			'post_type'      => 'team',
			'posts_per_page' => - 1,
			'no_found_rows'  => true,
			'orderby'        => [ 'title' => 'ASC' ],
			'tax_query'      => [
				[
					'taxonomy' => 'league',
					'terms'    => [ '代表' ],
					'field'    => 'name',
					'operator' => 'NOT IN',
				],
			],
		] );
		$teams   = new \WP_Query( $query_args );
		$options = [];
		foreach ( $teams->posts as $team ) {
			$league      = get_the_terms( $team, 'league' );
			$league_name = '不明なグループ';
			if ( $league && ! is_wp_error( $league ) ) {
				foreach ( $league as $l ) {
					$league_name = $l->name;
				}
			}
			if ( ! isset( $options[ $league_name ] ) ) {
				$options[ $league_name ] = [];
			}
			$options[ $league_name ][ $team->ID ] = $team->post_title;
		}
		?>
		<table class="form-table">
			<tr>
				<th><label for="sk-team-id">所属チーム</label></th>
				<td>
					<select name="post_parent" id="sk-team-id">
						<option value="0" <?php selected( ! $post->post_parent ) ?>>所属なし</option>
						<?php foreach ( $options as $label => $opts ) : ?>
							<optgroup label="<?php echo esc_attr( $label ) ?>">
								<?php foreach ( $opts as $id => $opt ) : ?>
									<option
										value="<?php echo esc_attr( $id ) ?>" <?php selected( $id, (int) $post->post_parent ) ?>>
										<?php echo esc_html( $opt ) ?>
									</option>
								<?php endforeach; ?>
							</optgroup>
						<?php endforeach; ?>
					</select>
				</td>
			</tr>
		</table>
		<?php
	}

	/**
	 * クエリバーにチームを追加できるようにする
	 *
	 * @param string[] $vars クエリバー
	 * @return string[]
	 */
	public function add_query_vars( $vars ) {
		$vars[] = 'team_id';
		return $vars;
	}

	/**
	 * チームIDで絞り込めるようにする
	 *
	 * @param \WP_Query $wp_query クエリオブジェクト
	 * @return void
	 */
	public function pre_get_posts( &$wp_query ) {
		$team_id = $wp_query->get( 'team_id' );
		if ( ! $team_id ) {
			return;
		}
		if ( \Tarosky\Common\Models\Players::instance()->is_national_team( $team_id ) ) {
			$meta_query = (array) $wp_query->get( 'meta_query' );
			$meta_query = array_values( array_filter( $meta_query ) );
			$wp_query->set( 'meta_query', array_merge( $meta_query, [
				[
					'key'   => apply_filters( 'sk_national_team_query_key', '_player_international_team' ),
					'value' => $team_id,
				],
			] ) );
		} else {
			$wp_query->set( 'post_parent', $team_id );
		}
	}

	/**
	 * カラムにチームを追加
	 *
	 * @param string[] $columns カラム名
	 * @return string[]
	 */
	public function admin_column_header( $columns ) {
		$new_column = [];
		foreach ( $columns as $key => $name ) {
			$new_column[ $key ] = $name;
			if ( 'title' === $key ) {
				$new_column['team'] = 'チーム';
				$new_column['national'] = '代表';
			}
		}

		return $new_column;
	}

	/**
	 * カラムにチームを出力
	 *
	 * @param string $column
	 * @param int    $post_id
	 *
	 * @return void
	 */
	public function admin_column_content( $column, $post_id ) {
		switch ( $column ) {
			case 'team':
				$post   = get_post( $post_id );
				$parent = get_post( $post->post_parent );
				if ( ! $post->post_parent || ! $parent ) {
					echo '<span style="color: red;">割り当てなし</span>';
				} else {
					printf( '<a href="%s">%s</a>', admin_url( "edit.php?post_type=player&team_id={$parent->ID}" ), get_the_title( $parent ) );
				}
				break;
			case 'national':
				$team = get_post( get_post_meta( $post_id, '_player_international_team', true ) );
				if ( $team && 'team' === $team->post_type ) {
					printf( '<a href="%s">%s</a>', admin_url( "edit.php?post_type=player&team_id={$team->ID}" ), get_the_title( $team ) );
				} else {
					echo '<span style="color: lightgrey">-</span>';
				}
				break;
			default:
				// Do nothing
				break;
		}
	}
}
