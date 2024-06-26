<?php

namespace Tarosky\Common\Tarosky\Common\Pattern;


/**
 * フックを登録するためのパターン
 *
 * このクラスを継承したものは、フックの登録・解除だけを実行する。
 */
abstract class HookPattern extends Singleton {

	/**
	 * @var bool アクティブかどうか
	 */
	static protected $active = true;

	/**
	 * {@inheritDoc}
	 */
	protected function __construct( $settings = [] ) {
		if ( static::$active ) {
			$this->register_hooks();
		}
	}

	/**
	 * Register hooks on constructor.
	 *
	 * @return void
	 */
	abstract protected function register_hooks():void;

	/**
	 * このフックを登録するかどうか
	 *
	 * @param bool $active アクティブにするかどうか
	 * @return void
	 */
	public static function set_active( bool $active ) {
		static::$active = $active;
	}
}
