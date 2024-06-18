<?php

namespace Tarosky\Common\Pattern;
use Tarosky\Common\Utility\Input;

/**
 * REST APIのベースクラス
 *
 * @package Tarosky\Common\Pattern
 * @property-read Input $input
 */
abstract class RestBase extends Singleton {

	protected $root = 'sk/v1';

	protected $nonce_key = 'wp_rest';

	/**
	 * コンストラクタ
	 *
	 * @param array $settings
	 */
	final protected function __construct( array $settings = [] ) {
		add_action( 'rest_api_init', [ $this, 'rest_init' ] );
		$this->on_construct();
	}

	/**
	 * カスタマイズが必要な場合はこれをオーバーライド
	 */
	protected function on_construct() {
		// Do nothing.
	}

	/**
	 * REST APIを登録する
	 *
	 * @param \WP_REST_Server $wp_rest_server
	 *
	 * @return mixed
	 */
	abstract public function rest_init( $wp_rest_server );

	/**
	 * Getter
	 *
	 * @param string $name
	 *
	 * @return mixed
	 */
	public function __get( $name ) {
		switch ( $name ) {
			case 'input':
				return Input::instance();
			default:
				return null;
		}
	}
}
