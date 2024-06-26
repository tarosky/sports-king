<?php

namespace Tarosky\Common\Models;


use Tarosky\Common\Pattern\Model;

/**
 * つぶやきを保存する
 *
 * @deprecated
 * @package Tarosky\Common\Models
 */
class TwitterLogs extends Model {

	protected $version = '1.0';

	protected $name = 'sk_twitter_logs';

	protected $updated_column = 'modified';

	protected $default_placeholder = [
		'id'         => '%s',
		'abroad'     => '%d',
		'block_id'   => '%s',
		'tweet'      => '%s',
	    'modified'   => '%s',
	];

	/**
	 * データベースを作成する
	 *
	 * @return string
	 */
	protected function build_query() {
		return <<<SQL
		CREATE TABLE `{$this->table}` (
			`id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			`abroad` TINYINT NOT NULL,
			`block_id` VARCHAR(24) NOT NULL,
			`tweet` TEXT NOT NULL,
			`modified` DATETIME NOT NULL,
			PRIMARY KEY ( `id` ),
			INDEX by_tweet ( `abroad`, `block_id` ),
			INDEX by_date ( `modified`, `abroad`, `block_id` )
		) ENGINE=InnoDB DEFAULT CHARSET=utf8
SQL;
	}

	/**
	 * つぶやきの存在を確かめる
	 *
	 * @param int $abroad
	 * @param string $block_id
	 * @param string $text
	 *
	 * @return bool
	 */
	public function exists( $abroad, $block_id, $text ) {
		$query = <<<SQL
			SELECT id FROM {$this->table}
			WHERE abroad = %d
			  AND block_id = %s
			  AND tweet = %s
SQL;

		return (bool) $this->get_var( $query, $abroad, $block_id, $text );
	}

	/**
	 * つぶやきを保存する
	 *
	 * @param bool $abroad
	 * @param string $block_id
	 * @param string $text
	 *
	 * @return false|int
	 */
	public function save( $abroad, $block_id, $text ) {
		return $this->insert([
			'abroad' => $abroad,
		    'block_id' => $block_id,
		    'tweet' => $text,
		]);
	}
}
