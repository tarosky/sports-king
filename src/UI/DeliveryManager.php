<?php

namespace Tarosky\Common\UI;


use Tarosky\Common\Pattern\Singleton;
use Tarosky\Common\Utility\Input;

/**
 * Class DeliveryManager
 * @package Tarosky\Common\UI
 * @property-read Input $input
 */
class DeliveryManager extends Singleton {
	/**
	 * @var array Field Vavlue
	 */
	protected $fields = [
		'1' => '全てに配信（含むYahoo）',
		'2' => 'Yahoo以外に配信',
		'3' => 'ニコニコにのみ配信',
		'5' => 'BKAPPにのみ配信',
		''  => '配信しない',
	];

	protected $post_types = [ 'post', 'bkapp' ];

	/**
	 * DeliveryManager constructor.
	 *
	 * @param array $settings
	 */
	protected function __construct( array $settings = [] ) {
		if ( is_admin() ) {
			// Add meta box
			add_action( 'add_meta_boxes', function ( $post_type ) {
				if ( false !== array_search( $post_type, $this->post_types ) ) {
					// メタボックス追加
					add_meta_box( 'sk_delivery_manager', '外部配信設定', [
						$this,
						'add_meta_box',
					], $post_type, 'side', 'default' );
					// JSも追加
					wp_enqueue_script( 'sk-delivery-helper', get_template_directory_uri() . '/assets/js/admin/delivery-helper.js', [ 'jquery' ], sk_theme_version(), true );
					wp_localize_script( 'sk-delivery-helper', 'SkDeliveryHelper', $this->get_fields($post_type) );
				}
			}, 9 );
			// Add title
			add_action( 'edit_form_before_permalink', [ $this, 'after_title' ] );
			// Save
			add_action( 'save_post', [ $this, 'save_post' ], 10, 2 );
		}
	}

	/**
	 * Detect if post is deliverable
	 *
	 * @param null|int|\WP_Post $post
	 *
	 * @return int
	 */
	public function is_deliverable( $post = null ) {
		$post  = get_post( $post );
		$field = $this->get_status( $post );
		$is_ad = get_post_meta( $post->ID, '_is_ad', true );

		return ( '1' !== $is_ad ) && $field ? (int) $field : 0;
	}

	/**
	 * タイトル直下に配信ステータスを出す
	 *
	 * @param \WP_Post $post
	 */
	public function after_title( \WP_Post $post ) {
		if ( false !== array_search( $post->post_type, $this->post_types ) ) {
			?>
			<div class="sk_delivery__status"></div>
			<?php
		}
	}

	/**
	 * メタボックスを表示
	 *
	 * @param \WP_Post $post
	 */
	public function add_meta_box( \WP_Post $post ) {
		$current_value = $this->get_current_value( $post );
		?>
		<div class="sk_delivery__buttons">
			<?php foreach ( $this->get_fields(get_post_type($post)) as $value => $label ) : ?>
				<label class="sk_delivery__label">
					<input class="sk_delivery__input" type="radio" name="yahoo_upload"
					       value="<?= esc_attr( $value ) ?>" <?php checked( $current_value == $value ) ?>/>
					<?= esc_html( $label ) ?>
				</label>
			<?php endforeach; ?>
		</div>
		<strong class="sk_delivery__flag">広告フラグチェック中</strong>
		<p class="sk_delivery__notice">
			広告フラグにチェックが入っているものはこの設定に関わらず配信されません。
		</p>
		<?php
		/**
		 * sk_delivery_notice
		 *
		 * 配信設定に関する注記
		 *
		 * @param \WP_Post $post
		 */
		do_action( 'sk_delivery_notice', $post );
		wp_nonce_field( 'yahoo_upload', '_skyahoouploadnonce', false );
	}

	/**
	 * 保存
	 *
	 * @param int $post_id
	 * @param \WP_Post $post
	 */
	public function save_post( $post_id, $post ) {
		if ( wp_is_post_revision( $post ) || wp_is_post_autosave( $post ) ) {
			return;
		}
		if ( $this->input->verify_nonce( 'yahoo_upload', '_skyahoouploadnonce' ) ) {
			update_post_meta( $post_id, 'yahoo_upload', $this->input->post( 'yahoo_upload' ) );
		}
	}


	/**
	 * ステータスを取得する
	 *
	 * @param null|\WP_Post|integer $post
	 *
	 * @return mixed
	 */
	protected function get_status( $post = null ) {
		$post = get_post( $post );

		return get_post_meta( $post->ID, 'yahoo_upload', true );
	}

	/**
	 * 
	 * @param type $post_type
	 * @return type
	 */
	protected function get_fields( $post_type ) {
		$ret_fields = $this->fields;
		
		switch( $post_type ){
			case 'bkapp':
				foreach( $ret_fields as $key => $field ) {
					//	bkapp以外unset
					if( $key != 5 ) {
						unset( $ret_fields[$key] );
					}
				}
				array_values($ret_fields);
				break;
		}
		return $ret_fields;
	}

	/**
	 * 
	 * @param type $post
	 * @return int
	 */
	protected function get_current_value( $post ) {
		$ret_current_value = $this->get_status( $post );
		
		switch( get_post_type($post) ){
			case 'bkapp':
				$ret_current_value = 5;
				break;
		}

		return $ret_current_value;
	}

	/**
	 * マジックメソッド
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
