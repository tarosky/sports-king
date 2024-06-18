<?php

namespace Tarosky\Common\Models;


use Tarosky\Common\Pattern\Model;

class EuropeGames extends Model {

	protected $version = '1.0';

	protected $name = 'nk2';

	protected $prefix = 'm_';

	protected $primary_key = 'block_id';

	protected $updated_column = 'modified';

	protected $default_placeholder = [
		'block_id'  => '%s',
		'league_id' => '%s',
		'stadium'   => '%s',
		'ymd'       => '%s',
		'date'      => '%s',
		'state'     => '%s',
		'h_team'    => '%s',
		'a_team'    => '%s',
		'h_score'   => '%s',
		'a_score'   => '%s',
		'file_name' => '%s',
		'modified'  => '%s',
	];

	/**
	 * データが存在するか
	 *
	 * @param string $block_id
	 *
	 * @return bool
	 */
	public function exists( $block_id ) {
		$query = <<<SQL
			SELECT block_id FROM {$this->table}
			WHERE block_id = %s
SQL;

		return (bool) $this->get_var( $query, $block_id );
	}

	/**
	 * 試合を取得する
	 *
	 * @param string $block_id
	 *
	 * @return null|\stdClass
	 */
	public function get_match( $block_id ) {
		$query = <<<SQL
			SELECT * FROM {$this->table}
			WHERE block_id = %s
SQL;

		return $this->get_row( $query, $block_id );
	}

	/**
	 * 新規に試合日程を追加する
	 *
	 * @param string g$block_id
	 * @param string g$league_id
	 * @param string $stadium
	 * @param \DateTime $date
	 * @param string $state
	 * @param string $home
	 * @param string $away
	 * @param string $home_score
	 * @param string $away_score
	 * @param string $file_name
	 *
	 * @return false|int
	 */
	public function create( $block_id, $league_id, $stadium, \DateTime $date, $state, $home, $away, $home_score, $away_score, $file_name ) {
		return $this->insert( [
			'block_id'  => $block_id,
			'league_id' => $league_id,
			'stadium'   => $stadium,
			'ymd'       => $date->format( 'Ymd' ),
			'date'      => $date->format( 'Y-m-d H:i:s' ),
			'state'     => $state,
			'h_team'    => $home,
			'a_team'    => $away,
			'h_score'   => $home_score,
			'a_score'   => $away_score,
			'file_name' => $file_name,
		] );
	}

	/**
	 * データを更新する
	 *
	 * @param array $values
	 * @param array $where
	 *
	 * @return false|int
	 */
	public function modify( $values, $where ) {
		return $this->update( $values, $where );
	}

	/**
	 * 試合を検索する
	 *
	 * @param string $query
	 * @param string $year
	 * @param string $month
	 * @param string $day
	 *
	 * @return array
	 */
	public function search( $query = '', $year = '', $month = '', $day = '' ) {
		$wheres = [];
		// クエリを指定する
		if ( $query ) {
			$wheres[] = '(' . implode( ' AND ', array_map( function ( $q ) {
					return $this->db->prepare( '( ( h_team LIKE %s ) OR (a_team LIKE %s) )', "%{$q}%", "%{$q}%" );
				}, preg_split( '/[ |　]/', $query ) ) ) . ')';
		}
		// 年月日を作成
		if ( $year && $month && $day ) {
			$wheres[] = sprintf( '( EXTRACT(DATE FROM `date`) = \'%04d-%02d-%02d\' )', $year, $month, $day );
		} elseif ( $year && $month ) {
			$wheres[] = sprintf( '( EXTRACT(YEAR_MONTH FROM `date`) = \'%04d%02d\' )', $year, $month );
		} elseif ( $year ) {
			$wheres[] = sprintf( '(EXTRACT(YEAR FROM `date`) = \'%04d\'', $year );
		}
		// 条件が存在しなければ検索しない
		if ( empty( $wheres ) ) {
			return [];
		}
		// クエリを作成
		$wheres = 'WHERE ' . implode( ' AND ', $wheres );
		$query  = <<<SQL
			SELECT * FROM {$this->table}
			{$wheres}
			ORDER BY date DESC
			LIMIT 10;
SQL;

		return $this->get_results( $query );
	}


}
