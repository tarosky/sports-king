<?php

namespace Tarosky\Common\Tarosky\Common\UI\FreeInput;


class Category1 extends Taxonomy {

	protected $taxonomies = [ 'category' ];

	protected static $name = '_free1';

	protected $title = '自由入稿枠１';

	protected $description = 'このカテゴリーに所属する記事の本文直下に表示されます。';

	protected $nonce_key = '_free1nonce';

}
