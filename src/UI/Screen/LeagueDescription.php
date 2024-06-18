<?php

namespace Tarosky\Common\UI\Screen;


use Tarosky\BasketBallKing\Statics\Leagues;

class LeagueDescription extends ScreenBase {

	protected $title = 'リーグの説明';

	protected $parent = 'edit.php?post_type=team';

	protected $slug = 'sk_league_detail';

	protected $capability = 'edit_posts';

	/**
	 * 画面を表示する
	 */
	protected function render() {
		?>
		<p class="description">
			各リーグ、トーナメントの昇格・降格ルールなどについての説明文を記載してください。
		</p>
		<form action="<?= admin_url( 'edit.php' ) ?>" method="post">
			<input type="hidden" name="post_type" value="team">
			<input type="hidden" name="page" value="sk_league_detail">
			<?php wp_nonce_field( 'udpate_league_desc', '_leaguedescnonce' ); ?>
			<?php foreach ( [ '0' => '国内', '1' => '海外' ] as $abroad => $nation ) : ?>
				<h2><?= $nation ?></h2>

				<div class="sk-tabs">
					<ul>
						<?php foreach ( Leagues::LEAGUES[$abroad] as $league_id => $league) :
						$id = ($abroad ? 'abroad_' : 'national_') .$league_id;
						?>
						<li><a href="#<?= esc_attr( $id ) ?>-tab"><?= esc_html( $league['label'] ) ?></a></li>
						<?php endforeach; ?>
					</ul>
					<?php foreach ( Leagues::LEAGUES[$abroad] as $league_id => $league) :
						$id = ($abroad ? 'abroad_' : 'national_') .$league_id;
						$value = sk_get_league_desc( $abroad, $league_id );
					?>
						<div id="<?= esc_attr( $id ) ?>-tab">
							<?php wp_editor( $value, $id ) ?>
						</div>

					<?php endforeach; ?>
					<?php submit_button( '保存', 'primary', 'submit-'.$id ) ?>
				</div>

			<?php endforeach; ?>
		</form>
		<script>
			jQuery(document).ready(function($){
				$('.sk-tabs').tabs();
			});
		</script>
		<?php
	}

	public function admin_init() {
		if( $this->input->verify_nonce( 'udpate_league_desc', '_leaguedescnonce' ) ){
			foreach ( Leagues::LEAGUES as $abroad => $leagues ) {
				foreach ( $leagues as $league_id => $league ) {
					$id = ( $abroad ? 'abroad_' : 'national_' ) .$league_id;
					update_option( 'sk_league_'.$id, $this->input->post( $id ) );
				}
			}
			wp_safe_redirect( admin_url( 'edit.php?post_type=team&page='.$this->slug ) );
			exit;
		}
	}

	public function enqueue_scripts( $page ) {
		wp_enqueue_script( 'jquery-ui-tabs' );
		wp_enqueue_style( 'jquery-ui-mp6' );
	}


}
