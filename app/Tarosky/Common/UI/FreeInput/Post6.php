<?php

namespace Tarosky\Common\Tarosky\Common\UI\FreeInput;


class Post6 extends Post {

	protected $post_types = [ 'post', 'bk_best_member' ];

	protected static $name = '_free6';

	protected $title = '自由入稿枠６';

	protected $description = '記事ページのbodyに表示されます';

	protected $nonce_key = '_free6nonce';

}
