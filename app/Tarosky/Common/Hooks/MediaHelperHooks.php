<?php

namespace Tarosky\Common\Hooks;


use Tarosky\Common\Pattern\HookPattern;

/**
 * メディア一覧を改善する
 *
 */
class MediaHelperHooks extends HookPattern {

	/**
	 * {@inheritDoc}
	 */
	protected function register_hooks(): void {
		add_filter( 'manage_upload_columns', [ $this, 'attachments_column' ] );
		add_action( 'manage_media_custom_column', [ $this, 'attachment_column_content' ], 10, 2 );
	}

	/**
	 * 添付ファイルのカラムを追加する
	 *
	 * @param string[] $columns カラム名
	 * @return string[]
	 */
	public function attachments_column( $columns ) {
		if (  'list' !== filter_input( INPUT_GET, 'mode' ) ) {
			return $columns;
		}
		$new_columns = [];
		foreach ( $columns as $key => $label ) {
			$new_columns[ $key ] = $label;
			if ( 'parent' === $key ) {
				$new_columns['usage'] = __( '利用状況', 'sk' );
			}
		}
		return $new_columns;
	}

	/**
	 * 添付ファイルにカラムを追加する
	 *
	 * @param string $column  カラム名
	 * @param int    $post_id 添付ファイルID
	 *
	 * @return void
	 */
	public function attachment_column_content( $column, $post_id ) {
		if ( 'usage' !== $column || 'list' !== filter_input( INPUT_GET, 'mode' ) ) {
			return;
		}
		// 利用状況を出力する
		$parent = get_post_parent( $post_id );
		if ( ! $parent ) {
			echo '<span style="color: lightgray;">---</span>';
			return;
		}
		// 投稿タイプ名を取得
		$link = esc_url( add_query_arg( [
			'mode'        => 'list',
			'post_parent' => $parent->ID,
		], admin_url( 'upload.php' ) ) );
		printf(
			'<a href="%s">%s</a>',
			$link,
			esc_html__( '投稿に添付済み', 'sk' )
		);
		if ( get_query_var( 'post_parent' ) ) {
			printf(
				' | <small><a href="%s">%s</a></small>',
				esc_url( admin_url( 'upload.php?mode=list' ) ),
				__( '親の選択を解除', 'sk' )
			);
		}
		// サムネイル利用
		if ( has_post_thumbnail( $parent ) && $post_id === get_post_thumbnail_id( $parent ) ) {
			echo '<br /><span style="color: green;">' . __( 'アイキャッチ画像として利用', 'sk' ) . '</span>';
			return;
		}
		// 本文に利用している可能性を模索
		$urls = [
			wp_get_attachment_url( $post_id ),
		];
		if ( str_contains( get_post_mime_type( $post_id ), 'image/' ) ) {
			foreach ( get_intermediate_image_sizes() as $size ) {
				$url = wp_get_attachment_image_url( $post_id, $size );
				if ( $url ) {
					$urls[] = $url;
				}
			}
		}
		$found = false;
		foreach ( $urls as $url ) {
			if ( str_contains( $parent->post_content, $url ) ) {
				echo '<br /><span style="color: green;">' . __( '投稿本文に利用', 'sk' ) . '</span>';
				$found = true;
				break;
			}
		}
		if ( $found ) {
			return;
		}
		// カスタムフィールドに存在している可能性を模索
		$posts_custom = get_post_custom( $parent->ID );
		foreach ( $posts_custom as $key => $values ) {
			foreach ( $values as $value ) {
				if ( ! is_numeric( $value ) ) {
					if ( str_contains( $value, "i:{$post_id};" ) ) {
						$found = true;
						break 2;
					} elseif ( preg_match( "@s\d+:'{$post_id}';@", $value ) ) {
						$found = true;
						break 2;
					}
					continue 1;
				}
				if ( $post_id === (int) $value ) {
					// 添付ファイルのIDが同じ
					$found = true;
					break 2;
				}
			}
		}
		if ( $found ) {
			echo '<br /><span style="color: green;">' . __( 'カスタムフィールドに利用の可能性', 'sk' ) . '</span>';
		}
	}
}
