<?php

namespace Tarosky\Common\Tarosky\Common\UI\FreeInput;


class Tag5 extends Taxonomy {

	protected $taxonomies = [ 'post_tag' ];

	protected static $name = '_free5';

	protected $title = '自由入稿枠５';

	protected $description = 'このカテゴリーに所属する記事中のサイドバー上部に表示されます';

	protected $nonce_key = '_free5nonce';

}
