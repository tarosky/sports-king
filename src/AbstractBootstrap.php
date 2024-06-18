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
			'Tarosky\\Common\\UI\\FreeInput' => false,
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
	 * Bootstrap constructor.
	 *
	 * @param array $settings 設定項目
	 */
	public function __construct( array $settings = [] ) {
		$this->do_autoloads();

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
}
