<?php

namespace Tarosky\Common;

use Tarosky\Common\Pattern\Singleton;

/**
 * ブートストラップファイル
 *
 * @package sports-king
 */
abstract class AbstractBootstrap extends Singleton {

	/**
	 * 自動で読み込むクラスと名前空間の配列
	 *
	 * @return array{ dir:string, namespace:string, only_admin:bool }[]
	 */
	protected function autoloads() {
		$store = [];
		foreach ( [
			'Tarosky\\Common\\API\\Ajax'     => false,
			'Tarosky\\Common\\UI\\Screen'    => true,
			'Tarosky\\Common\\API\\Rest'     => false,
			'Tarosky\\Common\\API\\Rooter'   => false,
			'Tarosky\\Common\\Hooks'         => false,
		] as $namespace => $only_admin ) {
			$dir =  str_replace( 'Tarosky/Common', __DIR__, str_replace( '\\', '/', $namespace ) );
			if ( ! is_dir( $dir ) ) {
				continue;
			}
			if ( $only_admin && ! is_admin() ) {
				continue;
			}
			$store[] = [
				'dir'        => $dir,
				'namespace'  => $namespace,
				'only_admin' => $only_admin,
			];
		}
		return $store;
	}

	/**
	 * 自動読み込みを行う
	 *
	 * @return void
	 */
	protected function do_autoloads() {
		// Auto loader.
		foreach ( $this->autoloads() as $autoload ) {
			foreach ( scandir( $autoload['dir'] ) as $file ) {
				if ( preg_match( '#^([^_\.]+)\.php$#u', $file, $match ) ) {
					$class_name = $autoload['namespace'] . '\\' . $match[1];
					$reflection = new \ReflectionClass( $class_name );
					if ( ! $reflection->isAbstract() && $reflection->hasMethod( 'instance' ) ) {
						$class_name::instance();
					}
				}
			}
		}
	}

	/**
	 * コマンドの名前空間
	 *
	 * @return array{dir:string, namespace:string}[]
	 */
	protected function command_namespace() {
		return [
			[
				'dir'       => __DIR__ . DIRECTORY_SEPARATOR . 'Commands',
				'namespace' => 'Tarosky\\Common\\Commands',
			],
		];
	}

	/**
	 * ここで依存性を注入する
	 *
	 * @return void
	 */
	protected function inject_dependencies() {
		// Do something here.
	}

	/**
	 * Bootstrap constructor.
	 *
	 * @param array $settings 設定項目
	 */
	public function __construct( array $settings = [] ) {
		$this->inject_dependencies();
		$this->do_autoloads();
		// アセット登録
		add_action( 'init', [ $this, 'register_assets' ] );
		// アセット読み込み
		add_action( 'admin_enqueue_scripts', [ $this, 'admin_enqueue_scripts' ] );


		// コマンドを自動登録
		if ( defined( 'WP_CLI' ) && \WP_CLI ) {
			$commands = $this->command_namespace();
			foreach ( $commands as $command ) {
				if ( ! is_dir( $command['dir'] ) ) {
					continue 1;
				}
				foreach ( scandir( $command['dir'] ) as $file ) {
					if ( preg_match( '#^([^_\.]+)\.php$#u', $file, $match ) ) {
						$class_name = 'Tarosky\\Common\\Commands\\' . $match[1];
						if ( class_exists( $class_name ) ) {
							$reflection = new \ReflectionClass( $class_name );
							if ( ! $reflection->isAbstract() && $reflection->isSubclassOf( 'Tarosky\\Common\\Command' ) ) {
								/**
								 * @var Command $class_name
								 */
								\WP_CLI::add_command( $class_name::COMMAND_NAME, $class_name );
							}
						}
					}
				}
			}
		}
	}

	/**
	 * 必要なアセットを登録する
	 *
	 * @return void
	 */
	public function register_assets() {
		// jQuery Tokeninput
		// todo: もうPublic Archivedなので、select2に変更する
		// see: https://github.com/loopj/jquery-tokeninput
		wp_register_script( 'jquery-tokeninput', $this->get_root_directory_uri() . '/assets/js/admin/jquery.tokeninput.js', [ 'jquery' ], '1.6.1', true );
		// 管理画面用CSS
		list( $url, $hash ) = $this->asset_info( '/assets/css/admin/sports-admin.css' );
		wp_register_style( 'sports-admin', $url, [], $hash, 'screen' );
		// Relation Manager
		list( $url, $hash ) = $this->asset_info( '/assets/js/admin/relation-helper.js' );
		wp_register_script( 'sk-relation-helper', $url, [ 'jquery-tokeninput', 'jquery-ui-dialog' ], $hash, 'screen' );
		// 画像セレクター
		list( $url, $hash ) = $this->asset_info( '/assets/js/admin/image-selector.js' );
		wp_register_script( 'image-selector', $url, [ 'jquery' ], $hash, true );
		// URLサジェスト
		list( $url, $hash )= $this->asset_info( '/assets/js/admin/url-suggest.js' );
		wp_register_script( 'url-selector', $url, [ 'jquery-ui-autocomplete' ], $hash, true );
		wp_localize_script( 'url-selector', 'SkUrlSuggest', [
			'endpoint' => admin_url( 'admin-ajax.php?action=sk_search_link' ),
		] );
		// リンクヘルパー
		list( $url, $hash ) = $this->asset_info( '/assets/js/admin/link-helper.js' );
		wp_register_script( 'sk-link-helper', $url, [ 'jquery-ui-autocomplete', 'jquery-effects-highlight' ], $hash, true );
		// メディアボックス
		list( $url, $hash ) = $this->asset_info( '/assets/js/admin/media-box.js' );
		wp_register_script( 'media-box', $url, [ 'jquery-effects-highlight', 'jquery-ui-sortable' ], $hash, true );
		// 関連メディア
		list( $url, $hash ) = $this->asset_info( '/assets/js/admin/related-media-helper.js' );
		wp_register_script( 'related-media', $url, [ 'jquery-ui-autocomplete', 'jquery-effects-highlight' ], $hash, true );
		// 置換
		list( $url, $hash ) = $this->asset_info( '/assets/js/admin/replace-helper.js' );
		wp_register_script('sk-replacements-helper', $url, ['jquery-effects-highlight'], $hash, true);
		// デリバリー
		list( $url, $hash ) = $this->asset_info( '/assets/js/admin/delivery-helper.js' );
		wp_register_script( 'sk-delivery-helper', $url, [ 'jquery' ], $hash, true );
	}

	/**
	 * スポーツ関連のスタイルシート
	 *
	 * @return void
	 */
	public function admin_enqueue_scripts() {
		// 管理画面用共通スタイル
		wp_enqueue_style( 'sports-admin' );
	}

	/**
	 * URLとバージョニング用の配列を返す
	 *
	 * @param string $rel_path_from_root
	 * @return string[] [ url, hash ]の配列
	 */
	protected function asset_info( $rel_path_from_root ) {
		$path = $this->get_root_dir() . $rel_path_from_root;
		$url  = $this->get_root_directory_uri() . $rel_path_from_root;
		return [ $url, md5_file( $path ) ];
	}

	/**
	 * ルートのディレクトリを取得する
	 *
	 * @return string
	 */
	protected function get_root_dir() {
		return untrailingslashit( dirname( __DIR__, 3 ) );
	}

	/**
	 * ルートのURLを取得する
	 *
	 * @return string
	 */
	protected function get_root_directory_uri() {
		$base_dir = $this->get_root_dir();
		if ( str_contains( $base_dir, get_theme_root() ) ) {
			// テーマ内にある
			return str_replace( get_theme_root(), get_theme_root_uri(), $base_dir );
		} elseif ( str_contains( $base_dir, WP_PLUGIN_DIR ) ) {
			// プラグインに含まれている
			return plugin_dir_url( $base_dir . '/plugin.php' );
		} elseif ( str_contains( $base_dir, WPMU_PLUGIN_DIR ) ) {
			// muプラグインに含まれている
			return str_replace( WPMU_PLUGIN_DIR, WPMU_PLUGIN_URL, $base_dir );
		} else {
			return str_replace( ABSPATH, home_url( '/' ), $base_dir );
		}
	}
}
