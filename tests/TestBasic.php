<?php

/**
 * クラスの基本動作を確認
 */
class TestBasic extends \WP_UnitTestCase {

	/**
	 * フックパターンが動作するか
	 *
	 * @return void
	 */
	public function test_hook_pattern() {
		// ここでは1つを無効化しても、もう1つは有効化のまま
		\Tarosky\Common\Hooks\PlayerRegisterHooks::set_active( false );
		$this->assertFalse( \Tarosky\Common\Hooks\PlayerRegisterHooks::is_active() );
		$this->assertTrue( \Tarosky\Common\Hooks\TeamRegisterHooks::is_active() );
		// 無効化したら無効になる
		\Tarosky\Common\Hooks\TeamRegisterHooks::set_active( false );
		$this->assertFalse( \Tarosky\Common\Hooks\TeamRegisterHooks::is_active() );
	}
}
