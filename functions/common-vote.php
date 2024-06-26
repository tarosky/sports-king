<?php
/**
 * 投票関係のテンプレートタグ
 */

/**
 * 日付がどんな形式であってもいい感じにする
 *
 * @param string $key
 * @param null $post
 *
 * @return string
 */
function sk_vote_time( $key, $post = null ) {
	$post  = get_post( $post );
	$value = sk_meta( $key, $post );

	return $value ? substr( $value . '0000', 0, 14 ) : '';
}

/**
 * 投票の選択肢を返す
 *
 * @param null|int|WP_Post $post
 *
 * @return array
 */
function sk_vote_selection( $post = null ) {
	$post = get_post( $post );
	if ( ! ( $selection = sk_meta( 'choice', $post ) ) ) {
		return [];
	}
	return preg_split( "#(\r)?\n#", trim( $selection ) );
}

/**
 * 投票群の取得
 */
function sk_vote_list( $post = null ) {
	$post = get_post( $post );
	if( function_exists('tscf_repeat_field') ) {
		if( ! ($vote_list = tscf_repeat_field('vote_list', $post) ) ) {
			return [];
		}
	}
	return $vote_list;
}

/**
 * 投票可能か
 *
 * @param null|int|WP_Post $post
 *
 * @return bool
 */
function sk_voting( $post = null ) {
	$post  = get_post( $post );
	$start = sk_vote_time( 'vote_from', $post );
	$end   = sk_vote_time( 'vote_until', $post );
	$now   = date_i18n( 'YmdHis' );

	return $start && $end && $start < $now && $end > $now;
}

/**
 * 投票が終わっているか
 *
 * @param null|int|WP_Post $post
 *
 * @return bool
 */
function sk_vote_ended( $post = null ) {
	$post = get_post( $post );
	$end  = sk_vote_time( 'vote_until', $post );

	return $end && $end < date_i18n( 'YmdHis' );
}

/**
 * 投票告知中か
 *
 * @param null|int|WP_Post $post
 *
 * @return bool
 */
function sk_vote_announcing( $post = null ) {
	$post  = get_post( $post );
	$start = sk_vote_time( 'vote_from', $post );

	return $start && $start > date_i18n( 'YmdHis' );
}

function sk_is_vote_result_able( ) {
	$return = false;

	if( $vote_list = sk_vote_list() ) {
		if( sk_meta( 'circle' ) ) {
			$return = true;
		}
		foreach( $vote_list as $vote ) {
			if( !empty($vote['type']) && $vote['type'] == 'free' ) {
				$return = false;
				break;
			}
		}
	}

	return $return;
}

/**
 * 投票結果を表示させるか
 *
 * @return bool
 */
function sk_is_vote_result_display( ) {
	$return = false;

	$view = !empty( $_POST['view'] ) ? $_POST['view'] : null ;
	$return = $view === 'result' ? true : false ;
	return $return;
}
/**
 * 投票したか
 *
 * param int
 *
 * @return bool
 */
function sk_is_vote_answerd( $id = 0 ) {
	$ids = $_COOKIE['sk_vote'];
	$id_array = $ids ? explode('-', $ids) : [] ;
	return in_array($id, $id_array) ;
}

/**
 * 投票結果を返す
 *
 * @param null|int|WP_Post $post
 *
 * @return array
 */
function sk_vote_result( $post = null ) {
	$post = get_post( $post );

	$return = [];
	if( $choices = sk_vote_selection( $post ) ) {
		$results = \Tarosky\Common\Tarosky\Common\Models\VoteResults::instance()->get_scores( $post->ID );

		foreach ( $results as $result ) {
			$return [0][] = [
				'id' => $result->value,
				'question_no' => null,
				'score' => $result->score,
				'label' => isset( $choices[ $result->value ] ) ? esc_html( $choices[ $result->value ] ) : '不明',
			];
		}
	} else {
		$results = \Tarosky\Common\Tarosky\Common\Models\VoteResults::instance()->get_scores( $post->ID, true );
		$vote_list = sk_vote_list( $post );

		$search_list = [];
		foreach ( $vote_list as $qno => $vote ) {
			$choices = preg_split( "#(\r)?\n#", trim( $vote['choice'] ) );

			foreach ( $results as $questions ) {
				foreach ( $questions as $result ) {
					if( $result->question_no == $qno ) {
						foreach( explode(',', $result->value) as $explode_value ) {
							$keyno = sprintf( '%d-%d', $qno-1, $explode_value );
							if( array_key_exists( $keyno, $search_list) ) {
								$return[$qno-1][$search_list[$keyno]]['score'] = strval( $return[$qno-1][$search_list[$keyno]]['score'] + $result->score );
							}
							else {
								$return[$qno-1][] = [
									'id' => $explode_value,
									'question_no' => $result->question_no,
									'score' => $result->score,
									'label' => isset( $choices[ $explode_value ] ) ? esc_html( $choices[ $explode_value ] ) : '不明',
								];
								$search_list[$keyno] = count($return[$qno-1])-1;
							}
						}
					}
				}
			}
		}
	}

	if( $return ) {
		foreach ($return as &$ret) {
            $sort = [];
			foreach ((array) $ret as $key => $value) {
				$sort[$key] = $value['score'];
			}
			array_multisort($sort, SORT_DESC, $ret);
		}
	}

	return $return;
}

/**
 * 属性のラベルを取得する
 *
 * @param string $key
 *
 * @return string
 */
function sk_vote_attribute_label( $key ) {
	$labels = \Tarosky\Common\Tarosky\Common\Models\VoteResults::instance()->additional_keys;
	return isset( $labels[ $key ] ) ? $labels[ $key ] : '属性';
}

/**
 * 属性値を取得する
 *
 * @param null|int|WP_Post $post
 *
 * @return array
 */
function sk_vote_attributes( $post = null ) {
	$post = get_post( $post );
	return \Tarosky\Common\Tarosky\Common\Models\VoteResults::instance()->get_attributes( $post->ID );
}

/**
 * 性別を返す
 *
 * @param string|bool $key falseなら配列
 *
 * @return array|string
 */
function sk_sex( $key = false ) {
	$var = [
		'1' => '男性',
		'2' => '女性',
	];
	if ( false === $key ) {
		return $var;
	} else {
		return isset( $var[ $key ] ) ? $var[ $key ] : '無回答';
	}
}

/**
 * サッカーとの関わりを返す
 *
 * @param string|bool $key falseなら配列
 *
 * @return array|string
 */
function sk_play_styles( $key = false ) {
	$var = [
		'01' => '観戦',
		'02' => 'プレイ',
		'03' => 'ゲーム',
	];
	if ( false === $key ) {
		return $var;
	} else {
		return isset( $var[ $key ] ) ? $var[ $key ] : '無回答';
	}
}

/**
 * 世代を返す
 *
 * @param string|bool $key falseなら配列
 *
 * @return array|string
 */
function sk_generations( $key = false ) {
	$var = [
		'1' => '10代',
		'2' => '20代',
		'3' => '30代',
		'4' => '40代',
		'5' => '50代',
		'6' => '60代',
		'7' => '70代以上',
	];
	if ( false === $key ) {
		return $var;
	} else {
		return isset( $var[ $key ] ) ? $var[ $key ] : '無回答';
	}
}

/**
 * 職種を返す
 *
 * @param string|bool $key falseなら配列
 *
 * @return array|string
 */
function sk_jobs( $key = false ) {
	$var = [
		'1'  => '事務系会社員',
		'2'  => '技術系会社員',
		'3'  => '営業・販売系会社員',
		'4'  => '企画・調査系会社員',
		'5'  => '経営・管理職',
		'6'  => '公務員',
		'7'  => '医療関連職',
		'8'  => '教育関連職',
		'9'  => '自営業',
		'10' => '自由業',
		'11' => '小学生・中学生',
		'12' => '高校生',
		'13' => '大学生',
		'14' => '短大生',
		'15' => '専門学校生',
		'16' => 'パート・アルバイト',
		'17' => '専業主婦',
		'18' => '無職',
		'19' => 'その他',
	];
	if ( false === $key ) {
		return $var;
	} else {
		return isset( $var[ $key ] ) ? $var[ $key ] : '無回答';
	}
}

/**
 * 都道府県を返す
 *
 * @param string|bool $key falseなら配列
 *
 * @return array|string
 */
function sk_prefs( $key = false ) {
	$var = [
		'01'    => '北海道',
		'02'    => '青森県',
		'03'    => '岩手県',
		'04'    => '宮城県',
		'05'    => '秋田県',
		'06'    => '山形県',
		'07'    => '福島県',
		'08'    => '茨城県',
		'09'    => '栃木県',
		'10'    => '群馬県',
		'11'    => '埼玉県',
		'12'    => '千葉県',
		'13'    => '東京都',
		'14'    => '神奈川県',
		'15'    => '新潟県',
		'16'    => '富山県',
		'17'    => '石川県',
		'18'    => '福井県',
		'19'    => '山梨県',
		'20'    => '長野県',
		'21'    => '岐阜県',
		'22'    => '静岡県',
		'23'    => '愛知県',
		'24'    => '三重県',
		'25'    => '滋賀県',
		'26'    => '京都府',
		'27'    => '大阪府',
		'28'    => '兵庫県',
		'29'    => '奈良県',
		'30'    => '和歌山県',
		'31'    => '鳥取県',
		'32'    => '島根県',
		'33'    => '岡山県',
		'34'    => '広島県',
		'35'    => '山口県',
		'36'    => '徳島県',
		'37'    => '香川県',
		'38'    => '愛媛県',
		'39'    => '高知県',
		'40'    => '福岡県',
		'41'    => '佐賀県',
		'42'    => '長崎県',
		'43'    => '熊本県',
		'44'    => '大分県',
		'45'    => '宮崎県',
		'46'    => '鹿児島県',
		'47'    => '沖縄県',
		'10000' => 'その他',
	];
	if ( false === $key ) {
		return $var;
	} else {
		return isset( $var[ $key ] ) ? $var[ $key ] : '無回答';
	}
}

/**
 * エリアを返す
 *
 * @return array
 */
function sk_areas() {
	return [
		'北海道' => [ '01' ],
		'東北'  => [ '02', '03', '04', '05', '06', '07' ],
		'関東'  => [ '08', '09', '10', '11', '12', '13', '14' ],
		'中部'  => [ '15', '16', '17', '18', '19', '20', '21', '22', '23' ],
		'近畿'  => [ '24', '25', '26', '27', '28', '29', '30' ],
		'中国'  => [ '31', '32', '33', '34', '35' ],
		'四国'  => [ '36', '37', '38', '39' ],
		'九州'  => [ '40', '41', '42', '43', '44', '45', '46' ],
		'沖縄'  => [ '47' ],
		'その他' => [ '10000' ],
	];
}
