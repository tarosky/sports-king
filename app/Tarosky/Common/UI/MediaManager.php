<?php

namespace Tarosky\Common\UI;


use Tarosky\Common\Pattern\Singleton;
use Tarosky\Common\UI\Helper\RichInput;
use Tarosky\Common\Utility\Input;

/**
 * 媒体カテゴリーを司る
 *
 * @package Tarosky\Common\UI
 */
class MediaManager extends Singleton {

	use RichInput;

	/**
	 * コンストラクタ
	 *
	 * @param array $settings
	 */
	public function __construct( array $settings = array() ) {
		add_action( 'media_cat_edit_form_fields', array( $this, 'edit_form_fields' ), 10, 2 );
		add_action( 'edited_terms', array( $this, 'edited_terms' ), 10, 2 );
	}

	/**
	 * タグを更新する
	 *
	 * @param \WP_Term $term
	 * @param string $taxonomy
	 */
	public function edited_terms( $term_id, $taxonomy ) {
		if ( 'media_cat' !== $taxonomy ) {
			return;
		}
		if ( Input::instance()->verify_nonce( 'update_media', '_mediamanagernonce' ) ) {
			update_term_meta( $term_id, 'media_logo', $_POST['media_logo'] );
			update_term_meta( $term_id, 'media_order', $_POST['media_order'] );
		}
	}

	/**
	 * フィールドを表示する
	 *
	 * @param \WP_Term $term
	 * @param string $taxonomy
	 */
	public function edit_form_fields( $term, $taxonomy ) {
		$id    = get_term_meta( $term->term_id, 'media_logo', true );
		$order = get_term_meta( $term->term_id, 'media_order', true );
		?>
		<tr>
			<th>媒体ロゴ</th>
			<td>
				<?php wp_nonce_field( 'update_media', '_mediamanagernonce', false ); ?>
				<?php $this->image_input( 'media_logo', $id, 1 ); ?>
				<p class="description">
					サイズは300x100の透過の画像を設定してください
				</p>
			</td>
		</tr>
		<tr>
			<th><label for="media_order">優先順位</label></th>
			<td>
				<input type="number" name="media_order" id="media_order" value="<?php echo esc_attr( $order ); ?>" />
				<p class="description">大きい数字ほど先に表示されます。</p>
			</td>
		</tr>
		<?php
	}
}
