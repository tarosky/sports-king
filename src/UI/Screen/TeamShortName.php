<?php

namespace Tarosky\Common\UI\Screen;


use Tarosky\Common\Models\Replacements;
use Tarosky\Common\UI\Table\TeamShortNameTable;
use Tarosky\Common\Utility\Input;

class TeamShortName extends ScreenBase {

	protected $title = 'チーム短縮名';

	protected $parent = 'edit.php?post_type=team';

	protected $slug = 'sk_team_short_name';

	protected $capability = 'edit_posts';

	/**
	 * 画面を表示する
	 */
	protected function render() {
		?>
		<p class="description">海外チームの短縮名（4文字）を入力してください。空白にした場合は、先頭から4文字が表示されます。</p>
		<form id="sk-replacements" action="<?= admin_url( 'edit.php' ) ?>" method="get"
		      data-endpoint="<?= admin_url( 'admin-ajax.php' ) ?>" data-nonce="<?= wp_create_nonce( 'sk_team_short_name' ) ?>">
			<input type="hidden" name="post_type" value="team">
			<input type="hidden" name="page" value="<?= $this->slug ?>">
			<?php
			ob_start();
			$table = new TeamShortNameTable();
			$table->prepare_items();
			$table->search_box( '検索', 's' );
			$table->views();
			$table->display();
			$out = ob_get_contents();
			ob_end_clean();
			// ノンスのリファラーを削除
			$out = preg_replace( '#<input type="hidden" name="_wp_http_referer"[^>]*>#', '', $out );
			echo $out;
			?>
		</form>
		<?php
	}

	/**
	 * Ajaxで更新する
	 */
	public function admin_init() {
		if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
			add_action( 'wp_ajax_sk_team_short_name', function(){
				try {
					if ( ! $this->input->verify_nonce( 'sk_team_short_name' ) ) {
						throw new \Exception( '不正なアクセスです。', 401 );
					}
					$orig = $this->input->post( 'orig' );
					$new  = $this->input->post( 'replace' );
					if ( ! Replacements::instance()->update_short_name( $orig, $new ) ) {
						throw new \Exception( 'データの保存に失敗しました', 500 );
					}
					wp_send_json([
						'message' => $new ? '保存しました' : '削除しました',
					]);
				} catch ( \Exception $e ) {
					status_header( $e->getCode() );
					wp_send_json( [
						'error' => true,
					    'status' => $e->getCode(),
					    'message' => $e->getMessage(),
					] );
				}
			} );
		}
	}

	/**
	 * JSを読み込み
	 *
	 * @param string $page
	 */
	public function enqueue_scripts( $page ) {
		if ( false !== strpos( $page, 'sk_team_short_name' ) ) {
			wp_enqueue_script( 'short-team-helper', get_template_directory_uri().'/assets/js/admin/short-team-helper.js', ['jquery-effects-highlight'], sk_theme_version() ,true );
		}
	}
}
