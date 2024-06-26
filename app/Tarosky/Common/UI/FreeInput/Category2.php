<?php

namespace Tarosky\Common\UI\FreeInput;


class Category2 extends Taxonomy {

	protected $taxonomies = [ 'category' ];

	protected static $name = '_free2';

	protected $title = '自由入稿枠２';

	protected $description = 'このカテゴリーに所属する記事のカテゴリー情報最下部に表示されます。';

	protected $nonce_key = '_free2nonce';

}
