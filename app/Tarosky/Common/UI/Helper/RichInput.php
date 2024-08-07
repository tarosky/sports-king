<?php

namespace Tarosky\Common\UI\Helper;

/**
 * リッチな入力エリアを追加するTrait
 *
 * @package Tarosky\Common\UI\Helper
 */
trait RichInput {


	/**
	 * URLサジェスト機能付きのinputを表示
	 *
	 * @param string $name
	 * @param string $value
	 * @param string $placeholder
	 */
	protected function url_input( $name, $value, $placeholder = 'URLを入力してください' ) {
		if ( $post_id = url_to_postid( $value ) ) {
			// 特定の投稿
			$title = get_the_title( $post_id );
		} elseif ( false !== strpos( $value, home_url() ) ) {
			// 内部サイト
			$title = get_bloginfo( 'name' );
		} else {
			// 外部サイト
			$title = '';
		}
		wp_enqueue_script( 'url-selector' );
		?>
		<input type="text" class="regular-text url-suggest" name="<?php echo esc_attr( $name ); ?>"
				id="<?php echo esc_attr( $name ); ?>"
				placeholder="<?php echo esc_attr( $placeholder ); ?>" value="<?php echo esc_attr( $value ); ?>"
				data-internal="<?php echo esc_url( home_url() ); ?>"/>
		<a class="button url-suggest-button" href="#">確認</a>
		<small class="url-suggest-status">内部</small>
		<?php
	}

	/**
	 * 画像を保存するUI
	 *
	 * @param string $name
	 * @param string $value CSV of image ids.
	 * @param int $max
	 */
	protected function image_input( $name, $value, $max = 1, $is_display = true ) {
		wp_enqueue_media();
		wp_enqueue_script( 'image-selector' );
		$image_ids = array_filter( array_map( 'trim', explode( ',', $value ) ), function ( $id ) {
			return is_numeric( $id );
		} );
		$images    = array();
		if ( $image_ids ) {
			$images = get_posts( array(
				'post_type'      => 'attachment',
				'post__in'       => $image_ids,
				'post_mime_type' => 'image',
			) );
		}
		$width  = esc_attr( get_option( 'thumbnail_size_w', 150 ) );
		$height = esc_attr( get_option( 'thumbnail_size_h', 150 ) );

		$html    = <<<HTML
<div class="image-selector">
HTML;
		$name    = esc_attr( $name );
		$max     = max( 1, $max );
		$id_list = esc_attr( implode( ',', $image_ids ) );
		foreach ( $images as $image ) :
			$src     = wp_get_attachment_image_src( $image->ID )[0];
			$data_id = esc_attr( $image->ID );
			$html   .= <<<HTML
		<div class="image-selector-container">
			<img class="image-selector-picture" src="{$src}"
				 data-id="{$data_id}"/>
			<a class="button image-selector-delete" href="#">削除</a>
		</div>
HTML;
	endforeach;
		$html .= <<<HTML
	<p class="image-selector-place-holder"
	   style="width: {$width}px; height: {$height}px;"></p>
	<input type="hidden" name="{$name}" class="image-selector-input"
		   data-max="{$max}" value="{$id_list}">
	<p>
		<a class="button image-selector-button" href="#">選択</a>
		<small class="description">最大{$max}つまで</small>
	</p>
</div>
HTML;
		if ( $is_display ) {
			echo $html;
		} else {
			return $html;
		}
	}
}
