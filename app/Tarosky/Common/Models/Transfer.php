<?php

namespace Tarosky\Common\Models;


use Tarosky\Common\Pattern\Model;

/**
 * データを取得する
 *
 * @package Tarosky\Common\Models
 */
class Transfer extends Model {

	protected $version = '1.0.1';

	protected $name = 'bk_transfer';

	protected $updated_column = 'modified';

	protected $default_placeholder = array(
		'id'                      => '%d',
		'post_id'                 => '%d',
		'player_id'               => '%d',
		'ymd'                     => '%s',
		'national_year'           => '%d',
		'abroad_year'             => '%d',
		'abroad_season'           => '%d',

		//      'from_abroad'     => '%d',
		//      'from_league_id'  => '%s',
			'from_league_term_id' => '%d',
		'from_league_is_abroad'   => '%d',
		'from_team_id'            => '%d',
		'from_position'           => '%s',
		'from_number'             => '%d',
		'from_employ'             => '%s',
		'from_employ_free'        => '%s',

		//      'to_abroad'     => '%d',
		//      'to_league_id'  => '%s',
			'to_league_term_id'   => '%d',
		'to_league_is_abroad'     => '%d',
		'to_team_id'              => '%d',
		'to_position'             => '%s',
		'to_number'               => '%d',
		'to_employ'               => '%s',
		'to_employ_free'          => '%s',

		'modified'                => '%s',
	);

	/**
	 * データベースを作成する
	 *
	 * @return string
	 */
	protected function build_query() {
		return <<<SQL
		CREATE TABLE `{$this->table}` (
			`id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			`post_id` bigint(20) unsigned NOT NULL,
			`player_id` bigint(20) unsigned NOT NULL,
			`status` varchar(20) NOT NULL DEFAULT 'publish',
			`ymd` varchar(8) NOT NULL,
			`national_year` int(10) unsigned NOT NULL,
			`abroad_year` int(10) unsigned NOT NULL,
			`abroad_season` int(2) unsigned NOT NULL,
			`from_team_id` bigint(64) unsigned NOT NULL,
			`from_league_term_id` int(11) DEFAULT NULL,
			`from_league_is_abroad` tinyint(1) DEFAULT NULL,
			`from_position` varchar(256) NOT NULL DEFAULT '',
			`from_number` bigint(20) unsigned NOT NULL,
			`from_employ` varchar(64) NOT NULL DEFAULT '',
			`from_employ_free` varchar(256) DEFAULT NULL,
			`to_team_id` bigint(20) unsigned NOT NULL,
			`to_league_term_id` int(11) DEFAULT NULL,
			`to_league_is_abroad` tinyint(1) DEFAULT NULL,
			`to_position` varchar(256) NOT NULL DEFAULT '',
			`to_number` bigint(20) unsigned NOT NULL,
			`to_employ` varchar(64) NOT NULL DEFAULT '',
			`to_employ_free` varchar(256) DEFAULT NULL,
			`modified` datetime NOT NULL,
			PRIMARY KEY (`id`),
			KEY `legue` (`from_abroad`,`from_league_id`),
			KEY `by_match` (`from_abroad`),
			KEY `by_date` (`from_abroad`,`from_league_id`)
		) ENGINE=InnoDB DEFAULT CHARSET=utf8
SQL;
	}

	public function exists( $post_id ) {
		$query = <<<SQL
			SELECT id FROM {$this->table}
			WHERE post_id = %s
SQL;

		return (int) $this->get_var( $query, $post_id );
	}

	/**
	 * 上書きする
	 *
	 * @param string $type
	 * @param string $from
	 * @param string $to
	 *
	 * @return false|int
	 */
	public function override( $post_id, $data ) {

		$exists = (int) $this->exists( $post_id );
		if ( ! $exists ) {
			return $this->insert( $data );
		} else {
			return $this->update( $data, array( 'id' => $exists ) );
		}
	}

	/**
	 * 移籍情報を取得
	 *
	 */
	public function get_transfer_info( $args = array() ) {
		if ( ! isset( $args['status'] ) ) {
			$args['status'] = '"publish"';
		}

		$query = <<<SQL
			SELECT * FROM {$this->table}
SQL;
		if ( $args ) {
			$count = 0;
			foreach ( $args as $key => $val ) {
				$junction = 'WHERE';
				$value    = isset( $val['value'] ) ? $val['value'] : $val;
				$compare  = isset( $val['compare'] ) ? $val['compare'] : '=';

				if ( $count ) {
					$junction = 'AND';
				}
				$query .= <<<SQL

					{$junction} {$key} {$compare} {$value}
SQL;
				++$count;
			}
		}

		$query .= <<<SQL
			ORDER BY ymd ASC
SQL;
		return $this->get_results( $query );
	}
}
