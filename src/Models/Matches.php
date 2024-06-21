<?php

namespace Tarosky\Common\Models;


use Tarosky\Common\Master\LeagueMaster;
use Tarosky\Common\Pattern\Model;
use Tarosky\Common\Statics\Leagues;

/**
 * データを取得する
 *
 * @package Tarosky\Common\Models
 */
class Matches extends Model {

	protected $version = '1.0.8';

	protected $name = 'bk_matches';

	protected $updated_column = 'modified';

	public $match_count = 0;

	protected $default_placeholder = [
		'id'         => '%s',
		'source'     => '%s',
		'league_id'  => '%s',
		'game_id'    => '%s',
		'game_year'  => '%d',
		'game_date'  => '%s',
		'game_time'  => '%s',
		'stadium_id' => '%d',
		'occasion'   => '%d',
		'round'      => '%s',
		'conference_id'  => '%d',
		'inter_league' => '%d',
		'max_period' => '%d',
		'status_id'  => '%d', // 0: 試合前 1: 試合中 2: 試合終了
		'h_team_id'  => '%d',
		'a_team_id'  => '%d',
		'h_score'    => '%d',
		'a_score'    => '%d',
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
			`source` VARCHAR(48) DEFAULT 'ds' NOT NULL,
			`league_id` VARCHAR(4) NOT NULL,
			`game_id` VARCHAR(24) NOT NULL,
			`game_year` INT UNSIGNED NOT NULL,
			`game_date` DATE NOT NULL,
			`game_time` TIME NOT NULL,
			`stadium_id` BIGINT UNSIGNED NOT NULL,
			`occasion` INT UNSIGNED NOT NULL,
			`inter_league` TINYINT UNSIGNED NOT NULL,
			`round` VARCHAR(48) NOT NULL,
			`conference_id` INT UNSIGNED NOT NULL,
			`max_period` INT UNSIGNED NOT NULL,
			`status_id` BIGINT UNSIGNED NOT NULL,
			`h_team_id` BIGINT UNSIGNED NOT NULL,
			`a_team_id` BIGINT UNSIGNED NOT NULL,
			`h_score` INT UNSIGNED NOT NULL,
			`a_score` INT UNSIGNED NOT NULL,
			`modified` DATETIME NOT NULL,
			PRIMARY KEY ( `id` ),
			UNIQUE source_id ( `source`, `game_id` ),
			INDEX legue ( `source`, `league_id` ),
			INDEX by_match ( `game_id` ),
			INDEX by_date ( `game_date`, `league_id` )
		) ENGINE=InnoDB DEFAULT CHARSET=utf8
SQL;
	}

	/**
	 * Insert data
	 *
	 * @param array $data
	 *
	 * @return false|int
	 */
	public function create( $data ) {
		return $this->insert( $data );
	}

	/**
	 * データを更新する
	 *
	 * @param array $value
	 * @param array $where
	 *
	 * @return false|int
	 */
	public function modify( $value, $where ) {
		return $this->update( $value, $where );
	}

	/**
	 * データの存在を確認する
	 *
	 * @param string $deprecated 使わない
	 * @param int    $block_id
	 *
	 * @return int
	 */
	public function exists( $deprecated, $block_id ) {
		$query = <<<SQL
			SELECT id FROM {$this->table}
			WHERE game_id = %s
SQL;

		return (int) $this->get_var( $query, $block_id );
	}

	/**
	 * チーム名が空のスケジュールを取得する
	 *
	 * @return array|mixed
	 */
	public function get_empty_match() {
		$query = <<<SQL
			SELECT * FROM {$this->table}
			WHERE abroad = 0 AND source = 'ds'
			  AND ( a_team = '' OR  h_team = '' )
SQL;
		return $this->results( $query );
	}

	/**
	 * ステータスが空のスケジュールを取得する
	 *
	 * @return array|mixed
	 */
	public function get_empty_status(){
		$query = <<<SQL
			SELECT * FROM {$this->table}
			WHERE abroad = 0 AND source = 'ds'
			  AND ( `status` = '' )
SQL;
		return $this->results( $query );
	}

	/**
	 * 最近のスケジュールを取得する
	 *
	 * @param bool $deprecated
	 * @param string $league_id
	 * @param int $team_id
	 * @param bool $current_season
	 * @param bool $extras 指定した場合は、前後に足して返す
	 * @param int $offset
	 * @param int $per_page
	 *
	 * @return array
	 */
	public function get_recent( $deprecated = false, $league_id = '', $team_id = 0, $current_season = true, $extras = true, $offset = 0, $per_page = 20 ) {
		$place_holders = [ 'ds' ];
		$wheres = [
			'm.`source` = %s',
		];
		$wheres[] = 'm.`game_date` != 0000-00-00';
		// リーグIDが指定されていたら
		if ( $league_id ) {
			$wheres[] = 'm.`league_id` = %s';
			$place_holders[] = $league_id;
		}
		// チームが指定されていたら
		if ( $team_id ) {
			$wheres[] = '( (  m.a_team_id = %d )  OR  ( m.h_team_id = %d ) )';
			$place_holders[] = $team_id;
			$place_holders[] = $team_id;
		}
		// 現在のシーズンに限定
		if ( $current_season ) {
			$wheres[] = '( m.game_year = %d )';
			$season = sk_current_season();
			$place_holders[] = $season;
		}
		$wheres = implode( ' AND ', $wheres );
		$query         = <<<SQL
			SELECT SQL_CALC_FOUND_ROWS * FROM {$this->table} AS m
			WHERE {$wheres}
SQL;
		array_unshift( $place_holders, $query );
		$query    = call_user_func_array( [ $this->db, 'prepare' ], $place_holders );
		// 前後3+7件を取得
		if ( $extras ) {
			$schedule = [];
			foreach (
				[
					$this->db->prepare( ' AND ( m.game_date > %s ) ORDER BY m.game_date ASC, m.game_time ASC LIMIT 3', current_time( 'mysql' ) )   => true,
					$this->db->prepare( ' AND ( m.game_date <= %s ) ORDER BY m.game_date DESC, m.game_time DESC LIMIT 7', current_time( 'mysql' ) ) => false,
				] as $q => $future
			) {
				$result = $this->db->get_results( $query . $q );
				foreach ( $result as $r ) {
					if ( $future ) {
						$schedule[] = $r;
					} else {
						array_unshift( $schedule, $r );
					}
				}
			}
		} else {
			if( $per_page !== -1 ) {
				$query .= $this->db->prepare( ' ORDER BY m.game_date ASC LIMIT %d, %d', $offset, $per_page );
			}
			else {
				$query .= ' ORDER BY m.game_date ASC ';
			}
			$schedule = $this->db->get_results( $query );
		}
		return array_filter( $this->fill_match( $schedule ), function ( $row ) {
		    return LeagueMaster::is_available( $row->league_id );
        });
	}

    /**
     * Override
     *
     * @param int $id
     *
     * @return mixed|null|\stdClass
     */
	public function get_match_by_id( $id ) {
	    if ( ! $row = $this->get( $id ) ) {
	        return $row;
        }
	    return current( $this->fill_match( [$row] ) );
    }

    /**
	 * Jリーグの節を返す
	 *
	 * @param bool   $abroad
	 *
	 * @return array|mixed
	 */
	public function get_regs ( $abroad ) {
		$source = $abroad ? 'kyodo' : 'ds';
		$range  = Leagues::get_range( $abroad );
		$query = <<<SQL
			SELECT league_id, stage, MAX(reg) FROM {$this->table}
			WHERE `abroad` = %d
			  AND `source` = %s
			  AND `date` BETWEEN %s AND %s
			GROUP BY league_id, stage
SQL;
		return $this->results( $query, $abroad, $source, $range[0], $range[1] );
	}

	/**
	 * 最新の期間を取る
	 *
	 * @param string $abroad
	 * @param string $league_id
	 *
	 * @return null|\stdClass
	 */
	public function get_nearest_reg( $abroad, $league_id ) {
		$now = date_i18n( 'Y-m-d H:i:s' );
		$query = <<<SQL
			SELECT reg, stage, ymd FROM {$this->table}
			WHERE `abroad` = %d
			  AND `source` = %s
			  AND `league_id` = %s
			  AND `date` < %s
		    ORDER BY `date` DESC
			LIMIT 1
SQL;
		$prepare = $this->db->prepare( $query, $abroad, 'ds', $league_id, $now );
		return $this->row( $query, $abroad, $abroad ? 'kyodo' : 'ds', $league_id, $now );
	}

	/**
	 * 直近のシーズンを取得する
	 *
	 * @param bool $abroad
	 * @param string $league_id
	 * @param string $time
	 * @param bool $year
	 *
	 * @return string
	 */
	public function get_nearest_season( $abroad, $league_id, $time = '', $year = false ) {
		$year = $this->current_season($abroad, $year );
		if ( ! $time ) {
			$time = date_i18n( 'Y-m-d H:i:s' );
		}
		$source = $abroad ? 'kyodo' : 'ds';
		$query = <<<SQL
			SELECT stage, reg FROM {$this->table}
			WHERE `abroad` = %d
			  AND `source` = %s
			  AND `league_id` = %s
		      AND season = %d
		    ORDER BY ABS( DATEDIFF(`date`, %s)) ASC
			LIMIT 1
SQL;
		if ( $result = $this->row( $query, $abroad, $source, $league_id, $year, $time ) ) {
			return "{$result->stage}-{$result->reg}";
		} else {
			return '1-1';
		}

	}

	/**
	 * 直近の試合の日付を取得する
	 *
	 * @param bool $deprecated
	 * @param string $league_id
	 * @param string $time
	 * @param bool $year
	 *
	 * @return string
	 */
	public function get_nearest_ymd( $deprecated, $league_id, $time = '' ) {
		if ( ! $time ) {
			$time = date_i18n( 'Y-m-d H:i:s' );
		}
		$query = <<<SQL
			SELECT game_date FROM {$this->table}
			WHERE  `source` = 'ds'
			  AND `league_id` = %s
		    ORDER BY ABS( DATEDIFF(`game_date`, %s)) ASC
			LIMIT 1
SQL;
		if ( $result = $this->row( $query, $league_id, $time ) ) {
			$ret_ymd = $result->game_date;
			if( ! $ret_ymd ) {
				$ret_ymd = mysql2date( 'Y-m-d', $result->date );
			}
			return $ret_ymd;
		}
		return '';
	}

    /**
     * 一番近いOccasionを取得する
     *
     * @param $deprecated
     * @param $league_id
     * @param $season
     * @param string $date_time
     * @return null|string
     */
	public function get_nearest_occasion( $deprecated, $league_id, $season, $date_time = '' ) {
	    if ( ! $date_time ) {
	        $date_time = date_i18n( 'Y-m-d' );
        }
        $query = <<<SQL
          SELECT occasion FROM {$this->table}
          WHERE  `source` = 'ds'
            AND  `league_id` = %d
            AND  game_year = %d
            AND  game_date >= %s
          ORDER BY game_date ASC
          LIMIT 1
SQL;
		if( !$recent = $this->get_var( $query, $league_id, $season, $date_time ) ){
			$query = <<<SQL
			  SELECT occasion FROM {$this->table}
			  WHERE  `source` = 'ds'
				AND  `league_id` = %d
				AND  game_year = %d
				AND  game_date < %s
			  ORDER BY game_date DESC
			  LIMIT 1
SQL;
			$recent = $this->get_var( $query, $league_id, $season, $date_time );
		}
	    return $recent;
    }

    /**
     * 節の一番小さい日付を取得する
     *
     * @param $abroad
     * @param $league_id
     * @param $season
     * @param $occasion
     * @return null|string
     */
	public function get_start_date( $abroad, $league_id, $season, $occasion ) {
	    $query = <<<SQL
          SELECT MIN(game_date) AS game_date FROM {$this->table}
          WHERE  `source` = 'ds'
            AND  `league_id` = %s
            AND  `game_year` = %d
            AND  `occasion` = %d
SQL;
	    return $this->get_var( $query, $league_id, $season, $occasion );
    }

	/**
	 * 指定した節のゲームを取得する
	 *
	 * @param bool $abroad
	 * @param string $league_id
	 * @param string $reg
	 * @param string $stage
	 *
	 * @return array
	 */
	public function get_list( $abroad, $league_id, $from, $to ) {
		$source = $abroad ? 'kyodo' : 'ds';
		$wheres = [
//			'( `abroad` = %d )',
		    '( `source` = %s )',
		    '( `league_id` = %s )',
		];
		$wheres[] = $this->db->prepare( '( `game_date` BETWEEN %s AND %s )', $from, $to );
		$wheres = implode( ' AND ', $wheres );
		$query = <<<SQL
			SELECT * FROM {$this->table}
			WHERE {$wheres}
			ORDER BY `game_date` DESC
SQL;
		$matches = $this->results( $query, $source, $league_id );
        return $this->fill_match( $matches );
	}

	/**
	 * シーズンごとの試合を全部取得する
	 *
	 * @param bool $deprecated
	 * @param int $league_id
	 * @param string $season
	 * @param bool $year
	 *
	 * @return array|mixed
	 */
	public function get_season( $deprecated, $league_id, $season, $year = false ) {
	    if ( ! $year ) {
	        $year = sk_current_season();
        }
		$source = 'ds';
		$wheres = [
            'source = %s',
		    'league_id = %s',
            'game_year = %d',
        ];
		$placeholders = [
		    $source, $league_id, $year
        ];
		$wheres = implode( ' AND ', array_map( function( $where ) {
		    return "({$where})";
        }, $wheres ) );

		$query = <<<SQL
			SELECT * FROM {$this->table}
			WHERE {$wheres}
			ORDER BY `game_date` DESC
SQL;
        array_unshift( $placeholders, $query );
        $matches = $this->results( call_user_func_array( [$this->db, 'prepare'] , $placeholders) );
        return $this->fill_match( $matches );
	}

    /**
     * データベースにスタジアム、チームを追加する
     *
     * @param array $matches
     * @return array
     */
	protected function fill_match( $matches ) {
	    $ids = [
            'team' => [],
            'stadium' => [],
        ];
        foreach ( $matches as $match ) {
            foreach ( [ 'h_team_id', 'a_team_id' ] as $key ) {
                $id = $match->{$key};
                if ( ! isset( $ids['team'][$id] ) ) {
                    $ids['team'][$id] = null;
                }
            }
            if ( ! isset( $ids['stadium'][$match->stadium_id] ) ) {
                $ids['stadium'][$match->stadium_id] = null;
            }
        }
        foreach ( get_posts( [
            'post_type'   => 'team',
            'post_status' => 'publish',
            'posts_per_page' => -1,
            'meta_query' => [
                [
                    'key' => '_team_id',
                    'value' => array_keys( $ids['team'] ),
                    'compare' => 'IN',
                ]
            ],
        ] ) as $post ) {
            $id = get_post_meta( $post->ID, "_team_id", true );
            $ids['team'][$id] = $post;
        }
        foreach ( get_terms( [
            'taxonomy' => 'stadium',
            'number' => 0,
            'hide_empty' => false,
            'meta_query' => [
                [
                    'key' => 'stadium_id',
                    'value' => array_keys( $ids['stadium'] ),
                    'compare' => 'IN',
                ]
            ],
        ] ) as $term ) {
            $id = get_term_meta( $term->term_id, 'stadium_id', true );
            $ids['stadium'][$id] = $term;
        }
        return array_map( function( $match ) use ( $ids ) {
            $match->h_team = $ids['team'][$match->h_team_id];
            $match->a_team = $ids['team'][$match->a_team_id];
            $match->stadium = $ids['stadium'][$match->stadium_id];
            $match->status  = $this->convert_match_status( $match->status_id );
            return $match;
        }, $matches );

    }

    /**
     * Convert Status
     * @param int $status_id
     * @return string
     */
    public function convert_match_status( $status_id ) {
        switch ( $status_id ) {
            case 1:
                $status = '試合中';
                break;
            case 2:
                $status = '試合終了';
                break;
            default:
                $status = '試合前';
                break;
        }
        return $status;
    }

	/**
	 * シーズンを返す
	 *
	 * @param $abroad
	 * @param string $year
	 * @param string $month
	 *
	 * @return int|string
	 */
	public function current_season( $abroad = 0, $year = '', $month = '', $deprecated = false ){
		if( !$year ){
			$year = date_i18n('Y');
		}
		if( !$month ){
			$month = date_i18n('n');
		}
		if( $month < 9 ){
			$year -= 1;
		}
		return $year;
	}

	/**
	 * 試合を取得する
	 *
	 * @param bool $abroad
	 * @param string $league_id
	 * @param int $year
	 *
	 * @return array
	 */
	function get_range ( $abroad, $league_id, $year = 0 ) {
		$year = $this->current_season($abroad, $year, '', true);
		$source = 'ds';
		$query = <<<SQL
			SELECT game_year, occasion, round, MIN(`game_date`) as date_from FROM {$this->table}
			WHERE `source` = %s
		      AND `league_id` = %s
			  AND `game_year` = %d
			  AND `occasion` > 0
	      	GROUP BY game_year, occasion
			ORDER BY occasion ASC
SQL;
		return array_map( function( $row ) {

			return $row;
		}, $this->results( $query, $source, $league_id, $year ) );
	}

	/**
	 * 試合データを取得する
	 *
	 * @param int $abroad
	 * @param string $block_id
	 *
	 * @return null|\stdClass
	 */
	public function get_match( $abroad, $block_id ) {
		$query = <<<SQL
			SELECT * FROM {$this->table}
			WHERE  `game_id` = %s
SQL;
		return $this->row( $query, $block_id );
	}



	/**
	 * 試合を検索する
	 *
	 * @param string $query
     * @param string $abroad
	 * @param string $year
	 * @param string $month
	 * @param string $day
     * @param int    $season
     * @param int    $occasion
     * @param null|int $paged
     * @param int $per_page
	 *
	 * @return array
	 */
	public function search( $query = '', $abroad = '', $league = '', $year = '', $month = '', $day = '', $season = false, $occasion = '', $paged = null, $per_page = 10 ) {
		$wheres = [];
		// クエリを設定
		if ( '' !== $abroad ) {
//			$wheres[] = $this->db->prepare( '( `abroad` = %d )', $abroad );
		}
        if ('' !== $league) {
            $wheres[] = $this->db->prepare('( `league_id` = %d )', $league);
        }
		// 年月日を作成
		if ( $year && $month && $day ) {
			$wheres[] = sprintf( '( CAST(`game_date` AS DATE) = \'%04d-%02d-%02d\' )', $year, $month, $day );
		} elseif ( $year && $month ) {
			$wheres[] = sprintf( '( EXTRACT(YEAR_MONTH FROM `game_date`) = \'%04d%02d\' )', $year, $month );
		} elseif ( $year ) {
			$wheres[] = sprintf( '(EXTRACT(YEAR FROM `game_date`) = \'%04d\'', $year );
		}
		if ( $season ) {
            $wheres[] = $this->db->prepare( '( game_year = %d )', $season );
        }
		if ( '' != $occasion ) {
		    $wheres[] = $this->db->prepare( '( occasion = %d )', $occasion );
        }
		// クエリを指定する
		if ( $query ) {
            $team_ids = [];
            foreach ( get_posts([
                'post_type'  => 'any',
                'post_status' => 'publish',
                's'          => $query,
                'posts_per_page' => -1,
            ]) as $post ) {
                switch ( $post->post_type ) {
                    case 'player':
                        $post_id = $post->post_parent;
                        break;
                    default:
                        $post_id = $post->ID;
                        break;
                }
                if ( ! ( $team_id = sk_meta( '_team_id', $post_id ) ) ) {
                    continue;
                }
                if ( false === array_search( $team_id, $team_ids ) ) {
                    $team_ids[] = $team_id;
                }
            }
            if ( ! $team_ids ) {
                return [];
            }
            $team_ids = implode( ', ', array_map( 'intval', $team_ids ) );
			$wheres[] = "( ( h_team_id IN ({$team_ids}) ) OR ( a_team_id IN ({$team_ids}) ) )";
		}
		// 条件が存在しなければ検索しない
		if ( empty( $wheres ) ) {
			return [];
		}
		$wheres = 'WHERE ' . implode( ' AND ', $wheres );
		// クエリを作成
        if ( is_null( $paged ) ) {
            $limit = sprintf( 'LIMIT %d', $per_page );
        } else {
            $paged = max(1, (int) $paged);
            $limit = sprintf( 'LIMIT %d, %d', $per_page * ( $paged - 1 ), $per_page );
        }
		$sql  = <<<SQL
			SELECT SQL_CALC_FOUND_ROWS *
			FROM {$this->table}
			{$wheres}
			ORDER BY game_date ASC, game_time ASC
			{$limit}
SQL;
		$result = $this->get_results( $sql );
//		var_dump( $sql );
//		exit;
		$this->match_count = $this->found_count();
		$result = $this->fill_match( $result );
		return $result;
	}

	/**
	 * 名前のリストを取得する
	 *
	 * @param string $league
	 * @param string $search_query
	 * @param int $offset
	 * @param int $per_page
	 *
	 * @return array
	 */
	public function get_name_list( $league = '', $search_query = '', $offset = 0, $per_page = 50 ) {
		$wheres = [
			'( abroad = 1)',
		    "( `source` = 'kyodo' )",
		    $this->db->prepare( '( YEAR( `date` ) >= %d )', date_i18n('Y') - 1 ),
		];
		if ( ! $league ) {
			// Do nothing
		} elseif ( array_key_exists( $league, Leagues::LEAGUES[1] ) ) {
			$wheres[] = $this->db->prepare( 'league_id = %s', $league );
		} else {
			$leagues = implode( ', ', array_map( function($league){
				return "'{$league}'";
			}, array_keys( Leagues::LEAGUES[1] ) ) );
			$wheres[] = "( league_id NOT IN ({$leagues}) )";
		}
		// クエリを指定する
		if ( $search_query ) {
			$wheres[] = '(' . implode( ' AND ', array_map( function ( $q ) {
				return $this->db->prepare( '( ( h_team LIKE %s) )', "%{$q}%" );
			}, preg_split( '/[ |　]/u', $search_query ) ) ) . ')';
		}
		$wheres = implode( ' AND ', $wheres );
		$sql = <<<SQL
			SELECT SQL_CALC_FOUND_ROWS h_team
			FROM {$this->table}
			WHERE {$wheres}
			GROUP BY h_team
			ORDER BY h_team ASC
			LIMIT %d, %d
SQL;
		return $this->get_results( $sql, $offset, $per_page );
	}
}
