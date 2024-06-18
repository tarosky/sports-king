<?php

namespace Tarosky\Common\Pattern;

use Tarosky\Common\Utility\StringHelper;

/**
 * Model base
 *
 * @package Tarosky\Common\Pattern
 * @property-read \wpdb $db
 * @property-read StringHelper $str
 * @property-read string $table
 * @method null|string get_var( $query )
 * @method null|\stdClass get_row( $query )
 * @method int query( $query )
 * @method array get_col( $query )
 * @method array get_results( $query )
 */
abstract class Model extends Singleton {

	/**
	 * @var string
	 */
	protected $version = '1.0';

	/**
	 * Name of table
	 *
	 * If not specified, class name will be used.
	 *
	 * @var string
	 */
	protected $name = '';

	/**
	 * Prefix of table
	 *
	 * If not specified, <code>$wpdb->prefix</code> will be used.
	 *
	 * @var string
	 */
	protected $prefix = '';

	/**
	 * Primary key for this model
	 *
	 * <code>$this_table.$this->primary_key</code> will used for
	 * <code>$this->get()</code> method.
	 *
	 * @var string
	 */
	protected $primary_key = 'ID';

	/**
	 * If specified, automatically inserted.
	 *
	 * @var string
	 */
	protected $created_column = '';

	/**
	 * If specified, automatically inserted.
	 *
	 * @var string
	 */
	protected $updated_column = '';

	/**
	 * Specify default column's placeholder.
	 *
	 * <code>
	 * // column => placeholder
	 * [ 'date' => '%s', 'post_id' => '%d']
	 * </code>
	 *
	 * @var array
	 */
	protected $default_placeholder = [];

	/**
	 * Row parse class
	 *
	 * @var string
	 */
	protected $result_class = '';

	/**
	 * Get found rows
	 *
	 * @return int
	 */
	final public function found_count() {
		return (int) $this->db->get_var( 'SELECT FOUND_ROWS() AS count' );
	}

	/**
	 * Get row
	 *
	 * @param int $id
	 *
	 * @return null|\stdClass
	 */
	public static function get( $id ) {
		$model = static::instance();
		$query = "SELECT * FROM {$model->table} WHERE {$model->primary_key} = %d";

		return $model->get_row( $query, $id );
	}

	/**
	 * Get prepared query.
	 *
	 * @param string $sql SQL.
	 *
	 * @return mixed
	 */
	protected function prepare( $sql ) {
		$args = func_get_args();
		if ( 1 < count( $args ) ) {
			return call_user_func_array( [ $this->db, 'prepare' ], $args );
		} else {
			return $sql;
		}
	}

	/**
	 * Get converted result
	 *
	 * @param string $query SQL.
	 *
	 * @return null|\stdClass
	 */
	public function row( $query ) {
		$row = call_user_func_array( [ $this, 'get_row' ], func_get_args() );
		if ( ! $row || ! $this->result_class ) {
			return $row;
		} else {
			return new $this->result_class( $row );
		}
	}

	/**
	 * Get converted results.
	 *
	 * @param string $query SQL.
	 *
	 * @return array|mixed
	 */
	public function results( $query ) {
		$results = call_user_func_array( [ $this, 'get_results' ], func_get_args() );
		if ( $this->result_class ) {
			$converted_result = [];
			foreach ( $results as $result ) {
				$converted_result[] = new $this->result_class( $result );
			}

			return $converted_result;
		} else {
			return $results;
		}
	}

	/**
	 * Insert row
	 *
	 * @param array $values Array with 'column' => 'value'.
	 * @param array $place_holders Array of '%s', '%d', '%f'.
	 * @param string $table Default this table.
	 *
	 * @return false|int Number of rows or false on failure
	 */
	protected function insert( array $values, array $place_holders = [], $table = '' ) {
		if ( ! $table ) {
			$table = $this->table;
		}
		if ( $this->created_column && ! isset( $values[ $this->created_column ] ) ) {
			$values[ $this->created_column ] = current_time( 'mysql' );
		}
		if ( $this->updated_column && ! isset( $values[ $this->updated_column ] ) ) {
			$values[ $this->updated_column ] = current_time( 'mysql' );
		}
		if ( ! $place_holders ) {
			$place_holders = $this->parse_place_holders( $values );
		}

		return $this->db->insert( $table, $values, $place_holders );
	}

	/**
	 * Update table
	 *
	 * @param array $values Value to update.
	 * @param array $wheres ['column' => 'value'] format.
	 * @param array $place_holders Place holder for value.
	 * @param array $where_format Place holder for where.
	 * @param string $table Default this table.
	 *
	 * @return false|int
	 */
	protected function update( array $values, array $wheres = [], array $place_holders = [], array $where_format = [], $table = '' ) {
		if ( ! $table ) {
			$table = $this->table;
		}
		if ( $this->updated_column && ! isset( $values[ $this->updated_column ] ) ) {
			$values[ $this->updated_column ] = current_time( 'mysql' );
		}
		if ( ! $place_holders ) {
			$place_holders = $this->parse_place_holders( $values );
		}
		if ( ! $where_format ) {
			$where_format = $this->parse_place_holders( $wheres );
		}

		return $this->db->update( $table, $values, $wheres, $place_holders, $where_format );
	}

	/**
	 * Get place holder.
	 *
	 * @param array $values Get where with $default_placeholder.
	 *
	 * @return array
	 */
	protected function parse_place_holders( $values ) {
		$place_holders = [];
		foreach ( $values as $key => $val ) {
			$place_holders[] = isset( $this->default_placeholder[ $key ] ) ? $this->default_placeholder[ $key ] : '%s';
		}

		return $place_holders;
	}


	/**
	 * Delete record
	 *
	 * @param array $wheres Where to delete.
	 * @param array $place_holder Placeholder.
	 * @param string $table If not specified, <code>$this->table</code> will be used.
	 *
	 * @return false|int
	 */
	protected function delete( array $wheres, $place_holder = [], $table = '' ) {
		if ( ! $table ) {
			$table = $this->table;
		}
		if ( $place_holder ) {
			$place_holder = $this->parse_place_holders( $wheres );
		}

		return $this->db->delete( $table, $wheres, $place_holder );
	}

	/**
	 * Magic method
	 *
	 * @param string $name Function name.
	 * @param array $arguments Arguments.
	 *
	 * @return mixed
	 */
	public function __call( $name, $arguments ) {
		switch ( $name ) {
			case 'get_row':
			case 'get_var':
			case 'get_results':
			case 'get_col':
			case 'query':
				return call_user_func_array( [ $this->db, $name ], [
					call_user_func_array( [
						$this,
						'prepare',
					], $arguments )
				] );
				break;
			default:
				// Do nothing.
				break;
		}
	}

	/**
	 * Build hook
	 */
	public function build() {
		add_action( 'admin_init', [ $this, 'build_hook' ] );

	}

	/**
	 * Register hooks.
	 */
	protected function hooks() {
		// Do  nothing.
	}

	/**
	 * Build Query
	 *
	 * @return string
	 */
	protected function build_query() {
		return '';
	}

	/**
	 * 構築時に実行される
	 */
	public function build_hook() {
		$query = $this->build_query();
		if ( $query ) {
			$installed_version = get_option( "{$this->table}_version", '' );
			if ( ! $installed_version || version_compare( $this->version, $installed_version, '>' ) ) {
				if ( ! function_exists( 'dbDelta' ) ) {
					require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
				}
				$for_update = dbDelta( $query );
				update_option( "{$this->table}_version", $this->version );
				$message = "データベース{$this->table}をバージョン{$this->version}にアップデートしました。";
				add_action( 'admin_notices', function () use ( $message ) {
					printf( '<div class="updated"><p>%s</p></div>', esc_html( $message ) );
				} );
			}
		}
	}

	/**
	 * Getter magic method
	 *
	 * @param string $name Name of key.
	 *
	 * @return mixed
	 */
	public function __get( $name ) {
		switch ( $name ) {
			case 'db':
				global $wpdb;

				return $wpdb;
				break;
			case 'table':
				$prefix = $this->prefix ?: $this->db->prefix;
				if ( $this->name ) {
					$name = $this->name;
				} else {
					$seg  = explode( '\\', get_called_class() );
					$name = $this->str->decamelize( $seg[ count( $seg ) - 1 ] );
				}

				return $prefix . $name;
				break;
			case 'str':
				return StringHelper::instance();
				break;
			default:
				return null;
				break;
		}
	}
}
