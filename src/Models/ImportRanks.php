<?php

namespace Tarosky\Common\Models;


use Tarosky\Common\Pattern\Model;

/**
 * インポートしたランキング
 *
 * @package Tarosky\Common\Models
 */
class ImportRanks extends Model {

	protected $version = '1.0';

	protected $name = 'sk_imported_rank';

	protected $primary_key = 'id';

	protected $updated_column = 'updated';

	protected $default_placeholder = [
		'id'       => '%d',
		'key'      => '%s',
		'league'   => '%s',
		'suffix'   => '%s',
		'file'     => '%s',
		'contents' => '%s',
		'updated'  => '%s',
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
			`league` VARCHAR(48) NOT NULL,
			`suffix` VARCHAR(256) NOT NULL,
			`file` VARCHAR(256) NOT NULL,
			`contents` LONGTEXT NOT NULL,
			`updated` DATETIME NOT NULL,
			PRIMARY KEY (id),
			INDEX key_name(`key`, `league`)
		) ENGINE=InnoDB DEFAULT CHARSET=utf8
SQL;
	}

	/**
	 * セーブする
	 *
	 * @param string $key
	 * @param string $name
	 * @param string $suffix
	 * @param string $contents
	 *
	 * @return false|int
	 */
	public function save( $key, $name, $suffix, $file, $contents = '', $updated = '' ) {
		$values = [
			'key'      => $key,
			'league'   => $name,
			'suffix'   => $suffix,
			'file'     => $file,
			'contents' => $contents,
		];
		if ( $updated ) {
			$values['updated'] = $updated;
		}

		return $this->insert( $values );
	}

	/**
	 * 存在をチェック
	 *
	 * @param string $key
	 * @param string $file
	 *
	 * @return bool
	 */
	public function exist( $key, $file ) {
		$query = <<<SQL
			SELECT * FROM {$this->table}
			WHERE `key` = %s AND `file` = %s
SQL;

		return $this->get_row( $query, $key, $file );
	}

	/**
	 * 最新のランキングを取得する
	 *
	 * @param string $key
	 * @param string $league
	 *
	 * @return null|string
	 */
	public function get_latest_rank( $key, $league ) {
		$query = <<<SQL
			SELECT contents FROM {$this->table}
			WHERE `key` = %s AND `league` = %s
			ORDER BY updated DESC
			LIMIT 1
SQL;
		return $this->get_var( $query, $key, $league );
	}

	/**
	 * 最新のデータの日付を取得する
	 *
	 * @param string $key
	 * @param string $league
	 *
	 * @return null|string
	 */
	public function get_latest_rank_date( $key, $league ) {
		$query = <<<SQL
			SELECT CAST(updated AS DATE) AS updated FROM {$this->table}
			WHERE `key` = %s AND `league` = %s
			ORDER BY updated DESC
			LIMIT 1
SQL;
		return $this->get_var( $query, $key, $league );
	}

	/**
	 * グループリーグのランキングを取得する
	 *
	 * @param string $key
	 * @param string $league
	 *
	 * @return array
	 */
	public function get_group_rank( $key, $league ) {
		$query = <<<SQL
			SELECT * FROM (
				SELECT * FROM {$this->table}
				WHERE `key` = %s AND `league` = %s
				ORDER BY `updated` DESC
		  	) AS sk
			GROUP BY `suffix`
SQL;
		return $this->get_results( $query, $key, $league );
	}


	public function get_abroad_ranking( $league_id ) {
		$path = ABSPATH."NK2/data/kyodonews.jp/{$league_id}/39";
		if ( ! is_dir( $path ) ) {
			return null;
		}
		$dates = [];
		foreach ( scandir( $path ) as $file ) {
			if ( 0 === strpos( $file, '.' ) || ! preg_match( '#\.xml$#', $file ) ) {
				continue;
			}
			if ( preg_match( '#^(\\d{4}\\d{2}\\d{2})#', $file, $match ) ) {
				if ( ! isset( $dates[$match[1]] ) ) {
					$dates[$match[1]] = [];
				}
				$dates[$match[1]][] = $file;
			}
		}
		krsort($dates);
		foreach ( $dates as $date => $files ) {
			$file_to_check = [ ];
			foreach ( $files as $file ) {
				if ( preg_match( '#([a-zA-Z0-9])(\\d{3})_UTF8\.xml$#', $file, $match ) ) {
					$file_to_check[ $match[1] ] = $file;
				}
			}
			if ( 1 < count( $file_to_check ) ) {
				// グループリーグ
				$rank = [ ];
				foreach ( $file_to_check as $xml_file ) {
					if ( $xml = simplexml_load_file( $path . '/' . $xml_file ) ) {
						$body = $xml->NewsItem->NewsComponent->ContentItem->DataContent->InContent->InData->SportsData->Body;
						$key  = (string) $body->Meta->Phase . (string) $body->Meta->Heat;
						$rs   = [ ];
						foreach ( $body->Standing->Team as $team ) {
							$name = (string) $team->Name->Formal[0];
							$post = sk_get_team_by_name( $name );
							if ( ! $post || ! ( $name_s = sk_meta( '_team_short_name', $post ) ) ) {
								$name_s = mb_substr( $name, 0, 8, 'utf-8' );
							}
							$r    = [
								'group'     => '',
								'rank'      => sk_hankaku( $team->Result->Rank ),
								'id'        => (int) $team->attributes()->TeamId,
								'name'      => $name_s,
								'name_long' => $name,
								'point'     => sk_hankaku( $team->Result->OutcomeTotal->WinningPoint ),
								'game'      => sk_hankaku( $team->Result->OutcomeTotal->MatchCount ),
								'win'       => sk_hankaku( $team->Result->OutcomeTotal->WinCount ),
								'lose'      => sk_hankaku( $team->Result->OutcomeTotal->LossCount ),
								'draw'      => sk_hankaku( $team->Result->OutcomeTotal->TieCount ),
								'score'     => sk_hankaku( $team->Result->OutcomeTotal->TotalScore ),
								'lost'      => sk_hankaku( $team->Result->OutcomeTotal->TotalScoreOpposing ),
								'differ'    => sk_hankaku( $team->Result->OutcomeTotal->GoalDifference ),
							];
							$rs[] = $r;
						}
						$rank[ $key ] = $rs;
					}
				}

				return $rank;
			} else {
				if ( $xml = simplexml_load_file( $path . '/' . current( $file_to_check ) ) ) {
					$rank = [];
					foreach ( $xml->NewsItem->NewsComponent->ContentItem->DataContent->InContent->InData->SportsData->Body->Standing->Team as $team ) {
						$name = (string) $team->Name->Formal[0];
						$post = sk_get_team_by_name( $name );
						if ( ! $post || ! ( $name_s = sk_meta( '_team_short_name', $post ) ) ) {
							$name_s = mb_substr( $name, 0, 8, 'utf-8' );
						}
						$r      = [
							'group'     => '',
							'rank'      => sk_hankaku( $team->Result->Rank ),
							'id'        => (int) $team->attributes()->TeamId,
							'name'      => $name_s,
							'name_long' => $name,
							'point'     => sk_hankaku( $team->Result->OutcomeTotal->WinningPoint ),
							'game'      => sk_hankaku( $team->Result->OutcomeTotal->MatchCount ),
							'win'       => sk_hankaku( $team->Result->OutcomeTotal->WinCount ),
							'lose'      => sk_hankaku( $team->Result->OutcomeTotal->LossCount ),
							'draw'      => sk_hankaku( $team->Result->OutcomeTotal->TieCount ),
							'score'     => sk_hankaku( $team->Result->OutcomeTotal->TotalScore ),
							'lost'      => sk_hankaku( $team->Result->OutcomeTotal->TotalScoreOpposing ),
							'differ'    => sk_hankaku( $team->Result->OutcomeTotal->GoalDifference ),
						];
						$rank[] = $r;
					}

					return [ $rank ];
				}
			}
		}
	}

	/**
	 * 最新のリーグ別ランキングを取得する
	 *
	 * @param bool $only_date 日付だけ取得したい場合はtrue
	 * @return array
	 */
	public function get_national_ranking ( $only_date = false ) {
		$root = ABSPATH.'NK/data';
		$cur_dir = [];
		foreach ( scandir( $root ) as $dir ) {
			if ( preg_match( '#[0-9]{4}#', $dir ) && is_dir( $root.'/'.$dir ) ) {
				$cur_dir[] = $dir;
			}
		}
		if ( ! $cur_dir ) {
			return [];
		}
		rsort( $cur_dir );
		$dir = $root.'/'.$cur_dir[0].'/real';
		if ( ! is_dir( $dir ) ) {
			return [];
		}
		$rankings = [];
		$replacer = Replacements::instance();
		$team_master = TeamMaster::instance();
		foreach ( scandir( $dir ) as $file ) {
			if ( preg_match( '#^rank_team-([0-9]+)(-[0-9]*)?\.xml$#' , $file, $match ) ) {
				$xml = simplexml_load_file( $dir . '/' . $file );
				if ( $xml ) {
					$date = (string) $xml->RankReport->GameDate;
					if ( $only_date ) {
						$date_formed = preg_replace( '#(\\d{4})(\\d{2})(\\d{2})#', '$1-$2-$3', $date );
						if ( isset( $match[2] ) ) {
							$rankings[ $match[1] . $match[2] ] = $date_formed;
						} else {
							$rankings[ $match[1] ] = $date_formed;
						}
					} else {
						$rank = [];
						if ( 'rank_team-2-3.xml' == $file && ( date_i18n( 'n' ) < 7 ) && substr( $date, 0, 4 ) < date_i18n( 'Y' ) ) {
							// J1の場合、混乱を招くので、去年の2ndシーズンのデータは出さない
							$rankings['2-3'] = [];
							continue;
						}
						foreach ( $xml->RankReport->RankInfo as $info ) {
							$master = $team_master->get( (int) $info->TeamID );
							if ( $master ) {
								$name = sk_hankaku( $master->name );
							} else {
								$name = (string) $info->TeamNameS;
							}
							$rank[] = [
								'group'     => (string) $info->GroupID,
								'rank'      => (int) $info->attributes()['Ranking'],
								'id'        => (int) $info->TeamID,
								'name'      => (string) $info->TeamNameS,
								'name_long' => $replacer->normalize( 'team', $name ),
								'point'     => (int) $info->Point,
								'game'      => (int) $info->Game,
								'win'       => (int) $info->Win,
								'lose'      => (int) $info->Lose,
								'draw'      => (int) $info->Draw,
								'score'     => (int) $info->Score,
								'lost'      => (int) $info->Lost,
								'differ'    => (int) $info->Differ,
							];
						}
						if ( isset( $match[2] ) ) {
							$rankings[ $match[1] . $match[2] ] = $rank;
						} else {
							$rankings[ $match[1] ] = $rank;
						}
					}
				}
			}
		}
		return $rankings ;
	}

	/**
	 * @param $rankings
	 *
	 * @return array
	 */
	public function get_j1_season_ranking( $rankings ) {
		$new_rank = [];
		foreach ( [ '2-2', '2-3' ] as $slug ) {
			if ( ! isset( $rankings[ $slug ] ) ) {
				continue;
			}
			foreach ( $rankings[ $slug ] as $rank ) {
				if ( ! isset( $new_rank[ $rank['id'] ] ) ) {
					$new_rank[ $rank['id'] ] = $rank;
				} else {
					foreach ( $rank as $key => $val ) {
						if ( false !== array_search( $key, [
								'point',
								'game',
								'win',
								'lose',
								'draw',
								'score',
								'lost',
								'differ',
							] )
						) {
							$new_rank[ $rank['id'] ][ $key ] += $val;
						}
					}
				}
			}
		}
		usort( $new_rank, function ( $a, $b ) {
			// 勝点で判定
			if ( $a['point'] == $b['point'] ) {
				// 勝点同率なら得失点差
				if ( $a['differ'] == $b['differ'] ) {
					// 得失点差も同じなら総得点
					if ( $a['score'] == $b['score'] ) {
						return 0;
					} else {
						return $a['score'] > $b['score'] ? - 1 : 1;
					}
				} else {
					return $a['differ'] > $b['differ'] ? -1 : 1;
				}
			} else {
				return $a['point'] > $b['point'] ? - 1 : 1;
			}
		} );
		foreach ( $new_rank as &$rank ) {
			$bigger = 0;
			foreach ( $new_rank as $r ) {
				if ( $r['point'] > $rank['point'] ) {
					$bigger++;
				}elseif( $r['point'] == $rank['point'] ){
					// 勝点同じ
					if ( $r['differ'] > $rank['differ']) {
						$bigger++;
					}elseif( $r['differ'] == $rank['differ'] ){
						if( $r['score'] > $rank['score'] ){
							$bigger++;
						}
					}
				}
			}
			$rank['rank'] = $bigger + 1;
		}
		return $new_rank;
	}

}
