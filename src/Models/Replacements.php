<?php

namespace Tarosky\Common\Models;


use Tarosky\Common\Pattern\Model;

/**
 * 置換情報データを司る
 *
 * @package Tarosky\Common\Models
 */
class Replacements extends Model {

	protected $version = '1.0';

	protected $name = 'sk_replacements';

	protected $primary_key = 'id';

	protected $default_placeholder = [
		'id'       => '%d',
		'type'     => '%s',
		'orig'     => '%s',
		'replaced' => '%s',
		'created'  => '%s',
		'updated'  => '%s',
	];

	protected $created_column = 'created';

	protected $updated_column = 'updated';

	/**
	 * @var array 種別名
	 */
	public $types = [
		'player'  => '人名',
		'stadium' => 'スタジアム',
		'team'    => 'チーム名',
	    'team_short' => 'チーム略称',
	];

	protected function build_query() {
		return <<<SQL
			CREATE TABLE `{$this->table}` (
				`id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
				`type` VARCHAR (48) NOT NULL,
				`orig` TEXT NOT NULL,
				`replaced` TEXT NOT NULL,
				`created` DATETIME NOT NULL,
				`updated` DATETIME NOT NULL,
				PRIMARY KEY (`id`),
				INDEX type_from (`type`, `orig`(6))
			) ENGINE=InnoDB DEFAULT CHARSET=utf8
SQL;
	}

	/**
	 * 短縮名が存在するかチェック
	 *
	 * @param string $name
	 *
	 * @return null|\stdClass
	 */
	public function short_name( $name ) {
		$query = <<<SQL
			SELECT * FROM {$this->table}
			WHERE type = 'team_short'
			  AND orig = %s
SQL;
		return $this->get_row( $query, $name );
	}

	/**
	 * 短縮名を更新する
	 *
	 * @param string $orig
	 * @param string $new
	 *
	 * @return bool
	 */
	public function update_short_name( $orig, $new ) {
		$exists = $this->short_name( $orig );
		if ( $exists ) {
			return (bool) $this->update( [
				'replaced' => $new,
			], [
				'id' => $exists->id,
			]);
		} else {
			return (bool) $this->insert( [
				'type' => 'team_short',
			    'orig' => $orig,
			    'replaced' => $new,
			] );
		}
	}

	/**
	 * 置換表を挿入する
	 *
	 * @param string $type
	 * @param string $from
	 * @param string $to
	 *
	 * @return false|int
	 */
	public function create( $type, $from, $to ) {
		return $this->insert( [
			'type'     => $type,
			'orig'     => $from,
			'replaced' => $to,
		] );
	}

	/**
	 * 置換表を更新する
	 *
	 * @param int $id
	 * @param string $from
	 * @param string $to
	 *
	 * @return false|int
	 */
	public function change( $id, $from, $to ) {
		return $this->update( [
			'orig'     => $from,
			'replaced' => $to,
		], [
			'id' => $id
		] );
	}

	/**
	 * 置換を削除する
	 *
	 * @param int $id
	 *
	 * @return false|int
	 */
	public function remove( $id ) {
		return $this->delete( [
			'id' => $id,
		] );
	}

	/**
	 * 名前が存在すれば返す
	 *
	 * @param string $type
	 * @param string $name
	 *
	 * @return string
	 */
	public function normalize( $type, $name ) {
		$query  = <<<SQL
		SELECT replaced FROM {$this->table}
		WHERE type = %s AND orig = %s
SQL;
		$exists = $this->get_var( $query, $type, $name );

		return $exists ?: $name;
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
	public function override( $type, $from, $to ) {
		$query  = <<<SQL
			SELECT id FROM {$this->table}
			WHERE type = %s
			  AND orig = %s
SQL;
		$exists = (int) $this->get_var( $query, $type, $from );
		if ( $exists ) {
			return $this->change( $exists, $from, $to );
		} else {
			return $this->create( $type, $from, $to );
		}
	}

	/**
	 *
	 *
	 * @param string $type
	 * @param string $search_query
	 * @param int $offset
	 * @param int $limit
	 *
	 * @return array
	 */
	public function get_item( $type = '', $search_query = '', $offset = 0, $limit = 20 ) {
		$wheres = [];
		if ( $type ) {
			$wheres[] = $this->db->prepare( '( type = %s )', $type );
		} else {
			$wheres[] = $this->db->prepare( '( type != %s )', $type );
		}
		if ( $search_query ) {
			foreach ( preg_split( '/( |　)/', $search_query ) as $term ) {
				$wheres[] = $this->db->prepare( '( orig LIKE %s )', "%$term%" );
			}
		}
		$wheres = $wheres ? 'WHERE ' . implode( ' AND ', $wheres ) : '';
		$offset = intval( $offset );
		$limit  = intval( $limit );
		$query  = <<<SQL
			SELECT SQL_CALC_FOUND_ROWS * FROM {$this->table}
			{$wheres}
			ORDER BY orig DESC
			LIMIT {$offset}, {$limit}
SQL;

		return array_map( function ( $row ) {
			if ( isset( $this->types[ $row->type ] ) ) {
				$row->label = $this->types[ $row->type ];
			} else {
				$row->label = $row->type;
			}

			return $row;
		}, $this->results( $query ) );
	}
}
