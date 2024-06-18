<?php

namespace Tarosky\Common\Service;


use Abraham\TwitterOAuth\TwitterOAuth;
use Tarosky\Common\Pattern\Singleton;

/**
 * @deprecated
 */
class Twitter extends Singleton {

	/**
	 * リクエストを発行する
	 *
	 * @see https://dev.twitter.com/rest/public
	 *
	 * @param string $method
	 * @param string $endpoint
	 * @param array $params
	 *
	 * @return array|object|\WP_Error
	 */
	public function request( $method, $endpoint, $params = [] ) {
		if ( ! $this->valid() ) {
			return new \WP_Error( 500, '認証情報が設定されていません。' );
		}
		$client = new TwitterOAuth( TTT_CONSUMER_KEY, TTT_CONSUMER_SECRET, TTT_ACCESS_TOKEN, TTT_ACCESS_TOKEN_SECRET );
		$method = strtolower( $method );
		switch ( $method ) {
			case 'get':
			case 'post':
			case 'put':
			case 'delete';
				return call_user_func_array( [ $client, $method ], [ $endpoint, $params ] );
				break;
			default:
				return new \WP_Error( 500, '誤ったメソッドが指定されています。' );
				break;
		}
	}

	/**
	 * つぶやく
	 *
	 * @param string $text
	 *
	 * @return array|object|\WP_Error
	 */
	public function tweet( $text ) {
		if ( ! $this->valid() ) {
			return new \WP_Error( 500, '認証情報が設定されていません。' );
		}
		$client = new TwitterOAuth( TTT_CONSUMER_KEY, TTT_CONSUMER_SECRET, TTT_ACCESS_TOKEN, TTT_ACCESS_TOKEN_SECRET );
		return $client->post( 'statuses/update', [
			'status' => $text,
		]);
	}

	/**
	 * 検索する
	 *
	 * @param string $query
	 * @param int $count
	 * @param string $next_results
	 *
	 * @return array|object|\WP_Error
	 */
	public function search( $query, $count, $next_results = '' ) {
		$params = [
			'q'     => $query,
			'count' => $count,
		];
		if ( $next_results ) {
			parse_str( $next_results, $params );
		}

		return $this->request( 'get', 'search/tweets', $params );
	}

	/**
	 * @param string $url
	 * @param bool $decode
	 *
	 * @return string
	 */
	public function extract_url( $url, $decode = false ) {
		if ( $decode ) {
			$url = urldecode( $url );
		}
		$retUrl       = '';
		$url_segments = explode( '?', $url );

		// 1. サイトURLに一致
		if ( preg_match( '@.*basketballking\.jp.*@u', $url_segments[0] ) ) {
			if ( $this->check_response( $url_segments[0] ) ) {
				$retUrl = $url;
			}
		} elseif ( ! empty( $url_segments[1] ) && preg_match( '@.*basketballking\.jp.*@u', $url_segments[1] ) ) {
			// 2. クエリ文字列を取得して、そのパラメータの中にURLがないか調べる
			parse_str( $url_segments[1], $parse_segments );
			if ( ! empty( $parse_segments['url'] ) ) {
				if ( $this->check_response( $parse_segments['url'] ) ) {
					$retUrl = $parse_segments['url'];
				}
			}
		} else {
			// 3. 短縮URLの場合は取りに行ってみる
			$response = wp_remote_request( $url );
			if ( 200 == wp_remote_retrieve_response_code( $response ) ) {
				$body = $response['body'];
				if ( preg_match( '@basketballking\.jp.*?@', $body, $matches ) ) {
					$retUrl = $matches[0];
				}
			}
		}

		// クエリを除去する
		$retUrl = explode( '?', $retUrl )[0];

		return $retUrl;
	}

	/**
	 * URLが到達可能かチェックする
	 *
	 * @param string $url
	 *
	 * @return bool
	 */
	protected function check_response( $url ) {
		static $done = [];
		$is_success = true;
		if ( isset( $done[ $url ] ) ) {
			return $done[ $url ];
		}
		$response = wp_remote_request( $url );
		if ( 200 !== wp_remote_retrieve_response_code( $response ) ) {
			$is_success = false;
		}
		$done[ $url ] = $is_success;

		return $is_success;
	}

	/**
	 * 認証情報が定義されているか
	 *
	 * @return bool
	 */
	public function valid() {
		return (
			defined( 'TTT_CONSUMER_KEY' )
			&&
			defined( 'TTT_CONSUMER_SECRET' )
			&&
			defined( 'TTT_ACCESS_TOKEN' )
			&&
			defined( 'TTT_ACCESS_TOKEN_SECRET' )
		);
	}


}
