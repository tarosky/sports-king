<?php

namespace Tarosky\Common\UI\FreeInput;

/**
 * タクソノミーに表示する
 *
 * @package Tarosky\Common\UI\FreeInput
 */
class Taxonomy extends Module {

	protected $taxonomies = array();

	/**
	 * データを取得する
	 *
	 * @param \WP_Term $object
	 *
	 * @return array
	 */
	public function get_raw_data_from( $object ) {
		return get_term_meta( $object->term_id, $this->name, true );
	}

	/**
	 * コンストラクタ
	 *
	 * @param array{taxonomies: string[], name: string, title: string, description: string, nonce_key: string} $setting
	 */
	protected function __construct( $setting = array() ) {
		parent::__construct( $setting );
		if ( is_admin() ) {
			foreach ( $this->taxonomies as $taxonomy ) {
				add_action( "{$taxonomy}_edit_form_fields", array( $this, 'edit_form_fields' ), 100, 2 );
			}
			add_action( 'edited_terms', array( $this, 'save_term' ), 10, 2 );
		}
	}

	/**
	 * タームメタを保存する
	 *
	 * @param int $term_id
	 * @param string $taxonomy
	 */
	public function save_term( $term_id, $taxonomy ) {
		if ( ! in_array( $taxonomy, $this->taxonomies, true ) || ! $this->verify() ) {
			return;
		}
		update_term_meta( $term_id, $this->name, $this->normalize() );
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
				<?php echo esc_html( $this->title ); ?>
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

	/**
	 * フィールドの値を設定する
	 *
	 * @param array{taxonomies: string[], name: string, title: string, description: string, nonce_key: string} $setting
	 * @return void
	 */
	protected function set_up( $setting ) {
		$setting           = wp_parse_args( $setting, array(
			'taxonomies'  => array(),
			'name'        => '',
			'title'       => '',
			'description' => '',
			'nonce_key'   => '',
		) );
		$this->taxonomies  = $setting['taxonomies'];
		$this->name        = $setting['name'];
		$this->title       = $setting['title'];
		$this->description = $setting['description'];
		$this->nonce_key   = $setting['nonce_key'];
	}



	/**
	 * タクソノミーを出力する
	 *
	 * @return string[]
	 */
	public function get_post_type() {
		return $this->taxonomies;
	}
}
