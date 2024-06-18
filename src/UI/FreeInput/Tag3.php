<?php

namespace Tarosky\Common\UI\FreeInput;


class Tag3 extends Taxonomy {

	protected $taxonomies = [ 'post_tag' ];

	protected static $name = '_free3';

	protected $title = '自由入稿枠３';

	protected $description = 'このカテゴリーに所属する記事中のシェアボタンの手前に表示されます。';

	protected $nonce_key = '_free3nonce';

}
