<?php

namespace Tarosky\Common\UI\FreeInput;


class Post4 extends Post {

	protected $post_types = [ 'post', 'bk_best_member' ];

	protected static $name = '_free4';

	protected $title = '自由入稿枠４';

	protected $description = '記事本文の１段落目と２段落目の間に表示されます';

	protected $nonce_key = '_free4nonce';

}
