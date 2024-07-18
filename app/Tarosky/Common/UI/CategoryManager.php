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
class CategoryManager extends Singleton {

	use RichInput;

	/**
	 * コンストラクタ
	 *
	 * @param array $settings
	 */
	public function __construct( array $settings = array() ) {
		//      add_action( 'edit_category_form_fields', [ $this, 'edit_form_fields' ] );
		add_action( 'category_edit_form_fields', array( $this, 'edit_form_fields' ) );
		add_action( 'category_edit_form', array( $this, 'category_edit_form' ), 10, 2 );
		add_action( 'edited_terms', array( $this, 'edited_terms' ), 10, 2 );
	}

	/**
	 * カテゴリーを更新する
	 *
	 * @param int $term_id
	 * @param string $taxonomy
	 */
	public function edited_terms( $term_id, $taxonomy ) {
		if ( 'category' !== $taxonomy ) {
			return;
		}
		$input = Input::instance();
		if ( $input->verify_nonce( 'sk_cateogry', '_skcategorynonce' ) ) {
			// 色を保存
			$color = $input->post( 'sk_category_color' );
			if ( preg_match( '@^#[0-9a-fA-F]{6}$@u', $color ) ) {
				update_term_meta( $term_id, 'color', $color );
			} else {
				delete_term_meta( $term_id, 'color' );
			}
			// ジャンルを保存
			update_term_meta( $term_id, 'cat_genre', $input->post( 'sk_category_genre' ) );
			// 画像を保存
			update_term_meta( $term_id, 'cat_image', $input->post( 'cat_image' ) );
			// リッチコンテンツを保存
			update_term_meta( $term_id, 'rich_contents', trim( $input->post( 'category-rich-edit' ) ) );
			// 優先順位を保存
			update_term_meta( $term_id, 'cat_priority', $input->post( 'sk_category_priority' ) );
		}
	}

	/**
	 * フィールドを表示する
	 *
	 * @param \WP_Term $term
	 */
	public function edit_form_fields( $term ) {
		wp_enqueue_style( 'wp-color-picker' );
		wp_enqueue_script( 'wp-color-picker' );
		?>
		<tr>
			<th>
				<label for="sk_category_genre">カテゴリー分類</label>
			</th>
			<td>
				<select name="sk_category_genre" id="sk_category_genre">
					<?php
					foreach ( sk_get_category_genres() as $key => $label ) {
						printf( '<option value="%s"%s>%s</option>', esc_attr( $key ), selected( $key, get_term_meta( $term->term_id, 'cat_genre', true ), false ), esc_html( $label ) );
					}
					?>
				</select>
			</td>
		</tr>
		<tr>
			<th>
				<label for="sk_category_priority">表示優先順位</label>
			</th>
			<td>
				<select name="sk_category_priority" id="sk_category_priority" size="1">
					<?php for ( $i = 1; $i <= 10; $i++ ) : ?>
						<?php echo sprintf( '<option value="%d" %s>%d</option>', $i, selected( $i, get_term_meta( $term->term_id, 'cat_priority', true ), false ), $i ); ?>
					<?php endfor; ?>
				</select>
				<p class="description">
					値が大きい方が優先度が高くなります。
				</p>
			</td>
		</tr>
		<tr>
			<th>
				<label for="category_color">テーマカラー</label>
			</th>
			<td>
				<p class="description">
					親テーマから継承されている色：
					<?php if ( $color = sk_term_cascading_meta( $term, 'color', $term->taxonomy, false ) ) : ?>
						<span style="color: <?php echo esc_attr( $color ); ?>;">■ <?php echo esc_attr( $color ); ?></span>
					<?php else : ?>
						<strong>なし</strong>
					<?php endif; ?>
				</p>
				<input type="text" value="<?php echo esc_attr( get_term_meta( $term->term_id, 'color', true ) ); ?>"
						id="sk_category_color" name="sk_category_color"/>
				<script>
					jQuery(document).ready(function ($) {
						$('#sk_category_color').wpColorPicker();
					});
				</script>
			</td>
		</tr>
		<tr>
			<th>画像</th>
			<td>
				<p>
					継承されている画像:
				<?php
				if ( ( $parent_media = sk_term_cascading_meta( $term, 'cat_image', $term->taxonomy, false ) )
					&& ( $attachment = get_post( $parent_media ) )
					&& ( 'attachment' == $attachment->post_type )
				) :
					?>
						<?php echo wp_get_attachment_image( $attachment->ID, 'thumbnail' ); ?>
					<?php else : ?>
						なし
					<?php endif; ?>
				</p>
				<?php
				$media = get_term_meta( $term->term_id, 'cat_image', true );
				$this->image_input( 'cat_image', $media, 1 );
				?>
				<p class="description">画像を設定した場合、このカテゴリーに属する投稿のタイトルの前に表示されます。</p>
			</td>
		</tr>
		<?php
	}

	/**
	 * @param $category
	 * @param $taxonomy
	 */
	public function category_edit_form( $category, $taxonomy ) {
		wp_nonce_field( 'sk_cateogry', '_skcategorynonce' );
		?>
		<div id="sk-category-editor">
			<h2>カテゴリ別自由入稿枠0</h2>
			<p class="description">カテゴリ記事一覧ページにのみ表示されます。</p>
			<?php wp_editor( get_term_meta( $category->term_id, 'rich_contents', true ), 'category-rich-edit' ); ?>
		</div>
		<?php
	}
}
