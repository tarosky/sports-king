<?php

namespace Tarosky\Common\UI\Table;


use Tarosky\BasketBallKing\Statics\Leagues;
use Tarosky\Common\Models\Matches;
use Tarosky\Common\Models\Replacements;
use Tarosky\Common\Utility\Input;

class TeamShortNameTable extends \WP_List_Table {

	public function __construct( $args = [] ) {
		parent::__construct( array(
			'singular'  => 'short_name',
			'plural'    => 'short_names',
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
			'name' => '名称',
		    'default_name' => 'デフォルト値',
		    'replaced' => '置換',
		];
	}

	/**
	 * 元のURLを取得する
	 *
	 * @param string $id
	 * @oaram string $label
	 *
	 * @return string
	 */
	protected function base_url( $id, $label ) {
		$base = admin_url( 'edit.php?post_type=team&page=sk_team_short_name' );
		$input = Input::instance();
		$current = $input->get( 'view' );
		$leagues = Leagues::LEAGUES[1];
		if ( ! $id ) {
			// Do nothing.
		} elseif ( array_key_exists( $id, $leagues ) ) {
			$base .= '&view='.$id;
		} else {
			$base .= '&view=other';
		}
		if ( $id == $current ) {
			return sprintf( '<strong class="current">%s</strong>', esc_html( $label ) );
		} else {
			return sprintf( '<a href="%s">%s</a>', esc_url( $base ), esc_html( $label ) );
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
			'' => $this->base_url( '', 'すべて' ),
		];
		foreach ( Leagues::LEAGUES[1] as $id => $league ) {
			$views[ $id ] = $this->base_url( $id, $league['label'] );
		}
		$views['other'] = $this->base_url( 'other', 'その他' );
		return $views;
	}

	/**
	 * 行を表示する
	 *
	 * @param object $item
	 * @param string $column_name
	 *
	 * @return bool|int|string
	 */
	public function column_default( $item, $column_name ) {
		switch ( $column_name ) {
			case 'name':
				return esc_html( $item->h_team );
				break;
			case 'default_name':
				return esc_html( mb_substr( $item->h_team, 0, 4,'utf-8' ) );
				break;
			case 'replaced':
				$short = Replacements::instance()->short_name( $item->h_team );
				$out = sprintf(
					'<input type="text" class="team-short-input" value="%s" placeholder="%s" />',
					esc_attr( $short ? $short->replaced : '' ),
					esc_attr( mb_substr( $item->h_team, 0, 4,'utf-8' ) )
				);
				$out .= sprintf( '<input type="hidden" value="%s" />', $item->h_team );
				$out .= $this->row_actions([
					'save'   => '<a href="#" class="save-team-short">保存</a>',
				    'delete' => '<a href="#" class="delete-team-short">削除</a>',
				]);
				return $out;
			default:
				// Do nothing.
				break;
		}
	}

	public function get_table_classes() {
		return array( 'widefat', 'striped', $this->_args['plural'] );
	}

	/**
	 * アイテムを取得する
	 */
	public function prepare_items() {
		$this->_column_headers = [ $this->get_columns(), [], [] ];
		$input = Input::instance();
		$model = Matches::instance();
		$cur_page = $this->get_pagenum();
		$query = $input->get( 's' );
		$type = $input->get( 'view' );
		$per_page = 50;
		$offset = ( max( 1, $cur_page ) - 1 ) * $per_page;
		$this->items = $model->get_name_list( $type, $query, $offset, $per_page );
		$total = $model->found_count();
		$this->set_pagination_args( [
			'total_items' => $total,
			'per_page'    => $per_page,
			'total_pages' => ceil( $total / $per_page ),
		] );
	}

}
