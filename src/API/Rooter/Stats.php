<?php

namespace Tarosky\Common\API\Rooter;


use Tarosky\BasketBallKing\Master\LeagueMaster;
use Tarosky\BasketBallKing\Models\Matches;
use Tarosky\BasketBallKing\Statics\Leagues;
use Tarosky\Common\Models\Players;
use Tarosky\Common\Models\Replacements;
use Tarosky\Common\Models\TeamMaster;
use Tarosky\Common\Pattern\RooterBase;
use Tarosky\Common\Utility\Input;

/**
 * 統計情報を表示する
 *
 * @package Tarosky\Common\API\Rooter
 * @property-read Input $input
 * @property-read Matches $matches
 * @property-read Players $players
 * @property-read TeamMaster $team_master
 * @property-read Replacements $replacer
 */
class Stats extends RooterBase {

	/**
	 * @var array
	 */
	protected $rewrite = [
		'stats/match/([0-9]+)\.html/?$'           => 'index.php?stats=match&match_id=$matches[1]',
	    'stats/(japan|world)/([^/]+)/match/([0-9\\-]+)/([0-9]+)/?' => 'index.php?stats=match&abroad=$matches[1]&league=$matches[2]&season=$matches[3]&occasion=$matches[4]',
	    'stats/(japan|world)/([^/]+)/match/([0-9\\-]+)/?' => 'index.php?stats=match&abroad=$matches[1]&league=$matches[2]&season=$matches[3]',
		'stats/(japan|world)/([^/]+)/([^/]+)/?' => 'index.php?stats=$matches[3]&abroad=$matches[1]&league=$matches[2]',
	];

	/**
	 * @var array
	 */
	protected $query_vars = [ 'stats', 'match_id', 'abroad', 'league', 'season', 'date', 'occasion' ];

	/**
	 * @var \stdClass 現在の日程・結果
	 */
	protected $match = null;

	/**
	 * statsクエリが設定されている場合だけ
	 *
	 * @param \WP_Query $wp_query
	 *
	 * @return bool
	 */
	protected function test_query( $wp_query ) {
		return $wp_query->is_main_query() && ( $wp_query->get( 'stats' ) );
	}

	/**
	 * {@inheritDoc}
	 */
	protected function on_construct() {
		add_action( 'bcn_after_fill', [ $this, 'add_breadcrumb_trail' ] );
	}

	/**
	 * 画面を表示する
	 *
	 * @param \WP_Query $wp_query
	 */
	protected function process( &$wp_query ) {
		$abroad    = Leagues::abroad_slug( $wp_query->get( 'abroad' ) );
		$league_id = Leagues::get_league_id( $abroad, $wp_query->get( 'league' ) );
		switch ( $wp_query->get( 'stats' ) ) {
			case 'match':
				if ( $match_id = $wp_query->get( 'match_id' ) ) {
					// これはシングルマッチ
					if ( ! ( $match = $this->matches->get_match_by_id( $match_id ) ) ) {
						$wp_query->set_404();
						return;
					}
					// B2の試合は表示しない
					if ( ! LeagueMaster::is_available( $match->league_id ) ) {
                        $wp_query->set_404();
                        return;
                    }
					$this->match = $match;
					$this->load_template( 'single', 'match', [
						'match' => $match,
					] );
				} else {
					// これはマッチリスト
					if ( ! $league_id ) {
						$wp_query->set_404();
						return;
					} else {
					    // B2なら非表示
					    if ( ! LeagueMaster::is_available( $league_id ) ) {
                            $wp_query->set_404();
                            return;
                        }
						if ( ! ( $season = $wp_query->get( 'season' ) ) ) {
							// 期間が指定されていなかったら
						    $season = bk_current_season();
						}
						if ( ! ( $occasion = $wp_query->get( 'occasion' ) ) ) {
							// 期間が指定されていなかったら
							$occasion = $this->matches->get_nearest_occasion( 0, $league_id, $season );
							$ymd = $this->matches->get_nearest_ymd( $abroad, $league_id );
//							$matches = $this->matches->get_recent(0, $league_id );
						    $matches = $this->matches->search( '', 0, $league_id, '', '', '', $season, $occasion, null, 100 );
						} else {
						    $matches = $this->matches->search( '', 0, $league_id, '', '', '', $season, $occasion, null, 100 );
						    $ymd = $this->matches->get_start_date( $abroad, $league_id, $season, $occasion );
                        }

						//	急遽、データスタジアムのデータが変わったための例外処理
						//	チャンピオンシップで日程が未定のときでも順番を任意の順にする
						if( !empty( Leagues::$round_sort[$league_id] ) ) {
							$round_sort = Leagues::$round_sort[$league_id];
							$sort = [];
							$out = count($round_sort);
							foreach ( $matches as $m ) {
								$key = array_search($m->round, $round_sort);
								if( $key !== false ) {
									$sort[$key][] = $m;
								}
								else {
									$sort[$out][] = $m;
								}
							}
							ksort($sort);

							$matches = array();
							foreach( $sort as $sort_list ) {
								foreach( $sort_list as $s ) {
									$matches[] = $s;
								}
							}
						}

						$this->load_template( 'archive', 'match', [
							'league_id' => $league_id,
						    'abroad'    => $abroad,
						    'matches'   => $matches,
						    'season'    => $season,
						    'display_date'    => $ymd ,
						] );
					}
				}
				break;
			case 'player-result':
				if (! $league_id || ! ( $ranking = bk_get_player_result( $league_id ) ) || ! LeagueMaster::is_available( $league_id ) ) {
					$wp_query->set_404();
					return;
				}
				$this->load_template( 'single', 'goal', [
					'league_id' => $league_id,
					'abroad'    => $abroad,
					'ranking'   => $ranking,
				] );
				break;
			case 'rank':
				if ( ! $league_id || ! ( $ranking = bk_get_ranking( $league_id ) ) || ! LeagueMaster::is_available( $league_id ) ) {
					$wp_query->set_404();
					return;
                }
				$wc_ranking = bk_get_ranking( $league_id, '', true );
				$this->load_template( 'single', 'rank', [
					'league_id'  => $league_id,
					'abroad'     => $abroad,
				    'ranking'    => $ranking,
				    'wc_ranking'    => $wc_ranking,
				    'season'     => $wp_query->get( 'season' ),
				    'calculated' => (string) $ranking->RankReport->Updated,
				    'group_league' => Leagues::is_group_league( $abroad, $league_id ),
				] );
				break;
			case 'redirect':
				$abroad = intval( 'nk2' == $wp_query->get( 'abroad' ) );
				$league_id = $this->input->get( 'league' ) ?: ( $abroad ? '02' : '2' );
				$url = sk_stat_url( 'match', $abroad, $abroad ? '02' : '2' );
				if ( ! is_numeric( $league_id ) ) {
					$leagues = [
						'eng' => '02',
					    'ger' => '03',
					    'esp' => '04',
					    'ita' => '01',
						'cl' => '11',
						'el' => '12',
						'acl' => '15',
					];
					if ( isset( $leagues[ $league_id ] ) ) {
						$league_id = $leagues[ $league_id ];
					}
				}
				switch ( $wp_query->get( 'match_id' ) ) {
					case 'goal':
						$url = sk_stat_url( 'goal', 0, $league_id );
						break;
					case 'rank':
						$url = sk_stat_url( 'rank', 0, $league_id );
						break;
					case 'schedule':
						$segment = Leagues::get_url_segment( $abroad, $league_id );
						if ( $segment ) {
							$url = sk_stat_url( 'match', $abroad, $league_id );
						}
						break;
					case 'match':
						$block_id = $this->input->get( 'id' );
						$match = $this->matches->get_match( $abroad, $block_id );
						if ( $match ) {
							$url = sk_match_url( $match->id );
						}
						break;
					case 'player-page':
						$id = (int) $this->input->get('c');
						foreach ( get_posts( [
							'post_type' => 'player',
						    'post_status' => 'publish',
						    'posts_per_page' => 1,
						    'meta_query' => [
							    [
								    'key' => '_player_id',
							        'value' => $id,
							    ],
						    ],
						] ) as $post ) {
							$url = get_permalink( $post );
						}
						break;
					case 'player-team':
						$name = $this->input->get( 'n' );
						if ( $team = $this->players->get_team_by_name( $name ) ) {
							$url = get_permalink( $team );
						}
						break;
					default:
						// Do nothing
						break;
				}
				wp_redirect( $url, 301 );
				exit;
				break;
			default:
				$wp_query->set_404();
				return;
				break;
		}
	}

	/**
	 * タイトルを変更
	 *
	 * @param array $title
	 *
	 * @return array
	 */
	public function document_title_parts( $title ) {
		if ( isset( $title['tagline'] ) ) {
			unset( $title['tagline'] );
		}
		$extra_title = [];
		if ( $this->match ) {
			// これは日程詳細なので、タイトル変更
			$match = $this->matches->get_match_by_id( get_query_var( 'match_id' ) );
			if ( ! $match ) {
				return $title;
			}
			$extra_title['match'] = sk_match_title( $match );
			$extra_title['match_label'] = '試合詳細';
		} else {
			$extra_title['matc_label'] = sk_stats_title();
		}
		if ( ! emptY( $extra_title ) ) {
			$title = array_merge( $extra_title, $title );
		}

		return $title;
	}

	/**
	 * 日程・結果のページだったら、パンクズを設定する
	 *
	 * @param \bcn_breadcrumb_trail $bcn
	 * @return void
	 *
	 */
	public function add_breadcrumb_trail( \bcn_breadcrumb_trail $bcn ) {
		global $wp_query;
		if ( ! $this->test_query( $wp_query ) ) {
			// 日程・結果ではない。
			return;
		}
		$match_id = $wp_query->get( 'match_id' );
		if ( $match_id ) {
			$match  = $this->matches->get_match_by_id( $match_id );
			if ( ! $match ) {
				return;
			}
			// ルート
			$root = $bcn->trail[0];
			$bcn->trail = [ new \bcn_breadcrumb( $root->get_title(), null, [ 'home' ], $root->get_url(), 'home', true ) ];
			// リーグの有無で処理を分岐
			$league = sk_get_league_by_id( $match->league_id );
			if ( $league && $league->parent ) {
				// リーグがある場合はリーグ戦
				$parent = get_term_by( 'term_id', $league->parent, 'league' );
				// リーグを追加
				array_unshift( $bcn->trail, new \bcn_breadcrumb( $parent->name, null, [ 'league-archive' ], get_term_link( $parent ), 'league-' . $parent->term_id, true ) );
				array_unshift( $bcn->trail, new \bcn_breadcrumb( $league->name, null, [ 'league-archive' ], get_term_link( $league ), 'league-' . $league->term_id, true ) );
				// 日程結果へのリンクを追加
				$stats_base = sprintf( '/stats/%s/%s/match/', $parent->slug, $league->slug );
				array_unshift( $bcn->trail, new \bcn_breadcrumb( '日程・結果', null, [ 'stats-archive' ], home_url( $stats_base ), 'stats-league-' . $league->term_id, true ) );
			} else {
				// リーグがない場合はカップ戦
				$japan = get_term_by( 'slug', 'japan', 'league' );
				array_unshift( $bcn->trail, new \bcn_breadcrumb( $japan->name, null, [ 'term' ], get_term_link( $japan ), 'stats-league-' . $japan->term_id, true ) );
			}
			// 節へのリンクを追加
			$match_title = sprintf( '%s vs %s', get_the_title( $match->h_team->ID ), get_the_title( $match->a_team->ID ) );
			if ( $match->occasion ) {
				// リーグ戦
				$season_title = sprintf( '%sシーズン第%s節', $match->game_year, $match->occasion );
				$season_link  = sprintf( '%s%s/%s/', $stats_base, $match->game_year, $match->occasion );
				array_unshift( $bcn->trail, new \bcn_breadcrumb( $season_title, null, [ 'match-archive' ], home_url( $season_link ), str_replace( '/', '-', $season_link ), true ) );
			} else {
				// カップ戦
				$season_title = LeagueMaster::label( $match->league_id );
				$season_link  = home_url( sprintf( 'stats/japan/%s/match', Leagues::get_league_slug( '0', $match->league_id ) ) );
				array_unshift( $bcn->trail, new \bcn_breadcrumb( $season_title, null, [ 'match-archive' ], $season_link, str_replace( '/', '-', $season_link ), true ) );
				$match_title = $match->round . ' ' . $match_title;
			}
			// チームを追加
			$match_link  = home_url( sprintf( '/stats/match/%d.html', $match->id ) );
			array_unshift( $bcn->trail, new \bcn_breadcrumb( $match_title, null, [ 'match-single', 'current-item' ], $match_link, "match-{$match->id}", false ) );
		} else {
			$league = get_queried_object();
			if ( ! is_a( $league, 'WP_Term' ) ) {
				$japan = get_term_by( 'slug', 'japan', 'league' );
				array_unshift( $bcn->trail, new \bcn_breadcrumb( $japan->name, null, [ 'term' ], get_term_link( $japan ), 'stats-league-' . $japan->term_id, true ) );
				$slug = get_query_var( 'league' );
				$season_title = LeagueMaster::label( Leagues::get_league_id( '0', $slug ) );
				$season_link  = home_url( sprintf( 'stats/japan/%s/match', $slug ) );
				array_unshift( $bcn->trail, new \bcn_breadcrumb( $season_title, null, [ 'match-archive', 'current-item' ], $season_link, 'match-' . $slug, false ) );
			} else {
				// 通常のリーグ戦
				$parent = get_term_by( 'term_id', $league->parent, 'league' );
				// 最後のパンクズになっているリーグにリンクをする
				foreach ( $bcn->trail as $index => $trail ) {
					/* @var \bcn_breadcrumb $trail */
					$types = $trail->get_types();
					if ( in_array( 'current-item', $types, true ) ) {
						// これは最後のパンクズ
						$new_types = array_values( array_filter( $types, function( $type ) {
							return 'current-item' !== $type;
						} ) );
						$bcn->trail[ $index ] = new \bcn_breadcrumb( $trail->get_title(), null, $new_types, $trail->get_url(), $trail->get_id(), true );
					}
				}
				// 日程結果へのリンクを追加
				$linked     = false;
				$stats_base = sprintf( '/stats/%s/%s/match/', $parent->slug, $league->slug );
				$base_types = [ 'stats-archive' ];
				$new_title = '';
				$new_link  = '';
				if ( in_array( get_query_var( 'stats' ), [ 'player-result', 'rank' ] ) ) {
					$linked = true;
					$new_title = [
						'player-result' => '個人成績',
						'rank'          => '順位表',
					][ get_query_var( 'stats' ) ];
					$new_link = home_url( str_replace( '/match/', '/' . get_query_var( 'stats' ) . '/', $stats_base ) );
				} elseif ( get_query_var( 'season' ) ) {
					$linked = true;
					if ( get_query_var( 'occasion' ) ) {
						$new_title = sprintf( '%sシーズン第%s節', get_query_var( 'season' ), get_query_var( 'occasion' ) );
						$new_link  = home_url( sprintf( '%s%s/%s/', $stats_base, get_query_var( 'season' ), get_query_var( 'occasion' ) ) );
					} else {
						$new_title = sprintf( '%sシーズン', get_query_var( 'season' ) );
						$new_link  = home_url( sprintf( '%s%s/', $stats_base, get_query_var( 'season' ) ) );
					}
				} else {
					$base_types[] = 'current-item';
				}
				array_unshift( $bcn->trail, new \bcn_breadcrumb( '日程・結果', null, $base_types, home_url( $stats_base ), 'stats-league-' . $league->term_id, $linked ) );
				if ( $new_title ) {
					array_unshift( $bcn->trail, new \bcn_breadcrumb( $new_title, null, [ 'stats-archive', 'current-item' ], $new_link, 'stats-league-' . $league->term_id, false ) );
				}
			}
		}
	}

	/**
	 * ゲッター
	 *
	 * @param string $name
	 *
	 * @return mixed
	 */
	public function __get( $name ) {
		switch ( $name ) {
			case 'input':
				return Input::instance();
			case 'matches':
				return Matches::instance();
			case 'players':
				return Players::instance();
			case 'replacer':
				return Replacements::instance();
			case 'team_master':
				return TeamMaster::instance();
			default:
				return parent::__get( $name );
		}
	}
}
