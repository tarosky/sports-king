<?php

namespace Tarosky\Common\Tarosky\Common\UI\FreeInput;


class Post3 extends Post {

	protected $post_types = [ 'post', 'bk_best_member' ];

	protected static $name = '_free3';

	protected $title = '自由入稿枠3';

	protected $description = '記事中のシェアボタンの手前に表示されます。';

	protected $nonce_key = '_free3nonce';

}
