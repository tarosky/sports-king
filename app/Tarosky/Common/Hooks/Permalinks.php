<?php

namespace Tarosky\Common\Hooks;


use Tarosky\Common\Pattern\HookPattern;

/**
 * パーマリンクの共通処理
 */
class Permalinks extends HookPattern {

	/**
	 * {@inheritDoc}
	 */
	protected function register_hooks(): void {
		add_filter( 'query_vars', array( $this, 'query_vars' ) );
	}

	/**
	 * メインクエリのページネーションに影響を与えないクエリ変数を追加する
	 *
	 * @param string[] $vars
	 * @return string[]
	 */
	public function query_vars( $vars ) {
		$vars[] = 'paginated';
		return $vars;
	}
}
