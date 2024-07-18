<?php

namespace Tarosky\Common\UI\FreeInput;


use Tarosky\Common\Pattern\Singleton;
use Tarosky\Common\Utility\Input;
use Tarosky\Common\UI\Helper\RichInput;

/**
 * Class FreeInputModule
 *
 * もとはシングルトン実装だったが、継承クラスが再現なく増えるので、次のような実装にした。
 * 1. static::instance() だとシングルトン実装で1つのインスタンスを取得。
 * 2. static::generate( $setting ) だと設定を元にしたインスタンスを生成
 *
 * @package Tarosky\Common\UI
 * @property-read Input $input
 */
abstract class Module {

	use RichInput;

	private static $instances = array();

	/**
	 * Name of this field.
	 *
	 * @var string
	 */
	protected $name = '';

	/**
	 * ノンスが保存されているキー
	 *
	 * @var string
	 */
	protected $nonce_key = '_wpnonce';

	/**
	 * タイトル
	 *
	 * @var string
	 */
	protected $title = '自由入稿枠';

	/**
	 * 説明
	 *
	 * @var string
	 */
	protected $description = '';

	/**
	 * ノンスアクション
	 *
	 * @var string
	 */
	protected $nonce_action = 'edit_free_field';

	/**
	 * nonceをチェックする
	 *
	 * @return bool
	 */
	protected function verify() {
		return $this->input->verify_nonce( $this->nonce_action, $this->nonce_key );
	}

	/**
	 * 保存用データの形式に揃える
	 *
	 * @return array
	 */
	protected function normalize() {
		$lists     = array();
		$list_text = array_filter( (array) $this->input->post( $this->key( 'list_text' ) ), function ( $var ) {
			return ! empty( $var );
		} );
		$list_url  = array_filter( (array) $this->input->post( $this->key( 'list_url' ) ), function ( $var ) {
			return ! empty( $var );
		} );
		foreach ( $list_text as $index => $text ) {
			$lists[] = array(
				'text' => $text,
				'url'  => $list_url[ $index ],
			);
		}
		$data = array(
			'active' => $this->input->post( $this->key( 'active' ) ) ?: 'text',
			'text'   => array(
				'title' => (string) $this->input->post( $this->key( 'text_title' ) ),
				'url'   => (string) $this->input->post( $this->key( 'text_url' ) ),
			),
			'image'  => array(
				'id'      => (int) $this->input->post( $this->key( 'image_id' ) ),
				'url'     => (string) $this->input->post( $this->key( 'image_url' ) ),
				'caption' => (string) $this->input->post( $this->key( 'image_caption' ) ),
			),
			'list'   => $lists,
			'html'   => array(
				'content' => (string) $this->input->post( $this->key( 'html_content' ) ),
			),
		);

		return $data;
	}

	/**
	 * 必要なスクリプトを読み込む
	 */
	public function enqueue_scripts() {
		wp_enqueue_script( 'media-box' );
	}

	/**
	 * データを取得する
	 *
	 * @param \WP_Post|\WP_Term $object Object to get data.
	 *
	 * @return array
	 */
	public function get_data( $object ) {
		$data = $this->get_raw_data( $object );
		return $data[ $data['active'] ];
	}

	/**
	 * データをすべて取得する
	 *
	 * @param \WP_Post|\WP_Term $object Object to get data.
	 *
	 * @return mixed
	 */
	public function get_raw_data( $object ) {
		$data = $this->get_raw_data_from( $object );
		if ( $data ) {
			return $data;
		} else {
			return $this->get_default_value();
		}
	}

	/**
	 * データベースからデータを取得する
	 *
	 * @param {Object} $object
	 *
	 * @return array
	 */
	public function get_raw_data_from( $object ) {
		// Should override.
		return array();
	}

	/**
	 * デフォルトの値
	 *
	 * @return array
	 */
	protected function get_default_value() {
		return array(
			'active' => 'text',
			'text'   => array(
				'title' => '',
				'url'   => '',
			),
			'image'  => array(
				'id'      => 0,
				'caption' => '',
				'url'     => '',
			),
			'list'   => array(),
			'html'   => array(
				'content' => '',
			),
		);
	}

	/**
	 * キー名を取得する
	 *
	 * @param string $name
	 *
	 * @return string
	 */
	protected function key( $name ) {
		return esc_attr( $this->name . '_' . $name );
	}

	/**
	 * フォームを表示する
	 *
	 * @param \WP_Post|\WP_Term $object Object data
	 */
	public function show_ui( $object ) {
		wp_nonce_field( $this->nonce_action, $this->nonce_key, false, true );
		$data = $this->get_raw_data( $object );
		?>
		<div class="freeInput__selector">
			<?php
			foreach (
				array(
					'text'  => 'テキスト',
					'image' => '画像',
					'list'  => 'リスト',
					'html'  => 'HTML',
				) as $key => $label
			) :
				?>
				<label class="freeInput__label">
					<input type="radio" name="<?php echo $this->key( 'active' ); ?>"
							value="<?php echo esc_attr( $key ); ?>" <?php checked( $key === $data['active'] ); ?> />
					<span class="freeInput__label--border"></span>
					<span class="freeInput__label--text"><?php echo esc_html( $label ); ?></span>
				</label>
			<?php endforeach; ?>
			<div style="clear:both;"></div>
		</div>


		<div class="freeInput__tab" data-type="text">
			<table class="form-table freeInput__table">
				<tr>
					<th>
						<label for="<?php echo $this->key( 'text_title' ); ?>">テキスト</label>
					</th>
					<td>
						<textarea rows="2" name="<?php echo $this->key( 'text_title' ); ?>"
									id="<?php echo $this->key( 'text_title' ); ?>"><?php echo esc_textarea( $data['text']['title'] ); ?></textarea>
					</td>
				</tr>
				<tr>
					<th><label<?php echo $this->key( 'text_url' ); ?>>URL</label></th>
					<td>
						<?php $this->url_input( $this->key( 'text_url' ), $data['text']['url'] ); ?>
					</td>
				</tr>
			</table>
		</div><!-- // .freeInput__tab -->


		<div class="freeInput__tab" data-type="image">
			<table class="form-table freeInput__table">
				<tr>
					<th><label for="<?php echo $this->key( 'image_id' ); ?>">画像ファイル</label></th>
					<td>
						<?php $this->image_input( $this->key( 'image_id' ), $data['image']['id'], 1 ); ?>
					</td>
				</tr>
				<tr>
					<th><label for="<?php echo $this->key( 'image_url' ); ?>">URL</label></th>
					<td>
						<?php $this->url_input( $this->key( 'image_url' ), $data['image']['url'] ); ?>
					</td>
				</tr>
				<tr>
					<th>
						<label for="<?php echo $this->key( 'image_caption' ); ?>">
							キャプション
							<small class="description">（任意）</small>
						</label>
					</th>
					<td>
						<textarea rows="2" name="<?php echo $this->key( 'image_caption' ); ?>"
									id="<?php echo $this->key( 'image_caption' ); ?>"><?php echo esc_textarea( $data['image']['caption'] ); ?></textarea>
					</td>
				</tr>
			</table>
		</div><!-- // .freeInput__tab -->


		<div class="freeInput__tab" data-type="list">
			<p class="freeInput__controller--list">
				<a class="button freeInput__list-add">追加</a>
			</p>
			<ul class="freeInput__list">
				<?php foreach ( $data['list'] as $list ) : ?>
					<li>
						<p>
							<label>
								<small>テキスト:</small>
								<br/>
								<input type="text" class="regular-text" name="<?php echo $this->key( 'list_text[]' ); ?>"
										value="<?php echo esc_attr( $list['text'] ); ?>"/>
							</label>
						</p>
						<p>
							<label>
								<small>URL:</small>
								<br/>
								<?php $this->url_input( $this->key( 'list_url[]' ), $list['url'] ); ?>
							</label>
						</p>
						<a class="close" href="#"><span class="dashicons dashicons-dismiss"></span></a>
						<a class="drag" href="#"><span class="dashicons dashicons-menu"></span></a>
					</li>
				<?php endforeach; ?>

			</ul>
			<script type="text/template" class="freeInput__template">
				<li>
					<p>
						<label>
							<small>テキスト:</small>
							<br/>
							<input type="text" class="regular-text" name="<?php echo $this->key( 'list_text[]' ); ?>"
									value=""/>
						</label>
					</p>
					<p>
						<label>
							<small>URL:</small>
							<br/>
							<?php $this->url_input( $this->key( 'list_url[]' ), '' ); ?>
						</label>
					</p>
					<a class="close" href="#"><span class="dashicons dashicons-dismiss"></span></a>
					<a class="drag" href="#"><span class="dashicons dashicons-menu"></span></a>
				</li>
			</script>
		</div><!-- // .freeInput__tab -->


		<div class="freeInput__tab" data-type="html">
			<p class="freeInput__notice">
				この項目にはHTMLを自由に入稿できます。
				外部サービスのエンベッドタグなどを目的にしていますので、
				<strong>複雑なレイアウトのHTMLを入力しない</strong>よう注意してください。
			</p>
			<textarea class="freeInput__html" id="$data['html']['content']" rows="10"
						name="<?php echo $this->key( 'html_content' ); ?>"
						id="<?php echo $this->key( 'html_content' ); ?>"><?php echo esc_textarea( isset( $data['html']['content'] ) ? $data['html']['content'] : '' ); ?></textarea>
		</div><!-- // .freeInput__tab -->
		<?php
	}

	/**
	 * Singleton実装のフォールバック
	 *
	 * @return static
	 */
	public static function instance() {
		$class_name = get_called_class();
		if ( ! isset( self::$instances[ $class_name ] ) ) {
			self::$instances[ $class_name ] = new $class_name();
		}
		return self::$instances[ $class_name ];
	}

	/**
	 * Getter
	 *
	 * @param string $name
	 *
	 * @return null|static
	 */
	public function __get( $name ) {
		switch ( $name ) {
			case 'input':
				return Input::instance();
				break;
			default:
				return null;
				break;
		}
	}

	/**
	 * 設定を元にインスタンスを作成する
	 *
	 * @param array $setting 設定
	 * @return static
	 */
	public static function generate( $setting ) {
		$class_name = get_called_class();
		$instance   = new $class_name( $setting );
		return $instance;
	}

	/**
	 * コンストラクタ
	 *
	 * @param array $setting
	 */
	protected function __construct( $setting = array() ) {
		if ( ! empty( $setting ) ) {
			$this->set_up( $setting );
		}
	}

	/**
	 * 設定を自身のインスタンスに割り当てる
	 *
	 * @param array $setting 設定
	 *
	 * @return mixed
	 */
	abstract protected function set_up( $setting );
}
