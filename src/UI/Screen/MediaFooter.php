<?php

namespace Tarosky\Common\UI\Screen;
use Tarosky\Common\UI\Helper\RichInput;


class MediaFooter extends ScreenBase {
	use RichInput;

	protected $slug = 'sk-media-footer';

	protected $capability = 'edit_others_posts';

	protected $parent = 'sk-page-builder';

	protected $title = '媒体';

	protected $menu_title = 'フッター媒体';

	public function admin_init() {
		if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
			add_action( 'wp_ajax_sk_media_save', [ $this, 'ajax_save' ] );
		}
	}

	/**
	 * 情報を保存する
	 */
	public function ajax_save() {

		try {
			if ( ! current_user_can( 'edit_others_posts' ) ) {
				throw new \Exception( 'この操作をする権限がありません。', 403 );
			}
			if ( ! $this->input->verify_nonce( 'save_media' ) ) {
				throw new \Exception( '不正なアクセスです。', 401 );
			}
			
			update_option( 'media_footer_objects', $this->input->post( 'data' ) );
			wp_send_json( [
				'message' => '設定を保存しました。',
			] );
		} catch ( \Exception $e ) {
			status_header( $e->getCode() );
			wp_send_json([
				'status' => $e->getCode(),
			    'message' => $e->getMessage(),
			]);
		}
	}

	/**
	 * 管理画面を描画する
	 */
	protected function render() {
		$media = sk_get_footer_media( false );
		?>
		<div class="updated">
			<p>
				左から順に最大4件まで表示されます。
				<strong>ボタンを押して左右に動かすことができます。</strong>
			</p>
		</div>
		<div class="media-editor">
			<div class="media-editor__line clearfix">
				<?php
					foreach ( $media as $object ) {
						echo $this->get_media_template( $object );
					}
				?>
			</div><!-- //media-editor__line -->
		</div>

		<p class="submit">
			<button id="media-submit" class="button-primary" data-endpoint="<?= wp_nonce_url(admin_url('admin-ajax.php?action=sk_media_save'), 'save_media') ?>">保存</button>
		</p>

		<?php
	}

	/**
	 * ピックアップオブジェクトを返す
	 *
	 * @param int|array $object
	 *
	 * @return array|null|\WP_Post
	 */
	public function get_media_object( $object ) {
		return array_merge( [
			'url'   => '',
			'image_id' => 0,
			'image' => '',
			'text_1' => '',
			'text_2' => '',
		], (array) $object );
	}

	/**
	 * データからレイアウトを作成する
	 *
	 * @param array|int $object
	 *
	 * @return string
	 */
	protected function get_media_template( $object ) {
		$object  = $this->get_media_object( $object );
		$wrapper = <<<HTML
		<div class="media-editor__wrap" data-type="%s">%s
			<table class="media-editor__controller">
			<tr>
				<td>
					<a class="button moveLeft" href="#">◀</a>&nbsp;
				</td>
				<td>
					<a class="button moveRight" href="#">▶</a>&nbsp;
				</td>
			</tr>
			</table>
		</div>
HTML;
		if ( ! $object ) {
			$type = 'broken';
			$html = <<<HTML
			<p class="description">この投稿は存在しません。削除されたか、あやまってデータが混入した可能性があります。</p>
HTML;
		} else {
			$type = 'link';
			
			$image_input = $this->image_input( 'image_id', intval($object['image_id']), 1, false );
			$image_input = str_replace('class="image-selector-input"', 'class="image-selector-input media-editor__input"', $image_input);
			$html = <<<'HTML'
				<label class="media-editor__label">
					テキスト1<br />
					<input type="text" class="media-editor__input" name="text_1" value="%1$s" />
				</label>
				<label class="media-editor__label">
					テキスト2<br />
					<input type="text" class="media-editor__input" name="text_2" value="%2$s" />
				</label>
				<div class="image_input">
					画像選択<br />
					%3$s
					<span class="description">画像選択と画像URL両方に指定がある場合、画像選択が優先されます。</span>
				</div>
				<label class="media-editor__label">
					画像URL<br />
					<input type="text" class="media-editor__input" name="image" value="%4$s" />
				</label>
				<label class="media-editor__label">
					リンク先URL<br />
					<input type="text" class="media-editor__input" name="url" value="%5$s" />
				</label>
HTML;
			$html = sprintf(
				$html,
				esc_attr( $object['text_1'] ),
				esc_attr( $object['text_2'] ),
				$image_input ,
				esc_attr( $object['image'] ),
				esc_attr( $object['url'] )
			);
		}

		return sprintf( $wrapper, $type, $html );
	}

	/**
	 * スクリプトを読み込む
	 *
	 * @param string $page
	 */
	public function enqueue_scripts( $page ) {
		if ( false !== strpos( $page, 'sk-media-footer' ) ) {
			wp_enqueue_script( 'akagi-media-helper', get_template_directory_uri() . '/assets/js/admin/footer-media-helper.js', [
				'jquery-ui-sortable',
				'jquery-ui-autocomplete',
				'jquery-effects-highlight',
			], sk_theme_version(), true );
		}
	}

}
