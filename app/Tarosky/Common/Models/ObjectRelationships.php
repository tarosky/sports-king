<?php

namespace Tarosky\Common\Models;


use Tarosky\Common\Pattern\Model;

/**
 * 投稿と投稿の関係を司るクラス
 * @package Tarosky\Common\Models
 */
class ObjectRelationships extends Model {

	protected $version = '1.0.0';

	protected $primary_key = 'id';

	protected $updated_column = 'updated';

	protected $default_placeholder = array(
		'id'         => '%d',
		'subject_id' => '%d',
		'object_id'  => '%d',
		'type'       => '%s',
		'updated'    => '%s',
	);

	/**
	 * フックを登録
	 */
	protected function hooks() {
		// 投稿が削除されたら、関連付けもすべて削除
		add_action( 'delete_post', array( $this, 'delete_post' ) );
		// クエリバーを追加
		add_filter( 'query_vars', array( $this, 'add_query_vars' ) );
		add_filter( 'posts_join', array( $this, 'posts_join' ), 10, 2 );
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
			`type` VARCHAR(48) NOT NULL,
			`subject_id` BIGINT UNSIGNED NOT NULL,
			`object_id` BIGINT UNSIGNED NOT NULL,
			`updated` DATETIME NOT NULL,
			PRIMARY KEY (id),
			INDEX type_subject_object (type, subject_id, object_id),
			UNIQUE (type, object_id, subject_id),
			INDEX type_object (type, object_id),
			INDEX type_date(type, updated)
		) ENGINE=InnoDB DEFAULT CHARSET=utf8
SQL;
	}

	/**
	 * 投稿が削除されたらひも付けも消す
	 *
	 * @param $post_id
	 */
	public function delete_post( $post_id ) {
		foreach ( array( 'object_id', 'subject_id' ) as $column ) {
			$this->delete( array(
				$column => $post_id,
			) );
		}
	}

	/**
	 * Save relation
	 *
	 * @param string $type
	 * @param int $subject_id
	 * @param array $object_ids
	 */
	public function set_relation( $type, $subject_id, $object_ids ) {
		$object_ids = (array) $object_ids;
		// 全部削除
		$this->delete( array(
			'type'       => $type,
			'subject_id' => $subject_id,
		) );
		foreach ( $object_ids as $object_id ) {
			$this->add_rel( $type, $subject_id, $object_id );
		}
	}

	/**
	 * 関係をつける
	 *
	 * @param string $type
	 * @param int $subject_id
	 * @param int $object_id
	 *
	 * @return int
	 */
	public function add_rel( $type, $subject_id, $object_id ) {
		return (int) $this->insert( array(
			'type'       => $type,
			'subject_id' => $subject_id,
			'object_id'  => $object_id,
			'updated'    => current_time( 'mysql' ),
		) );
	}

	/**
	 * 関連している投稿を取得する
	 *
	 * @param string $type
	 * @param int $post_id
	 *
	 * @return array
	 */
	public function get_relation( $type, $post_id, $exclude_types = array() ) {
		$query = <<<SQL
			SELECT p.* FROM {$this->table} AS r
			INNER JOIN {$this->db->posts} AS p
			ON p.ID = r.object_id
			WHERE r.type = %s
			  AND p.post_type = %s
			  AND r.subject_id = %d
SQL;
		if ( $exclude_types ) {
			$set    = '';
			$query .= ' AND p.post_type NOT IN( ';
			foreach ( $exclude_types as $key => $exclude ) {
				$deli = '';
				if ( $key ) {
					$deli = ', ';
				}

				$query .= <<<SQL
					'{$exclude}{$deli}'
SQL;
			}
			$query .= ')';
		}

		$query .= ' ORDER BY p.post_title ASC';

		return array_map( function ( $row ) {
			return new \WP_Post( $row );
		}, $this->results( $query, $type, $type, $post_id ) );
	}
	/**
	 * この投稿を持つニュースを返す
	 *
	 * @param string $rel
	 * @param int    $post_id
	 * @param int    $limit
	 * @param int    $offset
	 *
	 * @return array
	 */
	public function get_siblings( $rel, $post_id, $limit = 10, $offset = 0, $exclude_types = array() ) {
		$placeholders = array( $rel, $post_id );
		$query        = <<<SQL
			SELECT DISTINCT p.* FROM {$this->table} AS r
			INNER JOIN {$this->db->posts} AS p
			ON p.ID = r.subject_id
			WHERE r.type = %s
			  AND r.object_id = %d
			  AND p.post_status = 'publish'
SQL;
		if ( $exclude_types ) {
			$set    = '';
			$query .= ' AND p.post_type NOT IN( ';
			foreach ( $exclude_types as $key => $exclude ) {
				$deli = '';
				if ( $key ) {
					$deli = ', ';
				}

				$query .= <<<SQL
					'{$exclude}{$deli}'
SQL;
			}
			$query .= ')';
		}
		$query .= ' ORDER BY p.post_date DESC';

		if ( $limit ) {
			$query         .= ' LIMIT %d, %d';
			$placeholders[] = $offset;
			$placeholders[] = $limit;
		}
		array_unshift( $placeholders, $query );
		return array_map( function ( $row ) {
			return new \WP_Post( $row );
		}, call_user_func_array( array( $this, 'results' ), $placeholders ) );
	}


	/**
	 * クエリバーを追加。
	 *
	 * @param string[] $vars クエリバー。
	 * @return string[]
	 */
	public function add_query_vars( $vars ) {
		$vars[] = 'related_with';
		$vars[] = 'relation_type';
		return $vars;
	}

	/**
	 * クエリバーが指定されている場合は関連する記事をJOINする。
	 *
	 * @param string    $join  JOIN句。
	 * @param \WP_Query $query クエリオブジェクト。
	 *
	 * @return string
	 */
	public function posts_join( $join, $query ) {
		$with = $query->get( 'related_with' );
		$type = $query->get( 'relation_type' );
		if ( empty( $with ) || empty( $type ) ) {
			// 関連が正しく指定されていなければ何もしない。
			return $join;
		}
		// 複数指定を受け入れるため、配列に変換。
		$with = array_map( function ( $id ) {
			return (int) trim( $id );
		}, explode( ',', $with ) );
		// WHERE句を生成
		$wheres = array(
			$this->db->prepare( 'type=%s', $type ),
		);
		if ( 1 < count( $with ) ) {
			// 複数指定されている場合はIN句を使う。
			$wheres[] = sprintf( 'object_id IN (%s)', implode( ',', $with ) );
		} else {
			// 1つなのでIN句は使わない。
			$wheres[] = $this->db->prepare( 'object_id = %d', $with[0] );
		}
		$wheres = implode( ' AND ', $wheres );
		// JOIN句を生成。
		$join .= <<<SQL
			INNER JOIN (
			    SELECT DISTINCT subject_id
			    FROM {$this->table}
			    WHERE {$wheres}
			) AS rel_posts
			ON rel_posts.subject_id = {$this->db->posts}.ID
SQL;
		return $join;
	}
}
