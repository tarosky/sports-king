<?php

namespace Tarosky\Common\Tarosky\Common\UI\FreeInput;


class Category3 extends Taxonomy {

	protected $taxonomies = [ 'category' ];

	protected static $name = '_free3';

	protected $title = '自由入稿枠３';

	protected $description = 'このカテゴリーに所属する記事中のシェアボタンの手前に表示されます。';

	protected $nonce_key = '_free3nonce';

}
