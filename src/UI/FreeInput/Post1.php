<?php

namespace Tarosky\Common\UI\FreeInput;


class Post1 extends Post {

	protected $post_types = [ 'post', 'bk_best_member' ];

	protected static $name = '_free1';

	protected $title = '自由入稿枠１';

	protected $description = '記事直下に表示されます。';

	protected $nonce_key = '_free1nonce';

}
