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
class TagManager extends Singleton {

	use RichInput;

	/**
	 * コンストラクタ
	 *
	 * @param array $settings
	 */
	public function __construct( array $settings = array() ) {
		//      add_action( 'edit_tag_form_fields', [ $this, 'edit_form_fields' ] );
		add_action( 'post_tag_edit_form_fields', array( $this, 'edit_form_fields' ) );
		add_action( 'post_tag_edit_form', array( $this, 'post_tag_edit_form' ), 10, 2 );
		add_action( 'edited_terms', array( $this, 'edited_terms' ), 10, 2 );
	}

	/**
	 * カテゴリーを更新する
	 *
	 * @param int $term_id
	 * @param string $taxonomy
	 */
	public function edited_terms( $term_id, $taxonomy ) {
		if ( 'post_tag' !== $taxonomy ) {
			return;
		}
		$input = Input::instance();
		if ( $input->verify_nonce( 'sk_post_tag', '_skposttagnonce' ) ) {
			// リッチコンテンツを保存
			update_term_meta( $term_id, 'rich_contents', trim( $input->post( 'tag-rich-edit' ) ) );
			// 優先順位を保存
			update_term_meta( $term_id, 'tag_priority', $input->post( 'sk_tag_priority' ) );
		}
	}

	/**
	 * フィールドを表示する
	 *
	 * @param \WP_Term $term
	 */
	public function edit_form_fields( $term ) {
		?>
		<tr>
			<th>
				<label for="sk_tag_priority">表示優先順位</label>
			</th>
			<td>
				<select name="sk_tag_priority" id="sk_tag_priority" size="1">
					<?php for ( $i = 1; $i <= 10; $i++ ) : ?>
						<?php echo sprintf( '<option value="%d" %s>%d</option>', $i, selected( $i, get_term_meta( $term->term_id, 'tag_priority', true ), false ), $i ); ?>
					<?php endfor; ?>
				</select>
				<p class="description">
					値が大きい方が優先度が高くなります。
				</p>
			</td>
		</tr>
		<?php
	}

	/**
	 * @param $category
	 * @param $taxonomy
	 */
	public function post_tag_edit_form( $category, $taxonomy ) {
		wp_nonce_field( 'sk_post_tag', '_skposttagnonce' );
		?>
		<div id="sk-category-editor">
			<h2>タグ別自由入稿枠0</h2>
			<p class="description">タグ記事一覧ページにのみ表示されます。</p>
			<?php wp_editor( get_term_meta( $category->term_id, 'rich_contents', true ), 'tag-rich-edit' ); ?>
		</div>
		<?php
	}
}
