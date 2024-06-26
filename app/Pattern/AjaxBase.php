<?php

namespace Tarosky\Common\Pattern;


use Tarosky\Common\Utility\Input;

/**
 * Ajaxユーティリティ
 *
 * @package Tarosky\Common\Pattern
 * @property-read Input $input
 */
abstract class AjaxBase extends Singleton {

	/**
	 * @var string Ajax action name
	 */
	protected $action = '';

	/**
	 * @var string 'admin'(default) or 'public'
	 */
	protected $scope = 'admin';

	/**
	 * @var string If set, nonce will be verified
	 */
	protected $nonce = '';

	/**
	 * @var string Default '_wpnonce'
	 */
	protected $nonce_key = '_wpnonce';

	/**
	 * Ajax constructor.
	 *
	 * @param array $settings
	 */
	public function __construct( array $settings ) {
		if ( $this->action ) {
			add_action( 'admin_init', function () {
				switch ( $this->scope ) {
					case 'public':
						add_action( "wp_ajax_nopriv_{$this->action}", [ $this, 'ajax' ] );
						add_action( "wp_ajax_{$this->action}", [ $this, 'ajax' ] );
						break;
					default:
						add_action( "wp_ajax_{$this->action}", [ $this, 'ajax' ] );
						break;
				}
			} );
		}
	}

	/**
	 * Do Ajax request
	 */
	public function ajax() {
		try {
			if ( $this->nonce && $this->input->verify_nonce( $this->nonce, $this->nonce_key ) ) {
				// We should handle nonce.
				throw new \Exception( '不正なアクセスです。', 400 );
			}
			$validate = $this->validate_request();
			if ( is_wp_error( $validate ) ) {
				$this->handle_result( $validate );
			} else {
				$this->handle_result( $this->process() );
			}
		} catch ( \Exception $e ) {
			$this->handle_result( new \WP_Error( $e->getCode(), $e->getMessage() ) );
		}
	}

	/**
	 * 必要ならバリデーションを実装
	 *
	 * @return bool|\WP_Error
	 */
	protected function validate_request() {
		return true;
	}

	/**
	 * Send result as JSON
	 *
	 * @param array|\WP_Error $result
	 */
	protected function handle_result( $result ) {
		if ( is_wp_error( $result ) ) {
			http_send_status( $result->get_error_code() );
			wp_send_json( [
				'success'  => false,
				'messages' => $result->get_error_messages(),
			] );
		} else {
			wp_send_json( [
				'success' => true,
				'data'    => $result,
			] );
		}
	}

	/**
	 * Process Ajax
	 *
	 * @return array|\WP_Error
	 * @throws \Exception
	 */
	abstract protected function process();

	/**
	 * Getter
	 *
	 * @param string $name
	 *
	 * @return null|static
	 */
	public function __get( $name ) {
		switch ( $name ) {
			case 'input':
				return Input::instance();
				break;
			default:
				return null;
				break;
		}
	}


}
