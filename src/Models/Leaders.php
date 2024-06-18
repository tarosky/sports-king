<?php

namespace Tarosky\Common\Models;


use Tarosky\Common\Pattern\Model;

class Leaders extends Model {

	protected $version = '1.0.2';

	protected $name = 'bk_leaders';

	protected $updated_column = 'modified';

	protected $default_placeholder = [
		'game_id'         => '%d',
		'team_id'         => '%d',
		'category'        => '%d',
		'period'          => '%d',
		'ranking'         => '%d',
		'no'              => '%d',
		'player'          => '%d',
		'count'           => '%d',
		'offence_rebound' => '%d',
		'defence_rebound' => '%d',
		'modified'        => '%s',
	];

	/**
	 * Create Database
	 *
	 * @return string
	 */
	protected function build_query() {
		return <<<SQL
			CREATE TABLE `{$this->table}` (
				`game_id`         BIGINT UNSIGNED NOT NULL,
				`team_id`         BIGINT UNSIGNED NOT NULL,
				`category`        INT UNSIGNED NOT NULL,
				`period`          INT UNSIGNED NOT NULL,
				`ranking`         INT UNSIGNED NOT NULL,
				`no`              INT UNSIGNED NOT NULL,
				`player`          BIGINT UNSIGNED NOT NULL,
				`count`           INT UNSIGNED NOT NULL,
				`offence_rebound` INT UNSIGNED NOT NULL,
				`defence_rebound` INT UNSIGNED NOT NULL,
				`modified`        DATETIME NOT NULL,
				UNIQUE record_id ( `game_id`, `team_id`, `category`, `period`, `ranking`, `no` ),
				INDEX by_game ( `game_id`, `team_id` )
			) ENGINE=InnoDB DEFAULT CHARSET=utf8
SQL;
	}

	/**
	 * Delete from
	 *
	 * @param int $game_id
	 *
	 * @return false|int
	 */
	public function clear( $game_id ) {
		return $this->delete( [
			'game_id' => $game_id,
		] );
	}

	/**
	 * Add data
	 *
	 * @param array $values
	 *
	 * @return false|int
	 */
	public function add( $values ) {
		$keys = [
			'game_id', 'team_id', 'category', 'period', 'ranking', 'no'
		];
		// Check if record exists
		$wheres = [];
		foreach ( $keys as $key ) {
			$wheres[] = $this->prepare( "`{$key}` = {$this->default_placeholder[$key]}", $values[$key] );
		}
		$wheres = implode( ' AND ', $wheres );
		$query = <<<SQL
			SELECT COUNT(*) FROM {$this->table}
			WHERE {$wheres}
SQL;
		if ( $this->get_var( $query ) ) {
			// Record exists. Update.

		} else {
			// Just add.
			return $this->insert( $values );
		}

	}

	/**
	 * リーダーを取得する
	 *
	 * @param string $game_id
	 *
	 * @return array|null|object
	 */
	public function get_leaders( $game_id ) {
		$query = <<<SQL
			SELECT DISTINCT l.*, pm.post_id AS player_id, l.team_id AS team, pm2.post_id AS team_id
			FROM {$this->table} AS l
			LEFT JOIN {$this->db->postmeta} AS pm
			ON pm.meta_key = '_player_id' AND pm.meta_value = l.player
			LEFT JOIN {$this->db->postmeta} AS pm2
			ON pm2.meta_key = '_team_id' AND pm2.meta_value = l.team_id
			WHERE l.game_id = %s
			  AND l.period = 18
SQL;
		return $this->db->get_results( $this->db->prepare( $query, $game_id ) );
	}


}
