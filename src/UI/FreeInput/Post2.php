<?php

namespace Tarosky\Common\UI\FreeInput;


class Post2 extends Post {

	protected $post_types = [ 'post', 'bk_best_member' ];

	protected static $name = '_free2';

	protected $title = '自由入稿枠２';

	protected $description = 'スライドショーの後に表示されます。';

	protected $nonce_key = '_free2nonce';

}
