<?php

namespace Tarosky\Common\Tarosky\Common\UI\Fields;


use Tarosky\TSCF\UI\Fields\Select;

/**
 * チームに順番を入れられるようにする機能
 *
 * @deprecated 日付の昇順のみになるので、廃止
 * @package Tarosky\Common\UI\Fields
 */
class TeamOrder extends Select {

	protected $default = 0;

	/**
	 * 通常のselectを上書きした入力
	 */
	protected function display_field() {
		$current_value = $this->get_data( false );
		printf( '<select name="%1$s" id="%1$s">', esc_attr( $this->get_name() ) );
		// 指定しないを選ぶ
		$this->show_input( '0', '指定しない', $current_value );
		// 各都道府県を出力
		$prefs = sk_prefs();
		foreach ( sk_areas() as $label => $indexes ) {
			if ( 'その他' == $label ) {
				continue;
			}
			printf( '<optgroup label="%s">', esc_attr( $label ) );
			foreach ( $indexes as $index ) {
				$pref = $prefs[ $index ];
				$num = 47 - intval( $index ) + 1;
				$this->show_input( $num, $pref, $current_value );
			}
			echo '</optgroup>';
		}
		echo '</select>';
	}
}
