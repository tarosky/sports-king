<?php

namespace Tarosky\Common\UI\Screen;
use Tarosky\Common\UI\Helper\RichInput;


class HeaderSet extends ScreenBase {
    use RichInput;

    protected $slug = 'bbk-header-set';

    protected $capability = 'edit_others_posts';

    protected $parent = 'sk-page-builder';

    protected $title = 'ヘッダー項目設定';

    protected $menu_title = 'ヘッダー項目';

    public function admin_init() {
        if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
            add_action( 'wp_ajax_bbk_header_set_save', [ $this, 'ajax_save' ] );
        }
    }

    /**
     * 情報を保存する
     */
    public function ajax_save() {

        try {
            if ( ! current_user_can( 'edit_others_posts' ) ) {
                throw new \Exception( 'この操作をする権限がありません。', 403 );
            }
            if ( ! $this->input->verify_nonce( 'save_media' ) ) {
                throw new \Exception( '不正なアクセスです。', 401 );
            }

            update_option( 'heasder_set_objects', $this->input->post( 'data' ) );
            wp_send_json( [
                'message' => '設定を保存しました。',
            ] );
        } catch ( \Exception $e ) {
            status_header( $e->getCode() );
            wp_send_json([
                'status' => $e->getCode(),
                'message' => $e->getMessage(),
            ]);
        }
    }

    /**
     * 管理画面を描画する
     */
    protected function render() {
        $header_sets = bbk_get_header_set( );
        ?>
        <div class="updated">
            <p>
                ヘッダーメニューに表示する「動画」「特集」「お知らせ」の設定を行える。
            </p>
        </div>
        <div class="media-editor">
            <div class="media-editor__line clearfix">
                <?php
                foreach ( $header_sets as $key => $object ) {
                    echo $this->get_media_template( $object, $key );
                }
                ?>
            </div><!-- //media-editor__line -->
        </div>

        <p class="submit">
            <button id="header-set-submit" class="button-primary" data-endpoint="<?= wp_nonce_url(admin_url('admin-ajax.php?action=bbk_header_set_save'), 'save_media') ?>">保存</button>
        </p>

        <?php
    }

    /**
     * ピックアップオブジェクトを返す
     *
     * @param int|array $object
     *
     * @return array|null|\WP_Post
     */
    public function get_media_object( $object ) {
        return array_merge( [
            'url'   => '',
            'image_id' => 0,
            'newly' => 0,
        ], (array) $object );
    }

    /**
     * データからレイアウトを作成する
     *
     * @param array|int $object
     *
     * @return string
     */
    protected function get_media_template( $object, $index ) {
        $object  = $this->get_media_object( $object );
        $wrapper = <<<HTML
		<div class="media-editor__wrap" data-type="%s">
			%s
		</div>
HTML;
        if ( ! $object ) {
            $type = 'broken';
            $html = <<<HTML
			<p class="description">この投稿は存在しません。削除されたか、あやまってデータが混入した可能性があります。</p>
HTML;
        } else {
            $type = 'link';

            $image_input = $this->image_input( 'image_id', intval($object['image_id']), 1, false );
            $image_input = str_replace('class="image-selector-input"', 'class="image-selector-input media-editor__input"', $image_input);
            $checked = $object['newly'] ? 'checked="checked"' : '' ;

            if ( $index === 0 ) {
                $target = '動画' ;
                $html = <<<'HTML'
				<h3>%1$s</h3>
				<div class="image_input">
					画像選択<br />
					%2$s
				</div>
				<label class="media-editor__label">
					リンク先URL<br />
					<input type="text" class="media-editor__input" name="url" value="%3$s" />
				</label>
				<label class="media-editor__label">
					新着ON<br />
					<input type="checkbox" class="media-editor__input" name="newly"
						   value="%4$s" %5$s />
				</label>
HTML;
            } else {
                $target = $index == 1 ? '特集' : 'お知らせ' ;
                $html = <<<'HTML'
				<h3>%1$s</h3>
				<label class="media-editor__label">
					リンク先URL<br />
					<input type="text" class="media-editor__input" name="url" value="%3$s" />
				</label>
				<label class="media-editor__label">
					新着ON<br />
					<input type="checkbox" class="media-editor__input" name="newly"
						   value="%4$s" %5$s />
				</label>
HTML;
            }
            $html = sprintf(
                $html,
                $target,
                $image_input,
                esc_attr( $object['url'] ),
                esc_attr( $object['newly'] ),
                $checked
            );
        }

        return sprintf( $wrapper, $type, $html );
    }

    /**
     * スクリプトを読み込む
     *
     * @param string $page
     */
    public function enqueue_scripts( $page ) {
        if ( false !== strpos( $page, 'bbk-header-set' ) ) {
            wp_enqueue_script( 'beckham-media-helper', get_template_directory_uri() . '/assets/js/admin/header-set-helper.js', [
                'jquery-ui-sortable',
                'jquery-ui-autocomplete',
                'jquery-effects-highlight',
            ], sk_theme_version(), true );
        }
    }

}
