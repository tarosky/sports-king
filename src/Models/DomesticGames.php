<?php

namespace Tarosky\Common\Models;


use Tarosky\Common\Pattern\Model;

class DomesticGames extends Model {

	protected $version = '1.0';

	protected $name = 'nk';

	protected $prefix = 'm_';

	protected $primary_key = 'id';

	protected $updated_column = 'modified';

	protected $default_placeholder = [
		'id' => '%s',
	    'league_id' => '%s',
	    'stadium' => '%s',
	    'stage' => '%d',
	    'reg' => '%s',
	    'round' => '%s',
	    'date' => '%s',
	    'ymd' => '%s',
	    'season' => '%s',
	    'status' => '%d',
	    'h_team' => '%s',
	    'a_team' => '%s',
	    'h_score' => '%d',
	    'a_score' => '%d',
	    'gallery_id' => '%d',
	    'modified' => '%s',
	];



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
			WHERE id = %s
SQL;

		return $this->get_row( $query, $block_id );
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
	public function search( $query = '', $year = '', $month = '', $day = '' ){
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
