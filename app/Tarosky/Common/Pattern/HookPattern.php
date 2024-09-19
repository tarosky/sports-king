<?php

namespace Tarosky\Common\Pattern;


/**
 * フックを登録するためのパターン
 *
 * このクラスを継承したものは、フックの登録・解除だけを実行する。
 */
abstract class HookPattern extends Singleton {

	/**
	 * @var array<class-string, bool> 有効化されているかどうか
	 */
	private static array $inactives = [];

	/**
	 * {@inheritDoc}
	 */
	protected function __construct( $settings = array() ) {
		if ( static::is_active() ) {
			$this->register_hooks();
		}
	}

	/**
	 * Register hooks on constructor.
	 *
	 * @return void
	 */
	abstract protected function register_hooks(): void;

	/**
	 * このフックを登録するかどうか
	 *
	 * @param bool $active アクティブにするかどうか
	 * @return void
	 */
	public static function set_active( bool $active ) {
		self::$inactives[ get_called_class() ] = $active;
	}

	/**
	 * このフックが有効化どうか
	 *
	 * @return bool
	 */
	public static function is_active() {
		$class_name = get_called_class();
		return ! isset( self::$inactives[ $class_name ] ) || (bool) self::$inactives[ $class_name ];
	}
}
