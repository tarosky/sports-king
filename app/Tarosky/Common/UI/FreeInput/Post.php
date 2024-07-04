<?php

namespace Tarosky\Common\UI\FreeInput;

/**
 * 投稿タイプに表示する
 *
 * @package Tarosky\Common\UI\FreeInput
 */
class Post extends Module {

	protected $post_types = [];

	/**
	 * 投稿データを取得する
	 *
	 * @param \WP_Post $object
	 *
	 * @return array
	 */
	public function get_raw_data_from( $object ) {
		return get_post_meta( $object->ID, $this->name, true );
	}

	/**
	 * コンストラクタ
	 *
	 * @param array{post_types: string[], name: string, title: string, description: string, nonce_key: string} $setting
	 */
	protected function __construct( $setting = [] ) {
		parent::__construct( $setting );
		if ( is_admin() ) {
			add_action( 'add_meta_boxes', [ $this, 'add_meta_box' ], 10, 2 );
			add_action( 'save_post', [ $this, 'save_post' ], 10, 2 );
		}
	}

	/**
	 * ポストメタを保存する
	 *
	 * @param int $post_id
	 * @param \WP_Post $post
	 */
	public function save_post( $post_id, $post ) {
		if ( wp_is_post_autosave( $post ) || wp_is_post_revision( $post ) ) {
			return;
		}
		if ( in_array( $post->post_type, $this->post_types, true ) && $this->verify() ) {
			update_post_meta( $post_id, $this->name, $this->normalize() );
		}
	}

	/**
	 * メタボックスを登録
	 *
	 * @param string $post_type
	 * @param \WP_Post $post
	 */
	public function add_meta_box( $post_type, $post ) {
		if ( in_array( $post_type, $this->post_types, true ) ) {
			$this->enqueue_scripts();
			add_meta_box( 'metabox' . $this->name, $this->title, [ $this, 'render' ], $post_type, 'advanced', 'low' );
		}
	}

	/**
	 * メタボックスを描画する
	 *
	 * @param \WP_Post $post
	 */
	public function render( $post ) {
		if ( $this->description ) {
			printf( '<p class="description">%s</p>', esc_html( $this->description ) );
		}
		$this->show_ui( $post );
	}

	/**
	 * フィールドの値を設定する
	 *
	 * @param array{post_types: string[], name: string, title: string, description: string, nonce_key: string} $setting
	 * @return void
	 */
	protected function set_up( $setting ) {
		$setting = wp_parse_args( $setting, [
			'post_types'  => [],
			'name'        => '',
			'title'       => '',
			'description' => '',
			'nonce_key'   => '',
		] );
		$this->post_types = $setting['post_types'];
		$this->name = $setting['name'];
		$this->title = $setting['title'];
		$this->description = $setting['description'];
		$this->nonce_key = $setting['nonce_key'];
	}
}
