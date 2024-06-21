<?php
/**
 * ランキング関連の関数
 */


/**
 * ランキングファイルを取得する
 *
 * @param int    $league_id
 * @param string $year
 * @return null|SimpleXMLElement
 */
function sk_get_ranking( $league_id, $year = '', $is_wildcard = false ) {
	$file = "rank_team-{$league_id}.xml";
	if( $is_wildcard ) {
		$file = "rank_team_wc-{$league_id}.xml";
	}
	$path = sk_get_total_file_path( $file, 'team', $year );
	$xml = sk_get_total_file( $file, 'team', $year );
	if ( $xml ) {
		$xml->RankReport->Updated = date_i18n( 'Y-m-d H:i:s', filemtime( $path ) );
	}
	return $xml;
}

/**
 * 個人成績のファイルを取得する
 *
 * @todo バスケットに固有の値があるので、そのまま使えない
 * @param int|string $league_id リーグID
 * @param string     $year      シーズン年
 * @return array
 */
function sk_get_player_result( $league_id, $year = '' ) {
	$file = "rank_stats-{$league_id}.xml";
	$path = sk_get_total_file_path( $file, 'player', $year );
	$xml = sk_get_total_file( $file, 'player', $year );
	$time = null;
	if( file_exists($path) ) {
		$time = filemtime( $path );
	}
	$rankings = [
		'updated'  => date_i18n( 'Y-m-d H:i:s', $time ),
		'categories' => [],
	];
	$ids = [
		'player' => [],
		'team'   => [],
	];
	// 取得すべきデータだけ先にとる
	if( $xml ) {
		foreach ( $xml->RankStats->RankCategory->RankInfo as $ranker ) {
			$player_id = (int) $ranker->PlayerID;
			if ( ! isset( $ids['player'][$player_id] ) ) {
				$ids['player'][$player_id] = null;
			}
			$team_id = (int) $ranker->TeamID;
			if ( ! isset( $ids['team'][$team_id] ) ) {
				$ids['team'][$team_id] = null;
			}
		}
	}
	// 投稿を取得する
	foreach ( $ids as $post_type => $id ) {
		foreach ( get_posts( [
			'post_type' => $post_type,
			'post_status' => 'publish',
			'posts_per_page' => -1,
			'meta_query' => [
				[
					'key' => "_{$post_type}_id",
					'value' => array_keys( $id ),
					'compare' => 'IN',
				]
			],
		] ) as $post ) {
			$id = get_post_meta( $post->ID, "_{$post->post_type}_id", true );
			$ids[$post_type][$id] = $post;
		}
	}
	// データを取得
	if( $xml ) {
		foreach ( $xml->RankStats->RankCategory as $category ) {
			// ラベルを決定
			$cat_cd = (int) $category->CategoryCD;
			switch ( $cat_cd ) {
				case 10:
					$cat_name = '平均得点';
					break;
				case 12:
					$cat_name = '平均アシスト数';
					break;
				case 14:
					$cat_name = '平均リバウンド';
					break;
				case 16:
					$cat_name = '平均スティール数';
					break;
				case 18:
					$cat_name = '平均ブロック数';
					break;
				case 22:
					$cat_name = 'フリースロー成功率';
					break;
				case 26:
					$cat_name = '3P成功率';
					break;
				default:
					continue 2;
					break;
			}
			// 取得するキーを決定
			$field = 2 == (int) $category->DisplayType ? 'SuccessRate' : 'AverageCount';
			$rank = [];
			$i = 0;
			foreach ( $category->RankInfo as $ranker) {
				$i++;
				$player_id = (int) $ranker->PlayerID;
				$team_id   = (int) $ranker->TeamID;
				$rank[] = [
					'rank' => (int) $ranker->Rank,
					'category' => $cat_cd,
					'team_id' => $team_id,
					'team' => $ids['team'][$team_id],
					'team_name' => (string) $ranker->TeamName,
					'player_id' => $player_id,
					'player' => $ids['player'][$player_id],
					'player_name' => (string) $ranker->PlayerName,
					'count' => (string) $ranker->{$field},
				];
				if ( 30 <= $i ) {
					break;
				}
			}
			$rankings['categories'][$cat_name] = $rank;
		}
	}

	return $rankings;
}
