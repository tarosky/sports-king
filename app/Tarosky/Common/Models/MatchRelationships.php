<?php

namespace Tarosky\Common\Models;


use Tarosky\Common\Pattern\Model;

/**
 * 投稿と試合の関係を司るクラス
 *
 * @package Tarosky\Common\Models
 * @property-read Matches $matches
 */
class MatchRelationships extends Model {

	protected $version = '1.0.0';

	protected $name = 'sk_match_relationships';

	protected $primary_key = 'id';

	protected $updated_column = 'updated';

	protected $default_placeholder = [
		'id'       => '%d',
		'rel_type' => '%s',
		'abroad'   => '%d',
		'post_id'  => '%d',
		'match_id' => '%s',
		'updated'  => '%s',
	];

	/**
	 * フックを登録
	 */
	protected function hooks() {
		add_action( 'delete_post', [ $this, 'delete_post' ] );
	}

	/**
	 * Create DB.
	 *
	 * @return string
	 */
	protected function build_query() {
		$encoding = 'utf8';

		return <<<SQL
		CREATE TABLE {$this->table}(
			`id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			`abroad` TINYINT NOT NULL DEFAULT 0,
			`rel_type` VARCHAR(48) NOT NULL,
			`post_id` BIGINT UNSIGNED NOT NULL,
			`match_id` VARCHAR(128) NOT NULL,
			`updated` DATETIME NOT NULL,
			PRIMARY KEY (id),
			INDEX by_post (post_id, rel_type),
			INDEX by_match (match_id)
		) ENGINE=InnoDB DEFAULT CHARSET=utf8
SQL;
	}

	/**
	 * 投稿が削除されたらひも付けも消す
	 *
	 * @param $post_id
	 */
	public function delete_post( $post_id ) {
		$exists = <<<SQL
			SELECT COUNT(id) FROM {$this->table}
			WHERE post_id = %d
SQL;
		if ( $this->get_var( $exists, $post_id ) ) {
			$this->delete( [
				'post_id' => $post_id,
			] );
		}
	}

	/**
	 * 関連している試合を取得する
	 *
	 * @param int $post_id
	 *
	 * @return array|null|\stdClass
	 */
	public function get_match( $post_id ) {
		$query  = <<<SQL
			SELECT * FROM {$this->table}
			WHERE post_id = %d
			ORDER BY updated DESC
SQL;
		$result = $this->get_row( $query, $post_id );
		if ( ! $result ) {
			return null;
		}
		$return = [
			'id'     => $result->id,
			'type'   => $result->rel_type,
			'abroad' => $result->abroad,
			'league' => '',
			'home'   => '',
			'away'   => '',
			'date'   => '',
			'status' => '',
			'label'  => '試合データ破損',
		];
		if ( ! $match = $this->matches->get_match( $result->abroad, $result->match_id ) ) {
			return null;
		}

		list( $home, $away ) = sk_match_teams( $match );
		$home_title = $home ? $home->post_title : '未登録';
		$away_title = $away ? $away->post_title : '未登録';

		return array_merge( $return, [
			'match_id' => $match->game_id,
			'home'     => $home_title,
			'away'     => $away_title,
			'date'     => $match->game_date,
			'status'   => $this->matches->convert_match_status( $match->status_id ),
			'league'   => $match->league_id,
			'label'    => sprintf( '%s VS %s (%s)', $home_title, $away_title, mysql2date( 'Y.m.d', $match->game_date ) ),
		] );
	}

	/**
	 * 関係する投稿を取得する
	 *
	 * @param string $match_id
	 * @param int    $abroad 海外なら1, 国内なら0
	 *
	 * @return array
	 */
	public function get_posts( $match_id, $abroad = 0, $exclude_types = [] ) {
		$query = <<<SQL
			SELECT p.*, r.rel_type FROM {$this->table} AS r
			INNER JOIN {$this->db->posts} AS p
			ON r.post_id = p.ID
			WHERE r.match_id = %s
			  AND r.abroad = %d
			  AND p.post_status = 'publish'
SQL;
		if( $exclude_types ) {
			$set = '';
			$query .= ' AND p.post_type NOT IN( ';
			foreach( $exclude_types as $key => $exclude ){
				$deli = '';
				if( $key ) {
					$deli = ', ';
				}
				$query .= <<<SQL
					'{$exclude}{$deli}'
SQL;
			}
			$query .= ')';
		}
		$query .= <<<SQL
		    GROUP BY p.ID
		    ORDER BY p.post_date DESC
SQL;
		return array_map( function($p) {
			return new \WP_Post( $p );
		}, $this->get_results( $query, $match_id, $abroad ) );

	}

	/**
	 * 関係を追加する
	 *
	 * @param bool $abroad
	 * @param string $type
	 * @param int $post_id
	 * @param string $match_id
	 *
	 * @return int
	 */
	public function add_rel( $abroad, $type, $post_id, $match_id ) {
		return (int) $this->insert( [
			'rel_type' => $type,
			'abroad'   => $abroad,
			'post_id'  => $post_id,
			'match_id' => $match_id,
			'updated'  => current_time( 'mysql' ),
		] );
	}

	/**
	 * 修正する
	 *
	 * @param int $rel_id
	 * @param bool $abroad
	 * @param string $type
	 * @param string $match_id
	 *
	 * @return int
	 */
	public function modify( $rel_id, $abroad, $type, $match_id ) {
		return (int) $this->update( [
			'rel_type' => $type,
			'abroad'   => $abroad,
			'match_id' => $match_id,
		], [
			'id' => $rel_id,
		] );
	}

	/**
	 * 検索をするラッパー
	 *
	 * @param bool $abroad
	 * @param string $query
	 * @param string $year
	 * @param string $month
	 * @param string $day
	 *
	 * @return array
	 */
	public function search( $abroad = true, $query = '', $year = '', $month = '', $day = '', $league = '', $per_page = 10 ) {
		return array_map( function ( $row ) {
		    $sprintf = ['%s VS %s %s(%s)'];
		    foreach ( [ 'h_team', 'a_team' ] as $key ) {
		        if ( $row->{$key} ) {
		            $sprintf[] = $row->{$key}->post_title;
                } else {
		            $sprintf[] = '未登録';
                }
            }
		    $ab = ! empty( $row->abroad ) ? $row->abroad : 0 ;
            $l_id = ! empty( $row->league_id ) ? $row->league_id : 0 ;
			$league = \Tarosky\Common\Statics\Leagues::get_league($ab, $l_id);
			$sprintf[] = $league ? '['.$league['label'].']' : '' ;
            $sprintf[] = mysql2date( 'Y.m.d', $row->game_date ) ;
			return [
				'id'     => $row->game_id,
				'abroad' => 0,
				'label'  => call_user_func_array( 'sprintf', $sprintf ),
				'status' => $row->status,
				'date'   => $row->game_date,
			];
		}, $this->matches->search( $query, $abroad, $league, $year, $month, $day, false, '', null, $per_page ) );
	}

	/**
	 * 関連している投稿を取得する
	 *
	 * @param string $type
	 * @param int $post_id
	 *
	 * @return array
	 */
	public function get_relation( $type, $post_id ) {
		$query = <<<SQL
			SELECT p.* FROM {$this->table} AS r
			INNER JOIN {$this->db->posts} AS p
			ON p.ID = r.object_id
			WHERE r.type = %s
			  AND r.subject_id = %d
			ORDER BY p.post_title ASC
SQL;

		return array_map( function ( $row ) {
			return new \WP_Post( $row );
		}, $this->results( $query, $type, $post_id ) );
	}

	/**
	 * Get posts of same match
	 *
	 * @param int $post_id
	 *
	 * @return array
	 */
	public function get_same_match ( $post_id ) {
		$match = $this->get_match( $post_id );
		if ( ! $match ) {
			return [];
		}
		return array_filter( $this->get_posts( $match['match_id'], $match['abroad'], ['logbook'] ), function($post) use ($post_id) {
			return $post->ID != $post_id;
		} );
	}

	/**
	 * Getter
	 *
	 * @param string $name
	 *
	 * @return mixed|static
	 */
	public function __get( $name ) {
		switch ( $name ) {
			case 'matches':
				return Matches::instance();
				break;
			default:
				return parent::__get( $name );
				break;
		}
	}

}
