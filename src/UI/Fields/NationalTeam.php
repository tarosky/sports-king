<?php

namespace Tarosky\Common\UI\Fields;


use Tarosky\TSCF\UI\Fields\Select;


/**
 * 代表チームを選手が入力できるようにする
 *
 * @package Tarosky\Common\UI\Fields
 */
class NationalTeam extends Select {

	/**
	 * 代表チーム入力フィールドを表示する
	 */
	protected function display_field() {
		printf( '<select name="%1$s" id="%1$s">', esc_attr( $this->get_name() ) );
		$team_id = (int) $this->get_data( false );
		$this->show_input( 0, '代表ではない', $team_id );
		// すべてのリーグを取得
		$root = get_term_by( 'name', '代表', 'league' );
		if ( $root ) {
			$regions = get_terms( 'league', [
				'parent'     => $root->term_id,
				'hide_empty' => false,
			] );
			if ( $regions && ! is_wp_error( $regions ) ) {
				foreach ( $regions as $region ) {
					printf( '<optgroup label="%s">', esc_attr( $region->name ) );
					foreach ( get_posts( [
						'post_type'      => 'team',
						'post_status'    => 'any',
						'posts_per_page' => - 1,
						'tax_query'      => [
							[
								'taxonomy' => 'league',
								'terms'    => $region->term_id,
								'field'    => 'term_id',
							],
						],
					] ) as $post ) {
						$this->show_input( $post->ID, get_the_title( $post ), $team_id );
					}
					echo '</optgroup>';
				}
			}
		}
		echo '</select>';
	}
}
