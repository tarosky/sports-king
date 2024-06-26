<?php
/**
 * 日程・結果関連のファイル
 */

/**
 * 日程・結果のタイトルを取得する
 *
 * @return string
 */
function sk_stats_title() {
	$sep      = apply_filters( 'document_title_separator', '-' );
	$match_id = get_query_var( 'match_id' );
	if ( $match_id ) {
		$match = \Tarosky\Common\Tarosky\Common\Models\Matches::instance()->get_match_by_id( $match_id );
		if ( ! $match ) {
			return '試合詳細';
		}
		return sk_match_title( $match );
	} else {
		$abroad      = ( 'japan' === get_query_var( 'abroad' ) ) ? 0 : 1;
		$league_id   = \Tarosky\Common\Tarosky\Common\Statics\Leagues::get_league_id( $abroad, get_query_var( 'league' ) );
		$league      = \Tarosky\Common\Tarosky\Common\Master\LeagueMaster::label( $league_id );
		$extra_title = [];
		switch ( get_query_var( 'stats' ) ) {
			case 'player-result':
				$extra_title[] = sprintf( '%sの個人成績', $league );
				break;
			case 'rank':
				$extra_title[] = sprintf( '%sの順位表', $league );
				break;
			default:
				$detail = $league;
				$season = get_query_var( 'season' );
				if ( $season ) {
					$detail .= sprintf( ' %sシーズン', $season );
				}
				$occasion = get_query_var( 'occasion' );
				if ( $occasion ) {
					$detail .= sprintf( ' 第%s節', $occasion );
				}
				$detail .= 'の日程・結果';
				$extra_title[] = $detail;
				break;
		}
		// todo: Bリーグで決めうちになっている
		if ( ! \Tarosky\Common\Tarosky\Common\Master\LeagueMaster::is_abroad_league( get_query_var( 'league' ) ) ) {
			array_unshift( $extra_title, 'Bリーグ' );
		}
		return implode( " {$sep} ", $extra_title );
	}
}

/**
 * 試合詳細オブジェクトのタイトルを取得する
 *
 * @param \stdClass $match 試合詳細オブジェクト
 * @return string
 */
function sk_match_title( $match ) {
	$sep    = apply_filters( 'document_title_separator', '-' );
	$versus = sprintf( '%s vs %s', get_the_title( $match->h_team->ID ), get_the_title( $match->a_team->ID ) );
	if ( $match->occasion ) {
		$name = sprintf( '%sシーズン第%s節', $match->game_year, $match->occasion );
	} else {
		$name = $match->round;
	}
	$league = sk_get_league_by_id( $match->league_id );
	if ( $league ) {
		$name = $league->name . ' ' . $name;
	} else {
		//リーグがない（カップ戦）
		$name = \Tarosky\Common\Tarosky\Common\Master\LeagueMaster::label( $match->league_id );
	}
	$titles = [ $name, $versus ];
	// todo: Bリーグで決めうちになっている
	if ( ! \Tarosky\Common\Tarosky\Common\Master\LeagueMaster::is_abroad_league( $match->league_id ) ) {
		array_unshift( $titles, 'Bリーグ' );
	}
	return implode( " {$sep} ", $titles );
}

/**
 * 統計情報のURLを取得する
 *
 * @param $type
 * @param $abroad
 * @param $league_id
 * @param string $occasion
 *
 * @return bool|string|void
 */
function sk_stat_url( $type, $abroad, $league_id, $season = false, $occasion = '' ) {
	$segment = \Tarosky\Common\Tarosky\Common\Statics\Leagues::get_url_segment( $abroad, $league_id );
	$segment = "/stats{$segment}{$type}";
	if ( $season ) {
		$segment = untrailingslashit( $segment ) . "/{$season}/";
	}
	if ( $occasion ) {
		$segment = untrailingslashit( $segment ) . "/{$occasion}/";
	}

	return $segment ? home_url( $segment ) : false;
}

/**
 * 日程結果のURLを返す
 *
 * @param int $id
 *
 * @return string
 */
function sk_match_url( $id ) {
	return home_url( "/stats/match/{$id}.html" );
}

/**
 * 今年のシーズン年を返す
 *
 * @return string
 */
function sk_current_season() {
	$year = (int) date_i18n( 'Y' );
	if ( 9 > date_i18n( 'n' ) ) {
		$year--;
	}
	return $year;
}


