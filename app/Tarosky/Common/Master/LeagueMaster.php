<?php

namespace Tarosky\Common\Master;


use Tarosky\Common\Pattern\MasterBase;

/**
 * Class LeagueMaster
 * @package sports-king
 */
class LeagueMaster extends MasterBase {

	/**
	 * リーグやカップの名称を返す
	 *
	 * @return string[]
	 */
	public static function league_name() {
		return static::$masters[ 'league_name' ] ?? [];
	}

	/**
	 * 契約の都合上表示できないリーグを返す
	 *
	 * @return int[]
	 * @todo 契約の内容が変更したら削除する
	 * @see https://tarosky.backlog.jp/view/BASKETBALL_KING-86#comment-1159776890
	 * @since 1.0.2
	 */
	public static function unavailable_leagues() {
		return static::$masters[ 'unavailable' ] ?? [];
	}

	/**
	 * @return int[]
	 */
	public static function ranking_leagues() {
		return static::$masters[ 'has_ranking' ] ?? [];
	}

	/**
	 * 表示できないリーグであるかどうか
	 *
	 * @param int $league_id
	 *
	 * @return bool
	 */
	public static function is_available( $league_id ) {
		$unavailables = self::unavailable_leagues();

		return false === array_search( $league_id, $unavailables );
	}

	/**
	 * 順位表があるリーグかどうか
	 *
	 * @param int $league_id
	 *
	 * @return bool
	 */
	public static function is_ranking( $league_id ) {
		$rankings = self::ranking_leagues();

		return false !== array_search( $league_id, $rankings );
	}


	/**
	 * リーグのラベルを取得する
	 *
	 * @param int $league_id
	 *
	 * @return string
	 */
	public static function label( $league_id ) {
		foreach ( self::league_name() as $id => $label ) {
			if ( $id === (int) $league_id ) {
				return $label;
			}
		}

		return '';
	}

	/**
	 * Get leagues
	 *
	 * @param bool $abroad If true, returns abroad league
	 *
	 * @return array
	 */
	public static function leagues( $abroad = false ) {
		$key = $abroad ? 'abroad' : 'domestic';

		return isset( self::$masters[ $key ] ) ? self::$masters[ $key ] : [];
	}

	/**
	 * 海外リーグかどうか
	 *
	 * @param int $league_id League ID
	 *
	 * @return bool
	 */
	public static function is_abroad_league( $league_id ) {
		return array_key_exists( (int) $league_id, self::$masters[ 'abroad' ] );
	}
}
