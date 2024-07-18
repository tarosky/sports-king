<?php

namespace Tarosky\Common\Statics;


use Tarosky\Common\Pattern\ConstantHolder;

/**
 * リーグ情報の定数
 *
 * @package Tarosky\Common\Statics
 */
class Leagues extends ConstantHolder {

	const ABROAD = array(
		'0' => 'japan',
		'1' => 'world',
	);

	const SEASONS = array(
		1  => '年間シーズン',
		2  => '1stステージ',
		3  => '2ndステージ',
		4  => '予選リーグ',
		5  => '決勝トーナメント',
		6  => '順位決定戦',
		7  => '2次予選',
		8  => '最終予選',
		9  => '1次予選',
		10 => 'ポストシーズン',
		11 => 'アジア最終予選',
		12 => '3次予選',
	);

	const LEAGUES = array(
		'0' => array(
			'2'  => array(
				'slug'  => 'b1',
				'label' => 'B1',
			),
			'7'  => array(
				'slug'  => 'b2',
				'label' => 'B2',
			),
			'3'  => array(
				'slug'  => 'championship',
				'label' => 'チャンピオンシップ',
			),
			'4'  => array(
				'slug'  => 'b1-play-off',
				'label' => 'B1残留プレーオフ',
			),
			'8'  => array(
				'slug'  => 'b2-play-off',
				'label' => 'B2プレーオフ',
			),
			'11' => array(
				'slug'  => 'relegation',
				'label' => '入替戦',
			),
			'20' => array(
				'slug'  => 'early-kanto',
				'label' => 'アーリーカップ関東',
			),
			'21' => array(
				'slug'  => 'early-kansai',
				'label' => 'アーリーカップ関西',
			),
			'23' => array(
				'slug'  => 'early-tohoku',
				'label' => 'アーリーカップ東北',
			),
			'24' => array(
				'slug'  => 'early-tokai',
				'label' => 'アーリーカップ東海',
			),
			'25' => array(
				'slug'  => 'early-hokushinetsu',
				'label' => 'アーリーカップ北信越',
			),
			'26' => array(
				'slug'  => 'early-nishinihon',
				'label' => 'アーリーカップ西日本',
			),
		),
		'1' => array(),
	);

	/**
	 * @var array 降格・昇格のライン
	 */
	public static $lines = array(
		'cl'      => array(
			'color' => 'green',
			'label' => 'CL圏内',
		),
		'el'      => array(
			'color' => 'blue',
			'label' => 'EL圏内',
		),
		'dropoff' => array(
			'color' => 'greylight',
			'label' => '降格プレーオフ',
		),
		'drop'    => array(
			'color' => 'grey',
			'label' => '降格圏内',
		),
		'playoff' => array(
			'color' => 'blue',
			'label' => 'プレーオフ',
		),
		'raise'   => array(
			'color' => 'green',
			'label' => '昇格圏内',
		),
		'cs'      => array(
			'color' => 'green',
			'label' => 'B.LEAGUE CHAMPIONSHIP %d-%d 出場圏内 ',
		),
		'stayoff' => array(
			'color' => 'grey',
			'label' => 'B1 残留プレーオフ %d-%d 出場圏内',
		),
	);

	/**
	 *
	 */
	public static $round_sort = array(
		'3' => array(
			'クォーターファイナル１試合目',
			'クォーターファイナル1試合目',
			'クォーターファイナル2試合目',
			'クォーターファイナル3試合目',
			'セミファイナル1試合目',
			'セミファイナル2試合目',
			'セミファイナル3試合目',
			'ファイナル',
		),
	);

	/**
	 * @var array シーズン表示を日毎単位にさせる対象リーグ
	 */
	public static $season_to_daily_list = array(
		'euro' => array( 5, 9 ),
	);

	/**
	 * スラッグを海外か否かに帰る
	 *
	 * @param string $slug
	 *
	 * @return false|string
	 */
	public static function abroad_slug( $slug ) {
		if ( false !== ( $index = array_search( $slug, self::ABROAD ) ) ) {
			return $index;
		} else {
			return false;
		}
	}

	/**
	 * リーグIDを取得する
	 *
	 * @param string $abroad
	 * @param string $slug
	 *
	 * @return bool
	 */
	public static function get_league_id( $abroad, $slug ) {
		$abroads = self::ABROAD;
		if ( ! isset( $abroads[ $abroad ] ) ) {
			return false;
		}
		$leagues = self::LEAGUES;
		foreach ( $leagues[ $abroad ] as $id => $labels ) {
			if ( $slug == $labels['slug'] ) {
				return $id;
			}
		}

		return false;
	}

	/**
	 * リーグIDからSLUGを取得する
	 *
	 * @param string $abroad
	 * @param string $league_id
	 *
	 * @return bool
	 */
	public static function get_league_slug( $abroad, $league_id ) {
		$league_ids = self::LEAGUES;
		$abroads    = self::ABROAD;
		$abroad     = (int) $abroad;

		return isset( $league_ids[ $abroad ][ $league_id ] )
			? $league_ids[ $abroad ][ $league_id ]['slug']
			: false;
	}

	/**
	 * リーグIDからリーグ内容を取得する
	 *
	 * @param string $abroad
	 * @param string $league_id
	 *
	 * @return bool
	 */
	public static function get_league( $abroad, $league_id ) {
		$league_ids = self::LEAGUES;
		$abroads    = self::ABROAD;
		$abroad     = (int) $abroad;

		return isset( $league_ids[ $abroad ][ $league_id ] )
			? $league_ids[ $abroad ][ $league_id ]
			: false;
	}

	/**
	 * リーグIDからURLを取得する
	 *
	 * @param string $abroad
	 * @param string $league_id
	 *
	 * @return bool
	 */
	public static function get_url_segment( $abroad, $league_id ) {
		$league_ids = self::LEAGUES;
		$abroads    = self::ABROAD;
		$abroad     = (int) $abroad;

		return isset( $league_ids[ $abroad ][ $league_id ] )
			? "/{$abroads[$abroad]}/{$league_ids[ $abroad ][ $league_id ]['slug']}/"
			: false;
	}

	/**
	 * シーズンの期間を返す
	 *
	 * @param bool $abroad
	 *
	 * @return array
	 */
	public static function get_range( $abroad ) {
		if ( $abroad ) {
			if ( date_i18n( 'n' ) < 7 ) {
				return array(
					date_i18n( 'Y-07-01', current_time( 'timestamp' ) - ( 60 * 60 * 24 * 365 ) ),
					date_i18n( 'Y-06-31' ),
				);
			} else {
				return array(
					date_i18n( 'Y-07-01' ),
					date_i18n( 'Y-06-31', current_time( 'timestamp' ) + ( 60 * 60 * 24 * 365 ) ),
				);
			}
		} else {
			return array( date_i18n( 'Y-01-01' ), date_i18n( 'Y-12-31' ) );
		}
	}

	/**
	 * リーグでの降格・昇格ラインを取得する
	 *
	 * @param string $rank
	 * @param bool $abroad
	 * @param string $league_id
	 *
	 * @return array
	 */
	public static function get_league_status( $rank, $abroad, $league_id, $season = '', $tab = '' ) {
		if ( $abroad ) {
			switch ( $league_id ) {
				case '02': // プレミア
					if ( 5 > $rank ) {
						return self::$lines['cl'];
					} elseif ( 5 == $rank ) {
						return self::$lines['el'];
					} elseif ( 17 < $rank ) {
						return self::$lines['drop'];
					} else {
						return array();
					}
					break;
				case '03': // ブンデス
					if ( 5 > $rank ) {
						return self::$lines['cl'];
					} elseif ( 7 > $rank ) {
						return self::$lines['el'];
					} elseif ( 16 == $rank ) {
						return self::$lines['dropoff'];
					} elseif ( 16 < $rank ) {
						return self::$lines['drop'];
					} else {
						return array();
					}
					break;
				case '04': // エスパニョーラ
					if ( 5 > $rank ) {
						return self::$lines['cl'];
					} elseif ( 7 > $rank ) {
						return self::$lines['el'];
					} elseif ( 17 < $rank ) {
						return self::$lines['drop'];
					} else {
						return array();
					}
					break;
				case '01': // セリエA
					if ( 4 > $rank ) {
						return self::$lines['cl'];
					} elseif ( 6 > $rank ) {
						return self::$lines['el'];
					} elseif ( 17 < $rank ) {
						return self::$lines['drop'];
					} else {
						return array();
					}
					break;
				default:
					return array();
					break;
			}
		} else {
			switch ( $league_id ) {
				case 2:
					if ( $rank <= 2 ) {
						return self::$lines['cs'];
					}
					if ( $tab === 'wc' ) {
						if ( $rank > 8 ) {
							return self::$lines['stayoff'];
						}
					}
					break;
				case 6:
					if ( $rank < 3 ) {
						return self::$lines['raise'];
					} elseif ( $rank < 7 ) {
						return self::$lines['playoff'];
					} elseif ( $rank > 20 ) {
						return self::$lines['drop'];
					}
					break;
				case 68:
					if ( $rank < 3 ) {
						return self::$lines['playoff'];
					}
					break;
			}
		}

		return array();
	}

	/**
	 * リーグの判例を表示する
	 *
	 * @param string $abroad
	 * @param string $league_id
	 * @param string $season
	 *
	 * @return array
	 */
	public static function get_league_label( $abroad, $league_id, $season = '' ) {
		$arrays = self::$lines;
		if ( $abroad ) {
			switch ( $league_id ) {
				case '01':
				case '02':
				case '03':
				case '04':
					return array(
						'cl'      => $arrays['cl'],
						'el'      => $arrays['el'],
						'dropoff' => $arrays['dropoff'],
						'drop'    => $arrays['drop'],
					);
					break;
			}
		} else {
			switch ( $league_id ) {
				case 2:
					$cs      = $arrays['cs'];
					$stayoff = $arrays['stayoff'];

					$y             = date_i18n( 'Y' );
					$m             = date_i18n( 'm' );
					$before_season = intval( $y );
					$after_season  = ( intval( $y ) - 2000 ) + 1;
					if ( 1 <= intval( $m ) && intval( $m ) <= 5 ) :
						$before_season -= 1;
						$after_season  -= 1;
					endif;
					$cs['label']      = sprintf( $cs['label'], $before_season, $after_season );
					$stayoff['label'] = sprintf( $stayoff['label'], $before_season, $after_season );

					return array(
						'cs'      => $cs,
						'stayoff' => $stayoff,
					);
					break;
				case 6:
					return array(
						'raise'   => $arrays['raise'],
						'playoff' => $arrays['playoff'],
						'drop'    => $arrays['drop'],
					);
					break;
				case 68:
					return array(
						'playoff' => $arrays['playoff'],
						'drop'    => $arrays['drop'],
					);
					break;
			}
		}

		return array();
	}

	/**
	 * グループリーグか否か
	 *
	 * @param bool $abroad
	 * @param string $league_id
	 *
	 * @return bool
	 */
	public static function is_group_league( $abroad, $league_id ) {
		if ( $abroad ) {
			return false === array_search( $league_id, array( '01', '02', '03', '04' ) );
		} else {
			return false === array_search( $league_id, array( '2', '6', '68' ) );
		}
	}

	/**
	 * シーズンIDを口語に直す
	 *
	 * @param int $season_id
	 *
	 * @return string
	 */
	public static function season_label( $season_id ) {
		$labels = self::SEASONS;
		if ( isset( $labels[ $season_id ] ) ) {
			return $labels[ $season_id ];
		} else {
			return '';
		}
	}

	/**
	 * ラベルからシーズンIDを返す
	 *
	 * @param string $label
	 *
	 * @return int|mixed
	 */
	public static function get_season_id( $label ) {
		$labels = self::SEASONS;
		$id     = array_search( $label, $labels );
		if ( false === $id ) {
			if ( false !== mb_strpos( $label, '決勝', 0, 'utf-8' ) ) {
				return 5;
			} else {
				switch ( $label ) {
					case '1次リーグ':
						return 9;
						break;
					case '最終リーグ':
						return 8;
						break;
					case '3次リーグ':
						return 12;
						break;
					default:
						return 1;
						break;
				}
			}
		} else {
			return $id;
		}
	}

	/**
	 * シーズン表示を日毎単位にさせるリーグのスラッグ一覧を返す
	 *
	 * @return array
	 */
	public function get_season_to_daily_leagues() {
		return self::$season_to_daily_list;
	}
}
