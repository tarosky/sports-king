<?php

namespace Tarosky\Common\Hooks;

use Tarosky\Common\Pattern\HookPattern;

/**
 * データスタジアム接続設定
 */
class DataStudiumHooks extends HookPattern {

    /**
     * レジスター
     */
	protected function register_hooks(): void {
        add_action( 'admin_menu', [ $this, 'add_ds_screen' ], 40 );
        add_action( 'admin_init', [ $this, 'register_settings' ] );
	}

    /**
     * データスタジアム用の設定内容
     */
    public function register_settings() {
        if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
            return;
        }

        // データスタジアム接続方法
        add_settings_section( 'ds_options', __( 'データスタジアム設定', 'sk' ), '__return_false', 'sk_ds_options' );
        add_settings_field( 'sk_use_ftp', __( '接続方法', 'sk' ), function () {
            $use_ftp    = get_option( 'sk_use_ftp' );
            $ftp_checkd = $use_ftp == '1' ? 'checked' : '' ;
            ?>
            <div>
                <input type='checkbox' id='sk_ds_conn_ftp' name='sk_use_ftp' value='1' <?php echo $ftp_checkd; ?> />
                <label for='sk_ds_conn_ftp'>FTPで接続する</label>
            </div>
            <?php
        }, 'sk_ds_options', 'ds_options' );
        register_setting( 'sk_ds_options', 'sk_use_ftp' );
    }

    /**
     * データスタジアム用の管理画面を登録する
     *
     * @return void
     */
    public function add_ds_screen() {
        add_options_page( __( 'データスタジアム通信設定', 'sk' ), __( 'データスタジアム設定', 'sk' ), 'manage_options', 'sk_ds_options', [ $this, 'ds_options_page' ] );
    }

    /**
     * データスタジアム用の設定ページ
     *
     * @return void
     */
    public function ds_options_page() {
        ?>
        <div class="wrap">
            <h2><?php esc_html_e( 'データスタジアム通信用オプション', 'sk' ); ?></h2>

            <form method="post" action="<?php echo admin_url( 'options.php' ); ?>">
                <?php
                settings_fields( 'sk_ds_options' );
                do_settings_sections( 'sk_ds_options' );
                submit_button();
                ?>
            </form>
        </div>

        <?php
    }

}
