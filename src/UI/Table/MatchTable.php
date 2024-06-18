<?php

namespace Tarosky\Common\UI\Table;


use Tarosky\BasketBallKing\Master\LeagueMaster;
use Tarosky\Common\Models\Matches;
use Tarosky\Common\Models\Replacements;
use Tarosky\Common\Utility\Input;

class MatchTable extends \WP_List_Table {

	public function __construct( $args = [] ) {
		parent::__construct( array(
			'singular'  => 'match',
			'plural'    => 'matches',
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
			'season' => 'シーズン',
			'league' => 'リーグ',
			'date'   => '日程',
			'h_team_id' => 'ホーム',
		    'a_team_id' => 'アウェイ',
		    'status' => 'ステータス',
		];
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
		$model = Replacements::instance();
		switch($column_name){
			case 'season':
				return $item->game_year;
				break;
			case 'league':
				$league = bk_get_league_name( $item->league_id );
				return esc_html( LeagueMaster::label( $item->league_id ) );
				break;
			case 'date':
				return sprintf(
					'<a href="%s">%s</a>',
					sk_match_url( $item->id ),
					mysql2date( get_option( 'date_format' ), $item->game_date ) . ' ' . $item->game_time
				);
			case 'h_team_id':
			case 'a_team_id':
				$team = bk_get_team_by_id( $item->{$column_name} );
				if ( $team ) {
					return get_the_title( $team );
				} else {
					return '---';
				}
				break;
			case 'status':
				switch ( $item->status_id ) {
					case 2:
						return '試合終了';
						break;
					case 1:
						return '試合中';
						break;
					case 0:
					default:
						return '試合前';
						break;
				}
				break;
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
		$this->_column_headers = [$this->get_columns(), [], []];

		$input = Input::instance();
		$model = Matches::instance();

		$cur_page = $this->get_pagenum();
		$query = $input->get('s');
		$type = $input->get('view');
		$per_page = 20;

		$offset = (max(1, $cur_page) - 1 ) * $per_page;

		$this->items = $model->search( $query, 0, '', '', '', '', bk_current_season(), '', $cur_page, $per_page);
		$total = $model->match_count;
		$this->set_pagination_args( array(
			'total_items' => $total,
			'per_page'    => $per_page,
			'total_pages' => ceil( $total / $per_page ),
		) );
	}

}
