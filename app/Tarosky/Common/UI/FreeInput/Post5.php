<?php

namespace Tarosky\Common\Tarosky\Common\UI\FreeInput;


class Post5 extends Post {

	protected $post_types = [ 'post', 'bk_best_member' ];

	protected static $name = '_free5';

	protected $title = '自由入稿枠５';

	protected $description = '記事ページのheadに表示されます';

	protected $nonce_key = '_free5nonce';

}
