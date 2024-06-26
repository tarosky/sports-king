<?php

namespace Tarosky\Common\Models;


use Tarosky\Common\Pattern\Model;

class TeamMaster extends Model {

	protected $version = '1.0.0';

	protected $primary_key = 'id';

	protected $name = 'sk_team_master';

	protected $updated_column = 'updated';

	protected $default_placeholder = [
		'id'              => '%d',
		'name'            => '%s',
		'name_en'         => '%s',
		'name_kana'       => '%s',
		'name_short'      => '%s',
		'name_short_en'   => '%s',
		'name_short_kana' => '%s',
		'postal'          => '%s',
		'address'         => '%s',
		'tel'             => '%s',
		'stadium'         => '%s',
		'stadium_id'      => '%d',
		'stadium_address' => '%s',
		'color'           => '%s',
		'formation'       => '%s',
		'founded'         => '%d',
		'title'           => '%s',
		'comment'         => '%s',
		'updated'         => '%s',
	];

	/**
	 * テーブルを作成する
	 *
	 * @return string
	 */
	protected function build_query() {
		return <<<SQL
			CREATE TABLE `{$this->table}` (
				`id` BIGINT UNSIGNED NOT NULL,
				`name` VARCHAR(256) NOT NULL,
				`name_en` VARCHAR(256) NOT NULL,
				`name_kana` VARCHAR(256) NOT NULL,
				`name_short` VARCHAR(256) NOT NULL,
				`name_short_en` VARCHAR(256) NOT NULL,
				`name_short_kana` VARCHAR(256) NOT NULL,
				`postal` VARCHAR(11) NOT NULL,
				`address` TEXT NOT NULL,
				`tel` VARCHAR(48) NOT NULL,
				`stadium` VARCHAR(256) NOT NULL,
				`stadium_id` BIGINT UNSIGNED NOT NULL,
				`stadium_address` TEXT NOT NULL,
				`color` VARCHAR(24) NOT NULL,
				`formation` VARCHAR(48) NOT NULL,
				`founded` INT UNSIGNED NOT NULL,
				`title` TEXT NOT NULL,
				`comment` TEXT NOT NULL,
				`updated` DATETIME NOT NULL,
				PRIMARY KEY ( `id` )
			) ENGINE=InnoDB  DEFAULT CHARSET=utf8
SQL;
	}

	/**
	 * データを挿入する
	 *
	 * @param array $data
	 *
	 * @return bool|false|int
	 */
	public function add( $data ) {
		if ( ! isset( $data['id'] ) ) {
			return false;
		}
		if ( $this->exists( $data['id'] ) ) {
			$id = $data['id'];
			unset( $data['id'] );

			return $this->update( $data, [
				'id' => $id,
			] );
		} else {
			return $this->insert( $data );
		}
	}

	/**
	 * チーム名に変換する
	 *
	 * @param int $id
	 *
	 * @return string
	 */
	public function get_name( $id ) {
		$row = $this->get( $id );
		if ( $row ) {
			return Replacements::instance()->normalize( 'team', sk_hankaku( $row->name ) );
		} else {
			return '';
		}
	}

	/**
	 * DSのチームIDからWordPressの投稿を返す
	 *
	 * @param int $id
	 *
	 * @return null|\WP_Post
	 */
	public function get_team( $id ) {
		$name = $this->get_name( $id );
		if ( ! $name ) {
			return null;
		}
		$query = <<<SQL
			SELECT * FROM {$this->db->posts}
			WHERE post_type = 'team'
			  AND post_status = 'publish'
			  AND post_title = %s
SQL;
		$row = $this->get_row( $query, $name );
		return $row ? new \WP_Post( $row ) : null;
	}

	/**
	 * 短い名前を返す
	 *
	 * @param int|string $id_or_name
	 * @param int $abroad 初期値は0（日本）
	 *
	 * @return bool|string
	 */
	public function get_short_name( $id_or_name, $abroad = 0 ) {
		if ( ! $abroad ) {
			if ( $row = $this->get( $id_or_name ) ) {
				return $row->name_short;
			} else {
				return false;
			}
		} else {
			if ( $row = Replacements::instance()->short_name( $id_or_name ) ) {
				return $row->replaced;
			} else {
				return $id_or_name;
			}
		}
	}

	/**
	 * マスターが存在するか否か
	 *
	 * @param int $id
	 *
	 * @return bool
	 */
	public function exists( $id ) {
		return (bool) $this->get( $id );
	}

	/**
	 * 名前を取得する
	 *
	 * @param string $name
	 *
	 * @return null|\stdClass
	 */
	public function get_team_by_name( $name ) {
		$name = Replacements::instance()->normalize( 'team', $name );
		$length = mb_strlen( $name, 'UTF-8' );
		$query = <<<SQL
			SELECT * FROM {$this->db->posts}
			WHERE post_type = 'team'
			  AND post_status = 'publish'
			  AND post_title COLLATE 'utf8mb4_bin' LIKE %s
			ORDER BY ( CHAR_LENGTH( post_title ) - %d ) ASC
SQL;
		return $this->get_row( $query, "%{$name}%", $length );
	}
}
