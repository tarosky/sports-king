<?php

namespace Tarosky\Common\UI\Screen;


use Tarosky\Common\UI\Table\MatchTable;

class MatchSchedule extends ScreenBase {

	protected $title = 'スケジュール';

	protected $parent = 'edit.php?post_type=team';

	protected $slug = 'sk_match_list';

	protected $capability = 'edit_posts';

	/**
	 * 画面を表示する
	 */
	protected function render() {
	    ob_start();
		?>
		<p class="description">
			DataStadiumからインポートしたスケジュールを表示しています。
		</p>
        <form action="<?= admin_url( 'edit.php' ) ?>" method="get">
            <input type="hidden" name="post_type" value="team">
            <input type="hidden" name="page" value="sk_match_list">
        <?php
        $table = new MatchTable();
        $table->prepare_items();
        $table->search_box( '検索', 's' );
        $table->display();
        ?>
        </form>
        <?php
        $content = ob_get_contents();
        ob_end_clean();
        $content = preg_replace( '#<input type="hidden" name="_wp_http_referer" value="[^"]+" />#u', '', $content );
        echo $content;
	}

	public function admin_init() {
	}

	public function enqueue_scripts( $page ) {
	}


}
