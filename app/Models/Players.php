<?php

namespace Tarosky\Common\Models;


use Tarosky\Common\Pattern\Model;

/**
 * 旧プレイヤーデータを扱う
 */
class Players extends Model {


	/**
	 * Prefix
	 * @var string
	 */
	protected $prefix = 'm_';

	/**
	 * Primary key
	 * @var string
	 */
	protected $primary_key = 'id';

	/**
	 * Place holder
	 * @var array
	 */
	protected $default_placeholder = [
		'id'       => '%d',
		'code'     => '%s',
		'type'     => '%d', // 表示フラグ1は国内、2は海外
		'name_ja'  => '%s',
		'name_en'  => '%s',
		'club'     => '%s', // 文字（名寄せ必須？）
		'from'     => '%s', // 出身地
		'image'    => '%d', // S3にある画像URL
		'tag'      => '%s', // タグ？
		'created'  => '%s',
		'modified' => '%s',
		'data'     => '%s', // その他データ（JSON）
	];

	/**
	 * Column to be created.
	 *
	 * @var string
	 */
	protected $created_column = 'created';

	/**
	 * Column to be updated.
	 *
	 * @var string
	 */
	protected $updated_column = 'modified';

	/**
	 * 選手名鑑のデータを取得する
	 *
	 * @param int $limit
	 *
	 * @return array
	 */
	public function retrieve( $limit, $offset = 0 ) {
		$query   = <<<SQL
			SELECT * FROM {$this->table}
			ORDER BY id ASC
			LIMIT %d, %d
SQL;
		$results = [];
		foreach ( $this->results( $query, $offset, $limit ) as $player ) {
			$data = json_decode( $player->data, true );
			foreach ( $this->default_placeholder as $key => $pl ) {
				if ( 'data' !== $key ) {
					$data[ $key ] = $player->{$key};
				}
			}
			$results[] = $data;
		}

		return $results;
	}

	/**
	 * プレイヤーを取得する
	 *
	 * @param array $names
	 *
	 * @return array
	 */
	public function get_players( $names = [] ) {
		if ( ! $names ) {
			return [];
		}
		$in_terms = implode( ', ', array_map( function($name){
			return $this->db->prepare('%s', $name);
		}, $names ) );
		$query = <<<SQL
			SELECT p.*, ( count(p.ID) - 1 ) AS dupclidated FROM {$this->db->posts} AS p
			WHERE post_type = 'player'
			  AND post_status = 'publish'
			  AND post_title IN ({$in_terms})
			GROUP BY post_title
SQL;
		return $this->get_results( $query );
	}

	/**
	 * チームが代表に属しているか否か
	 *
	 * @param int $team_id
	 *
	 * @return bool
	 */
	public function is_national_team( $team_id ) {
		$term_ids = [];
		$root = get_term_by( 'name', '代表', 'league' );
		if ( $root ) {
			$term_ids[] = $root->term_id;
			$children = get_terms( 'league', [
				'hide_empty' => false,
				'parent' => $root->term_id,
			] );
			if ( $children && ! is_wp_error( $children ) ) {
				foreach ( $children as $child ) {
					$term_ids[] = $child->term_id;
				}
			}
		}
		if ( ! $term_ids ) {
			return false;
		} else {
			return has_term( $term_ids, 'league', $team_id );
		}
	}

	/**
	 * チームメンバーをポジションの配列で返す
	 *
	 * @param null|int|\WP_Post $team
	 *
	 * @return array
	 */
	public function get_team_members( $team = null ) {
		$team = get_post( $team );
		$args = [
			'post_type'      => 'player',
			'post_status'    => 'publish',
			'posts_per_page' => - 1,
			'orderby'        => [
				'name' => 'ASC',
			],
		];
		if ( $this->is_national_team( get_the_ID() ) ) {
			$args['meta_query'] = [
				[
					'key' => '_player_international_team',
				    'value' => $team->ID,
				],
			];
		} else {
			$args['post_parent'] = get_the_ID();
		}
		$members = [];
		//	GK DF MF FWの順番にするため先に空配列を作っておく
		$members['GK'] = [];
		$members['DF'] = [];
		$members['MF'] = [];
		$members['FW'] = [];
		foreach ( get_posts( $args ) as $player ) {
			$position = get_post_meta( $player->ID, '_player_position', true );
			if ( ! $position ) {
				continue;
			}
			if ( ! isset( $members[ $position ] ) ) {
				$members[ $position ] = [];
			}
			$members[ $position ][] = $player;
		}
		return $members;
	}

	/**
	 * チームに登録されているプレイヤーの数
	 *
	 * @param int $team_id
	 *
	 * @return int
	 */
	public function get_player_count( $team_id ) {
		// 代表かそうでないかでアルゴリズムを変更
		$args = [
			'post_type'      => 'player',
			'post_status'    => 'any',
			'posts_per_page' => 1,
		];
		if ( $this->is_national_team( $team_id ) ) {
			$args['meta_query'] = [
				[
					'key'   => apply_filters( 'sk_national_team_query_key', '_player_international_team' ),
					'value' => $team_id,
				],
			];
		} else {
			$args['post_parent'] = $team_id;
		}
		$query = new \WP_Query( $args );
		return $query->found_posts;
	}

	/**
	 * チームに所属する選手を表示する
	 *
	 * @param int $team_id
	 * @param string $name
	 *
	 * @return null|\WP_Post
	 */
	public function get_player_of( $team_id, $name ) {
		$query = <<<SQL
			SELECT * FROM {$this->db->posts}
			WHERE post_type = 'player'
			  AND post_status = 'publish'
			  AND post_title = %s
			  AND post_parent = %d
SQL;
		$row =  $this->get_row( $query, $name, $team_id );
		return $row ? new \WP_Post( $row ) : null;
	}

	/**
	 * クラブ名を取得する
	 *
	 * @param int $post_id
	 *
	 * @return null|string
	 */
	public function get_old_club( $post_id ) {
		$old_id = get_post_meta( $post_id, '_player_id', true );
		$query = <<<SQL
		SELECT club FROM {$this->table}
		WHERE `id` = %d
SQL;
		return $this->get_var($query, $old_id);
	}

	/**
	 * チームを名前で取得する
	 *
	 * @param string $name
	 *
	 * @return null|\stdClass
	 */
	public function get_team_by_name($name) {
		$query = <<<SQL
		SELECT * FROM {$this->db->posts}
		WHERE post_type = 'team'
		  AND post_status = 'publish'
		  AND post_title = %s
SQL;
		return $this->get_row($query, $name);
	}

	/**
	 * プレイヤーがすでに存在するかどうか
	 *
	 * @param int $id
	 *
	 * @return bool
	 */
	public function exists( $id ) {
		$query = <<<SQL
			SELECT post_id FROM {$this->db->postmeta}
			WHERE meta_key = '_player_id'
			  AND meta_value = %d
SQL;

		return (bool) $this->get_var( $query, $id );
	}

}
