<?php

namespace Tarosky\Common\UI\Screen;


use Tarosky\Common\Pattern\Singleton;
use Tarosky\Common\Utility\Input;

/**
 * 管理画面になにかを追加したい場合はこれを使う
 *
 * @package Tarosky\Common\UI\Screen
 * @property-read Input $input
 */
abstract class ScreenBase extends Singleton {

	/**
	 * @var string 権限
	 */
	protected $capability = 'manage_options';

	/**
	 * @var string ページスラッグ
	 */
	protected $slug = '';

	/**
	 * @var string 親ページのスラッグ
	 */
	protected $parent = '';

	/**
	 * @var string
	 */
	protected $position = 25;

	/**
	 * @var string
	 */
	protected $icon = '';

	/**
	 * @var bool
	 */
	protected $is_root = false;

	/**
	 * @var string ページタイトル
	 */
	protected $title = '';

	/**
	 * @var string 指定した場合、トップメニューの時にサブメニューを作る
	 */
	protected $submenu_title = '';

	/**
	 * @var string メニュータイトル（指定しなければタイトルを使う）
	 */
	protected $menu_title = '';

	public function __construct( array $settings ) {
		$index = $this->is_root ? 10 : 11;
		add_action( 'admin_menu', array( $this, 'admin_menu' ), $index );
		add_action( 'admin_init', array( $this, 'admin_init' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
		if ( ! $this->menu_title ) {
			$this->menu_title = $this->title;
		}
	}

	/**
	 * メニューを登録する
	 */
	public function admin_menu() {
		if ( $this->is_root ) {
			add_menu_page( $this->title, $this->menu_title, $this->capability, $this->slug, array(
				$this,
				'wrapper',
			), $this->icon, $this->position );
			if ( $this->submenu_title ) {
				add_submenu_page( $this->slug, $this->title, $this->submenu_title, $this->capability, $this->slug, array(
					$this,
					'wrapper',
				) );
			}
		} else {
			add_submenu_page( $this->parent, $this->title, $this->menu_title, $this->capability, $this->slug, array(
				$this,
				'wrapper',
			) );
		}
	}

	/**
	 * Executed on admin_init hook
	 */
	public function admin_init() {
		// Do something.
	}

	/**
	 * スクリプトを読み込む
	 *
	 * @param string $page
	 */
	public function enqueue_scripts( $page ) {
		// Do nothing.
	}

	/**
	 * Show menu
	 */
	public function wrapper() {
		?>
		<div class="wrap">
			<h2><?php echo esc_html( $this->title ); ?></h2>
			<?php $this->render(); ?>
		</div>
		<?php
	}

	/**
	 * 画面を表示する
	 * @return void
	 */
	abstract protected function render();

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
