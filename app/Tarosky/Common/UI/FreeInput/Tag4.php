<?php

namespace Tarosky\Common\Tarosky\Common\UI\FreeInput;


class Tag4 extends Taxonomy {

	protected $taxonomies = [ 'post_tag' ];

	protected static $name = '_free4';

	protected $title = '自由入稿枠４';

	protected $description = 'このカテゴリーに所属する記事中の記事本文の１段落目と２段落目の間に表示されます';

	protected $nonce_key = '_free4nonce';

}
