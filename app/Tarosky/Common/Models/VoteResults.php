<?php

namespace Tarosky\Common\Models;


use Tarosky\Common\Pattern\Model;

/**
 * 投票結果のモデル
 *
 * @package Tarosky\Common\Models
 */
class VoteResults extends Model {

	protected $prefix = 'sk_';

	protected $name = 'vote';

	protected $primary_key = 'id';

	protected $created_column = 'created';

	protected $default_placeholder = array(
		'id'          => '%d',
		'post_id'     => '%d',
		'value'       => '%s',
		'question_no' => '%d',
		'ip'          => '%s',
		'sex'         => '%s',
		'age'         => '%s',
		'pref'        => '%s',
		'job'         => '%s',
		'created'     => '%s',
	);

	/**
	 * 回答のキー
	 *
	 * @var array
	 */
	public $additional_keys = array(
		'sex'  => '性別',
		'job'  => '職業',
		'age'  => '世代',
		'pref' => '都道府県',
	);

	/**
	 * スコアを取得する
	 *
	 * @param int $post_id
	 *
	 * @return array
	 */
	public function get_scores( $post_id, $is_multiple = false ) {
		$ret_results = array();
		if ( ! $is_multiple ) {
			$query       = <<<SQL
				SELECT `value`, COUNT(id) AS score FROM {$this->table}
				WHERE post_id = %d
				GROUP BY `value`
				ORDER BY score DESC
SQL;
			$ret_results = $this->get_results( $query, $post_id );
		} else {
			$query = <<<SQL
				SELECT id, question_no FROM {$this->table}
				WHERE post_id = %d
				GROUP BY `question_no`
				ORDER BY id ASC
SQL;
			foreach ( $this->get_results( $query, $post_id ) as $question ) {
				$query         = <<<SQL
					SELECT `question_no`, `value`, COUNT(id) AS score FROM {$this->table}
					WHERE post_id = %d AND question_no = '{$question->question_no}' 
					GROUP BY `value`
					ORDER BY score DESC
SQL;
				$ret_results[] = $this->get_results( $query, $post_id );
			}
		}

		return $ret_results;
	}

	/**
	 * 属性値を取得する
	 *
	 * @param int $post_id
	 *
	 * @return array
	 */
	public function get_attributes( $post_id ) {
		$result = array();
		foreach ( $this->additional_keys as $key => $label ) {
			$result[ $key ] = $this->get_attribute( $post_id, $key );
		}
		return $result;
	}

	/**
	 * @param int $post_id
	 * @param $key
	 *
	 * @return array
	 */
	public function get_attribute( $post_id, $key ) {
		if ( ! array_key_exists( $key, $this->additional_keys ) ) {
			return array();
		}
		$query = <<<SQL
			SELECT `{$key}` as label, COUNT(id) AS score FROM {$this->table}
			WHERE post_id = %d
			AND question_no = 1
			GROUP BY `{$key}`
			ORDER BY score DESC
SQL;
		// Detect funtion name
		switch ( $key ) {
			case 'age':
				$func = 'sk_generations';
				break;
			case 'sex':
				$func = 'sk_sex';
				break;
			default:
				$func = "sk_{$key}s";
				break;
		}
		return array_map( function ( $row ) use ( $func ) {
			$row->label = call_user_func( $func, $row->label );
			return $row;
		}, $this->get_results( $query, $post_id ) );
	}

	/**
	 * データを挿入する
	 *
	 * @param array $data
	 *
	 * @return false|int
	 */
	public function record_score( $data ) {
		return $this->insert( $data );
	}
}
