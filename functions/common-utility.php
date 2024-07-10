<?php
/**
 * ライブラリ共通のユーティリティファイル
 *
 * @package sports-king
 */

/**
 * style.cssに記載されたテーマのバージョンを返す
 *
 * @param bool $child 初期値はfalse, trueにすると子テーマのバージョン
 * @return string
 */
function sk_theme_version( $child = false ) {
	$theme = wp_get_theme( $child ? get_stylesheet() : get_template() );
	if ( ! $theme->exists() ) {
		return '1.0.0';
	}
	return $theme->get( 'Version' );
}

/**
 * パンくずリストを表示
 *
 * @param array{nav_class:string, list_class:string} $args オプションの配列
 * @return void
 */
function sk_breadcrumb( $args = [] ) {
	$args = wp_parse_args( $args, [
		'nav_class'  => 'breadcrumb-nav',
		'list_class' => 'breadcrumb-nav__list',
	] );
	if ( function_exists( 'bcn_display_list' ) ) {
		printf( '<nav class="%s">', esc_attr( $args['nav_class'] ) );
		printf(  '<ol class="%s"  itemscope itemtype="http://schema.org/BreadcrumbList">', esc_attr( $args['list_class'] ) );
		bcn_display_list();
		echo '</ol>';
		echo '</nav>';
	}
}


/**
 * 移行期間の古いIDを取得する
 *
 * リニューアルによる同時入稿でIDが少し変わる場合に外部連携でIDを利用するケースがある
 *
 * @param int $post_id
 *
 * @return int
 */
function sk_diff_id( $post_id ) {
	return (int) get_post_meta( $post_id, '_sk_diff_id', true );
}

