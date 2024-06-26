<?php

namespace Tarosky\Common\Tarosky\Common\API\Rest;


use Tarosky\Common\Tarosky\Common\Models\VoteResults;
use Tarosky\Common\Tarosky\Common\Pattern\RestBase;
use function Tarosky\Common\API\Rest\get_post;
use function Tarosky\Common\API\Rest\register_rest_route;


/**
 * 投票をコントロール
 *
 * @package Tarosky\Common\API\Rest
 * @property-read VoteResults $vote_results
 */
class VoteController extends RestBase {

	/**
	 * REST APIを登録する
	 *
	 * @param \WP_REST_Server $wp_rest_server
	 */
	public function rest_init( $wp_rest_server ) {
		register_rest_route( $this->root, '/vote/(?P<post_id>\\d+)/?', [
			[
				'methods'             => 'GET',
				'permission_callback' => '__return_true',
				'callback'            => function ( $params ) {
					return new \WP_REST_Response( sk_vote_result( $params[ 'post_id' ] ) );
				},
				'args'                => [
					'post_id' => [
						'validate_callback' => function ( $var ) {
							$post = get_post( $var );

							return $post && ( 'sk_vote' == $post->post_type ) && ( 'publish' == $post->post_status );
						},
						'required'          => true,
					]
				]
			],
			[
				'methods'             => 'POST',
				'callback'            => [ $this, 'post_vote' ],
				'permission_callback' => '__return_true',
				'args'                => [
					'post_id'    => [
						'validate_callback' => function ( $var ) {
							$post = get_post( $var );

							// 投票受付中？
							return $post && 'sk_vote' === $post->post_type && sk_voting( $post );
						},
						'required'          => true,
					],
					'selection'  => [
						'validate_callback' => function ( $var ) {
							return is_array( $var );
						},
						'required'          => true,
					],
					'sex'        => [
						'validate_callback' => function ( $var ) {
							return array_key_exists( $var, sk_sex() );
						},
						'required'          => true,
					],
					'prefecture' => [
						'validate_callback' => function ( $var ) {
							return array_key_exists( $var, sk_prefs() );
						},
						'required'          => true,
					],
					'generation' => [
						'validate_callback' => function ( $var ) {
							return array_key_exists( $var, sk_generations() );
						},
						'required'          => true,
					],
					'job'        => [
						'validate_callback' => function ( $var ) {
							return array_key_exists( $var, sk_jobs() );
						},
						'required'          => true,
					],
				],
			]
		] );
	}

	/**
	 * 投票を保存する
	 *
	 * @param array $params
	 *
	 * @return \WP_Error|\WP_REST_Response
	 */
	public function post_vote( $params ) {
		$post = get_post( $params[ 'post_id' ] );

		$is_multiple = false;
		if ( $vote_selection = sk_vote_selection( $post ) ) {
			$selection_list = $params[ 'selection' ];
			if ( ! array_key_exists( $selection_list[ 0 ], $vote_selection ) ) {
				return new \WP_Error( '400', '無効な回答です。', [
					'status' => 400,
				] );
			}
		} else {
			$is_multiple    = true;
			$is_hit         = true;
			$selection_list = $params[ 'selection' ];
			$vote_list      = sk_vote_list( $post );
			foreach ( $vote_list as $vote_no => $vote ) {
				$vote_key = intval( $vote_no ) - 1;
				if ( isset( $selection_list[ $vote_key ] ) ) {
					switch ( $vote[ 'type' ] ) {
						case 'radio' :
						case 'check' :
							foreach ( explode( ',', $selection_list[ $vote_key ] ) as $selected ) {
								if ( ! array_key_exists( $selected, preg_split( "#(\r)?\n#", trim( $vote[ 'choice' ] ) ) ) ) {
									$is_hit = false;
								}
							}
							break;
					}
				} else {
					$is_hit = false;
				}
			}
			if ( ! $is_hit ) {
				return new \WP_Error( '400', '無効な回答です。', [
					'status' => 400,
				] );
			}
		}
		foreach ( $selection_list as $select_key => $selection ) {
			$values = [];
			$values = [
				'ip' => $this->input->remote_ip(),
			];
			foreach ( [ 'post_id', 'job', 'sex', 'prefecture', 'generation', 'selection', 'question_no' ] as $key ) {
				switch ( $key ) {
					case 'prefecture':
						$values[ 'pref' ] = $params[ $key ];
						break;
					case 'generation':
						$values[ 'age' ] = $params[ $key ];;
						break;
					case 'selection':
						$values[ 'value' ] = $selection;
						break;
					case 'question_no':
						$values[ 'question_no' ] = $is_multiple ? $select_key + 1 : 1;
						break;
					default:
						$values[ $key ] = $params[ $key ];
						break;
				}
			}
			if ( ! $this->vote_results->record_score( $values ) ) {
				return new \WP_Error( '500', '投票を保存できませんでした。', [
					'status' => 500,
				] );
			}
		}

		return new \WP_REST_Response( [
			'success' => true,
			'message' => '投票を保存しました。',
		] );
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
			case 'vote_results':
				return VoteResults::instance();
			default:
				return parent::__get( $name );
		}
	}

}
