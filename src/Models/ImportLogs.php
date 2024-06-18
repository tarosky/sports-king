<?php

namespace Tarosky\Common\Models;


use Tarosky\Common\Pattern\Model;

class ImportLogs extends Model{

	protected $version = '1.0';

	protected $name = 'sk_import_logs';

	protected $primary_key = 'id';

	protected $updated_column = 'updated';

	protected $default_placeholder = [
		'id' => '%d',
	    'key' => '%s',
	    'file' => '%s',
	    'contents' => '%s',
	    'note' => '%s',
	    'updated' => '%s'
	];

	/**
	 * クエリ
	 *
	 * @return string
	 */
	public function build_query() {
		return <<<SQL
		CREATE TABLE {$this->table}(
			`id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			`key` VARCHAR(48) NOT NULL,
			`file` VARCHAR(256) NOT NULL,
			`contents` LONGTEXT NOT NULL,
			`note` LONGTEXT NOT NULL,
			`updated` DATETIME NOT NULL,
			PRIMARY KEY (id),
			INDEX key_file(`key`, file(6))
		) ENGINE=InnoDB DEFAULT CHARSET=utf8
SQL;
	}

	/**
	 * セーブする
	 *
	 * @param string $key
	 * @param string $file
	 * @param string $contents
	 * @param string $note
	 *
	 * @return false|int
	 */
	public function save($key, $file, $contents = '', $note = ''){
		return $this->insert([
			'key' => $key,
		    'file' => $file,
		    'contents' => $contents,
		    'note' => $note,
		]);
	}

	/**
	 * 存在をチェック
	 *
	 * @param string $key
	 * @param string $file
	 *
	 * @return bool
	 */
	public function exist($key, $file){
		$query = <<<SQL
			SELECT id FROM {$this->table}
			WHERE `key` = %s AND `file` = %s
SQL;
		return (bool) $this->get_var($query, $key, $file);
	}

	/**
	 * 古いコンテンツを取得する
	 *
	 * @param string $key
	 * @param string $file
	 *
	 * @return \stdClass
	 */
	public function get_content($key, $file){
		$query = <<<SQL
			SELECT * FROM {$this->table}
			WHERE `key` = %s AND `file` = %s
SQL;
		return $this->get_row($query, $key, $file);
	}

}
