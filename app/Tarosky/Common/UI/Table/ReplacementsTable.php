<?php

namespace Tarosky\Common\Tarosky\Common\UI\Table;


use Tarosky\Common\Tarosky\Common\Models\Replacements;
use Tarosky\Common\Tarosky\Common\Utility\Input;
use function Tarosky\Common\UI\Table\admin_url;
use function Tarosky\Common\UI\Table\esc_html;
use function Tarosky\Common\UI\Table\mysql2date;

class ReplacementsTable extends \WP_List_Table {

	public function __construct( $args = [] ) {
		parent::__construct( array(
			'singular'  => 'replacement',
			'plural'    => 'replacements',
			'ajax'      => false,
		) );
	}


	/**
	 * カラム名を返す
	 *
	 * @return array
	 */
	public function get_columns() {
		return [
			'type' => '種別',
		    'replaced' => '置換前 → 置換後',
		    'updated' => '更新日',
		];
	}

	/**
	 * 元のURLを取得する
	 *
	 * @param string $view
	 *
	 * @return string
	 */
	protected function base_url($view){
		$base = admin_url('edit.php?post_type=team&page=sk_replacements');
		$model = Replacements::instance();
		$input = Input::instance();
		$current = $input->get('view');
		if ( isset( $model->types[$view] ) ) {
			$base .= '&view='.$view;
			$label = $model->types[$view];
		} else {
			$label = 'すべて';
		}
		if ( $view == Input::instance()->get('view') ) {
			return sprintf('<strong class="current">%s</strong>', esc_html($label));
		} else {
			return sprintf('<a href="%s">%s</a>', esc_url($base), esc_html($label));
		}
	}

	/**
	 * Get an associative array ( id => link ) with the list
	 * of views available on this table.
	 *
	 * @since 3.1.0
	 * @access protected
	 *
	 * @return array
	 */
	protected function get_views() {
		$views = [
			'' => $this->base_url( '' ),
		];
		foreach ( Replacements::instance()->types as $view => $label ) {
			if ( 'team_short' == $view ) {
				continue;
			}
			$views[ $view ] = $this->base_url( $view );
		}
		return $views;
	}

	/**
	 * 行を表示する
	 *
	 * @param object $item
	 * @param string $column_name
	 *
	 * @return string
	 */
	public function column_default( $item, $column_name ) {
		$model = Replacements::instance();
		switch ( $column_name ) {
			case 'type':
				return esc_html( $item->label );
			case 'replaced':
				$out = [];
				foreach ( [ 'orig', 'replaced' ] as $p ) {
					$out[] = sprintf( '<input class="regular-text sk-repl-%1$s" type="text" value="%2$s" />', esc_attr( $p ), esc_attr( $item->{$p} ) );
					$out[] = ' → ';
				}
				array_pop( $out );
				$out = implode( ' ', $out );
				$out .= $this->row_actions( [
					'save'   => sprintf( '<a href="#" class="save-repl" data-id="%s">保存</a>', $item->id ),
					'delete' => sprintf( '<a href="#" class="delete-repl" data-id="%s">削除</a>', $item->id ),
				] );
				return $out;
			case 'updated':
				return mysql2date( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), $item->updated );
			default:
				// Do nothing.
				return '';
		}
	}

	public function get_table_classes() {
		return array( 'widefat', 'striped', $this->_args['plural'] );
	}

	/**
	 * アイテムを取得する
	 */
	public function prepare_items() {
		$this->_column_headers = [$this->get_columns(), [], []];

		$input = Input::instance();
		$model = Replacements::instance();

		$cur_page = $this->get_pagenum();
		$query = $input->get('s');
		$type = $input->get('view');
		$per_page = 50;

		$offset = (max(1, $cur_page) - 1 ) * $per_page;

		$this->items = $model->get_item($type, $query, $offset, $per_page);
		$total = $model->found_count();
		$this->set_pagination_args( array(
			'total_items' => $total,
			'per_page'    => $per_page,
			'total_pages' => ceil($total / $per_page),
		) );
	}

}
