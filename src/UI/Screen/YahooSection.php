<?php

namespace Tarosky\Common\UI\Screen;


use Tarosky\BasketBallKing\Service\Yahoo\YahooNews;
use Tarosky\BasketBallKing\Service\Yahoo\YahooPremium;

class YahooSection extends ScreenBase {

	protected $slug = 'yahoo-options';

	protected $capability = 'manage_options';

	protected $title = 'Yahoo! 連携';

	protected $parent = 'options-general.php';

	public function admin_init() {
		if ( $this->input->verify_nonce( 'yahoo-options', '_yahoononce' ) ) {
			// 保存する
			update_option( 'yahoo_sync', (bool) $this->input->post( 'yahoo-sync' ) );
			wp_redirect( admin_url( "{$this->parent}?page={$this->slug}&updated=true" ) );
		}
	}

	/**
	 * 管理画面を描画する
	 */
	protected function render() {
		$yahoo = [
			'news' => YahooNews::instance(),
		    'premium' => YahooPremium::instance(),
		];
		$news = YahooNews::instance();
		$premium = YahooPremium::instance();
		?>
		<form method="post" action="<?= admin_url($this->parent) ?>">
			<input type="hidden" name="page" value="<?= $this->slug ?>">
			<?php wp_nonce_field( 'yahoo-options', '_yahoononce' ) ?>
			<table class="form-table">
				<tr>
					<th>配信設定</th>
					<td>
						<label>
							<input type="checkbox" name="yahoo-sync" value="1"<?php checked( $news->can_sync() ) ?>>
							Yahoo! にニュースを配信する
						</label>
					</td>
				</tr>
			</table>
			<?php submit_button( '更新' ) ?>
		</form>
		<?php
	}

}
