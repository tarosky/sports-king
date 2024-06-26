<?php

namespace Tarosky\Common\UI\FreeInput;

/**
 * タクソノミーに表示する
 *
 * @package Tarosky\Common\UI\FreeInput
 */
abstract class Taxonomy extends Module {

	protected $taxonomies = [];

	/**
	 * データを取得する
	 *
	 * @param \WP_Post $object
	 *
	 * @return array
	 */
	public static function get_raw_data_from( $object ) {
		return get_term_meta( $object->term_id, static::$name, true );
	}

	/**
	 * コンストラクタ
	 */
	protected function __construct() {
		if ( is_admin() ) {
			foreach ( $this->taxonomies as $taxonomy ) {
				add_action( "{$taxonomy}_edit_form_fields", [ $this, 'edit_form_fields' ], 10, 2 );
			}
			add_action( 'edited_terms', [ $this, 'save_term' ], 10, 2 );
		}
	}

	/**
	 * タームメタを保存する
	 *
	 * @param int $term_id
	 * @param string $taxonomy
	 */
	public function save_term( $term_id, $taxonomy ) {
		if ( false === array_search( $taxonomy, $this->taxonomies ) || ! $this->verify()  ) {
			return;
		}
		update_term_meta( $term_id, static::$name, $this->normalize() );
	}

	/**
	 * フォームテーブル表示
	 *
	 * @param \WP_Term $term
	 * @param string $taxonomy
	 */
	public function edit_form_fields( $term, $taxonomy ) {
		$this->enqueue_scripts();
		?>
		<tr>
			<th>
				<?= esc_html( $this->title ) ?>
			</th>
			<td class="freeInput__row">
				<?php
				$this->show_ui( $term );
				if ( $this->description ) {
					printf( '<p class="freeeInput__description">%s</p>', esc_html( $this->description ) );
				}
				?>
			</td>
		</tr>
		<?php
	}
}
