<?php

namespace Tarosky\Common\UI\Screen;
use Tarosky\Common\UI\Helper\RichInput;


class AddsHeader extends ScreenBase {
	use RichInput;

	protected $slug = 'bbk-adds-header';

	protected $capability = 'edit_others_posts';

	protected $parent = 'sk-page-builder';

	protected $title = 'ヘッダータグ';

	protected $menu_title = 'ヘッダータグ';

	public function admin_init() {
		if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
			add_action( 'wp_ajax_bbk_adds_save', [ $this, 'ajax_save' ] );
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
			if ( ! $this->input->verify_nonce( 'save_header' ) ) {
				throw new \Exception( '不正なアクセスです。', 401 );
			}

			update_option( 'adds_header_tags', stripslashes_deep($this->input->post( 'data' )) );
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
		$tag_labels = [
			'top' => 'TOPページ',
			'cat' => 'カテゴリページ',
			'db' => 'データベース（チーム・プレイヤー・スタッツ）',
			'feature' => '記事ページ',
		];

		$add_list = bbk_get_adds_header( );
		?>
		<div class="adds-editor">
			<div class="adds-editor__line clearfix">
				<?php
					foreach ( $add_list as $add_key => $tags ) {
						$label = strtoupper($add_key);
echo <<<HTML
			<div class="adds-editor">
				<h2>{$label}</h2>
HTML;
						foreach ( $tags as $tag_key => $tag ) {
							$name = sprintf( 'tag_%s_%s', strtolower($add_key), $tag_key );
echo <<<HTML
				<div class="adds-editor__wrap" data-type="link">
					<label class="adds-editor__label">
						{$tag_labels[$tag_key]}タグ<br />
						<textarea class="adds-editor__input" name="{$name}" rows="4" cols="40">{$tag}</textarea><br>
					</label>
				</div>
HTML;
						}
echo <<<HTML
			</div>
HTML;
					}
				?>
			</div><!-- //adds-editor__line -->
		</div>
		<p class="description">
			発行されたJavascriptタグなどを直接貼ってください。ここに入力された値はエスケープされず出力されますので、セキュリティには十分注意してください。
		</p>

		<p class="submit">
			<button id="adds-submit" class="button-primary" data-endpoint="<?= wp_nonce_url(admin_url('admin-ajax.php?action=bbk_adds_save'), 'save_header') ?>">保存</button>
		</p>

		<?php
	}

	/**
	 * スクリプトを読み込む
	 *
	 * @param string $page
	 */
	public function enqueue_scripts( $page ) {
		if ( false !== strpos( $page, 'bbk-adds-header' ) ) {
			wp_enqueue_script( 'akagi-adds-header', get_template_directory_uri() . '/src/js/admin/adds-header.js', [
				'jquery-ui-sortable',
				'jquery-ui-autocomplete',
				'jquery-effects-highlight',
			], sk_theme_version(), true );
		}
	}

}
