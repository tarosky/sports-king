<?php

namespace Tarosky\Common\UI\Screen;


use Tarosky\BasketBallKing\API\FeedManager;

class FeedList extends ScreenBase {

	protected $slug = 'feed-list';

	protected $capability = 'manage_options';

	protected $title = 'RSS一覧';

	protected $parent = 'options-general.php';

	/**
	 * 管理画面を描画する
	 */
	protected function render() {
		$feed = FeedManager::instance();

		echo ("<ul>");
		foreach ( $feed->get_partners() as $key => $media ) {
			$url = sprintf( "%s/rss/%s.xml", home_url(), $key );
			echo ( sprintf( "<li>%s：<a href='%s' target='_blank'>%s</a></li>", $media, $url, $url ));
		}
		echo ("</ul>");
	}

}
