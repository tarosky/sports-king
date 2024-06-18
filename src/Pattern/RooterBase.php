<?php

namespace Tarosky\Common\Pattern;


use Tarosky\Common\Utility\Input;
use Tarosky\Common\Utility\StringHelper;

/**
 * 既存のWP_Queryをハイジャックする
 *
 * @package Tarosky\Common\Pattern
 */
abstract class RooterBase extends Singleton {

	/**
	 * @var array
	 */
	protected $rewrite = [ ];

	/**
	 * @var array
	 */
	protected $query_vars = [];

	/**
	 * @var \WP_Query 現在のクエリオブジェクト
	 */
	protected $wp_query = null;

	/**
	 * コンストラクタ
	 *
	 * @param array $settings
	 */
	final protected function __construct( array $settings ) {
		if ( $this->query_vars ) {
			add_filter( 'query_vars', [ $this, 'filter_query_vars' ] );
		}
		if ( $this->rewrite ) {
			add_filter( 'rewrite_rules_array', [ $this, 'rewrite_rules_array' ] );
		}
		add_action( 'pre_get_posts', [ $this, 'pre_get_posts' ] );
		$this->on_construct();
	}

	/**
	 * クエリバーが必要なら登録する
	 *
	 * @param array $vars
	 *
	 * @return array
	 */
	public function filter_query_vars( $vars ) {
		foreach ( $this->query_vars as $var ) {
			$vars[] = $var;
		}

		return $vars;
	}

	/**
	 * リライトルールを追加する
	 *
	 * @param array $rewrites
	 *
	 * @return array
	 */
	public function rewrite_rules_array( $rewrites ) {
		return array_merge( $this->rewrite, $rewrites );
	}

	/**
	 * WP_Queryを判定して、処理を行う
	 *
	 * @param $wp_query
	 */
	final public function pre_get_posts( &$wp_query ) {
		if ( $this->test_query( $wp_query ) ) {
			$this->wp_query = $wp_query;
			$this->process( $wp_query );
		}
	}

	/**
	 * 処理を行う
	 *
	 * @param \WP_Query $wp_query
	 *
	 * @return mixed
	 */
	abstract protected function process( &$wp_query );

	/**
	 * テンプレートを読み込んで終了
	 *
	 * @param string $file
	 * @param string $slug
	 * @param array $args
	 */
	protected function load_template( $file, $slug = '', $args = [] ) {
		add_filter( 'document_title_parts', [ $this, 'document_title_parts' ] );
		do_action( 'template_redirect' );
		add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_scripts' ] );
		sk_get_template( $file, $slug, $args );
		exit;
	}

	/**
	 * 名前を変更する
	 *
	 * @param array $title
	 *
	 * @return mixed
	 */
	public function document_title_parts( $title ) {
		return $title;
	}

	/**
	 * スクリプトを読み込む
	 */
	public function enqueue_scripts() {
		// Do nothing.
	}

	/**
	 * コンストラクタの代替
	 */
	protected function on_construct() {
		// Override this if required.
	}

	/**
	 * クエリが該当するかテストする
	 *
	 * @param \WP_Query $wp_query
	 *
	 * @return bool
	 */
	abstract protected function test_query( $wp_query );


	/**
	 * ゲッター
	 *
	 * @param string $name
	 *
	 * @return mixed
	 */
	public function __get( $name ) {
		switch ( $name ) {
			case 'input':
				return Input::instance();
			case 'str':
				return StringHelper::instance();
			default:
				return null;
		}
	}
}
