<?php

namespace Tarosky\Common\Hooks;

use Tarosky\Common\Pattern\HookPattern;

/**
 * パンクズリストの共通処理
 */
class BreadCrumbHooks extends HookPattern {

	/**
	 * {@inheritDoc}
	 */
	protected function register_hooks(): void {
		add_action( 'bcn_after_fill', array( $this, 'add_pagination_link' ) );
		add_action( 'bcn_after_fill', array( $this, 'customize_attachment_breadcrumb' ) );
		add_filter( 'bcn_pick_post_term', array( $this, 'set_main_league' ), 10, 4 );
		// 指定したリーグがパンクズに出ないように
		add_action( 'league_edit_form_fields', [ $this, 'edit_form' ], 100, 2 );
		add_action( 'edited_term', [ $this, 'update_term' ], 10, 3 );
	}

	/**
	 * パンクズリストにページネーションが追加されていた場合、一つ前をリンクに戻す
	 *
	 * @param \bcn_breadcrumb_trail $bcn パンクズリストオブジェクト。
	 */
	public function add_pagination_link( \bcn_breadcrumb_trail $bcn ) {
		/** @var \bcn_breadcrumb $paginater */
		$paginater = $bcn->trail[0] ?? null;
		if ( ! $paginater || ! in_array( 'news-archive-paginated', $paginater->get_types(), true ) ) {
			// 該当しない。
			return;
		}
		/** @var \bcn_breadcrumb $original_link */
		$original_link = $bcn->trail[1] ?? null;
		if ( ! $original_link || ! in_array( 'current-item', $original_link->get_types(), true ) ) {
			// 該当しない。
			return;
		}
		// リンクさせる
		$bcn->trail[1] = new \bcn_breadcrumb( $original_link->get_title(), null, array_filter( $original_link->get_types(), function ( $type ) {
			return 'current-item' !== $type;
		} ), $original_link->get_url(), $original_link->get_id(), true );
	}

	/**
	 * attachmentページのパンくずリストに限り、attachmentページ自身ではなくattachmentページの親投稿へのパンくずを出力する。
	 *
	 * @param \bcn_breadcrumb_trail $bcn パンクズリストオブジェクト。
	 */
	public function customize_attachment_breadcrumb( \bcn_breadcrumb_trail $bcn ) {
		if ( ! is_attachment() ) {
			// 該当しない。
			return;
		}
		// attachmentページのパンくずを最後の項目のみ削除する
		// array_shift( $bcn->trail );
		// パンくずの最後の項目を、ここで新しく作るパンくずで置き換える
		// $types にcurrent-item を指定することによってパンくずの最後の項目のみ右矢印を無くす
		/** @var \bcn_breadcrumb $item */
		$item          = $bcn->trail[0]; // パンくずは末尾のものから順に配列trailに格納されている
		$types         = $item->get_types();
		$types[]       = 'current-item';
		$bcn->trail[0] = new \bcn_breadcrumb( sk_get_attachment_page_title(), null, $types, '', $item->get_id(), false );
	}

	/**
	 * teamは複数のリーグに所属するので、リーグの優先度が高いものを選ぶ
	 *
	 * @param bool|\WP_Term $term      選択されたターム
	 * @param int          $post_id   投稿ID
	 * @param string       $post_type 投稿タイプ
	 * @param string       $taxonomy  タクソノミー
	 * @return bool|\WP_Term
	 */
	public function set_main_league( $term, $post_id, $post_type, $taxonomy ) {
		if ( 'team' !== $post_type || 'league' !== $taxonomy ) {
			// 該当しない
			return $term;
		}
		if ( ! function_exists( 'sk_get_main_league' ) ) {
			return $term;
		}
		return sk_get_main_league( $post_id ) ?: $term;
	}

	/**
	 * タグの編集画面をカスタマイズする
	 *
	 * @param \WP_Term $term     ターム
	 * @param string   $taxonomy タクソノー
	 *
	 * @return void
	 */
	public function edit_form( $term, $taxonomy ) {
		?>
		<tr>
			<th><?php esc_html_e( 'パンくずリストの表示', 'sk' ); ?></th>
			<td>
				<?php wp_nonce_field( 'sk_update_bc_display', '_skleaguebcoption', false ); ?>
				<label>
					<input type="checkbox" value="1" name="breadcrumb_display" <?php checked( '1', get_term_meta( $term->term_id, 'breadcrumb_display', true ) ); ?> />
					<?php esc_html_e( 'このリーグをパンくずリストに表示しない', 'sk' ); ?>
				</label>
			</td>
		</tr>
		<?php
	}

	/**
	 * パンクズ設定を保存する
	 *
	 * @param int    $term_id  Term ID.
	 * @param int    $tt_id    Term taxonomy ID.
	 * @param string $taxonomy タクソノミー名
	 *
	 * @return void
	 */
	public function update_term( $term_id, $tt_id, $taxonomy ) {
		if ( 'league' !== $taxonomy ) {
			return;
		}
		if ( ! wp_verify_nonce( filter_input( INPUT_POST, '_skleaguebcoption' ), 'sk_update_bc_display' ) ) {
			// nonceが不正
			return;
		}
		if ( filter_input( INPUT_POST, 'breadcrumb_display' ) ) {
			update_term_meta( $term_id, 'breadcrumb_display', 1 );
		} else {
			delete_term_meta( $term_id, 'breadcrumb_display' );
		}
	}
}
