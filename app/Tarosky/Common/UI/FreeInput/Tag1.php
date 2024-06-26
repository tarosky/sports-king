<?php

namespace Tarosky\Common\UI\FreeInput;


class Tag1 extends Taxonomy {

	protected $taxonomies = [ 'post_tag' ];

	protected static $name = '_free1';

	protected $title = '自由入稿枠１';

	protected $description = 'このカテゴリーに所属する記事の本文直下に表示されます。';

	protected $nonce_key = '_free1nonce';

}
