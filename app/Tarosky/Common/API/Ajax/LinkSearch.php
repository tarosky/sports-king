<?php

namespace Tarosky\Common\API\Ajax;

use Tarosky\Common\Pattern\AjaxBase;

/**
 * Link search endpoint
 *
 * @package Tarosky\Common\API\Ajax
 */
class LinkSearch extends AjaxBase{

	protected $action = 'sk_search_link';

	/**
	 * 検索する
	 */
	protected function process() {
		if ( $this->input->get( 'q' ) ) {
			$query  = new \WP_Query( [
				's'              => $this->input->get( 'q' ),
				'post_type'      => [ 'any' ],
				'post_status'    => [ 'publish', 'future' ],
				'posts_per_page' => 10,
				'order'          => [
					'date' => 'DESC',
				],
			] );
			$result = [];
			while ( $query->have_posts() ) {
				$query->the_post();
				$cats = get_the_category();
				if ( $cats ) {
					$label = $cats[0]->name;
				} else {
					$label = get_post_type_object( get_post_type() )->labels->name;
				}
				$result[] = [
					'url'   => get_the_permalink(),
					'title' => sprintf(
						'%s(%s: %s)',
						get_the_title(), //mb_substr( get_the_title(), 0, 20, 'utf-8' ),
						get_the_time( 'Y.m.d' ),
						$label
					),
				];
			}
			return $result;
		} else {
			return [];
		}
	}

}
