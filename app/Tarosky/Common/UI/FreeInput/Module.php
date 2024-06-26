<?php

namespace Tarosky\Common\Tarosky\Common\UI\FreeInput;


use Tarosky\Common\Tarosky\Common\Pattern\Singleton;
use Tarosky\Common\Tarosky\Common\UI\Helper\RichInput;
use Tarosky\Common\Tarosky\Common\Utility\Input;
use function Tarosky\Common\UI\FreeInput\checked;
use function Tarosky\Common\UI\FreeInput\esc_attr;
use function Tarosky\Common\UI\FreeInput\esc_html;
use function Tarosky\Common\UI\FreeInput\esc_textarea;
use function Tarosky\Common\UI\FreeInput\wp_enqueue_script;
use function Tarosky\Common\UI\FreeInput\wp_nonce_field;

/**
 * Class FreeInputModule
 * @package Tarosky\Common\UI
 * @property-read Input $input
 */
abstract class Module extends Singleton {

	use RichInput;

	/**
	 * Name of this field.
	 *
	 * @var string
	 */
	protected static $name = '';

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
		$lists     = [];
		$list_text = array_filter( (array) $this->input->post( $this->key( 'list_text' ) ), function ( $var ) {
			return ! empty( $var );
		} );
		$list_url  = array_filter( (array) $this->input->post( $this->key( 'list_url' ) ), function ( $var ) {
			return ! empty( $var );
		} );
		foreach ( $list_text as $index => $text ) {
			$lists[] = [
				'text' => $text,
				'url'  => $list_url[ $index ],
			];
		}
		$data = [
			'active' => $this->input->post( $this->key( 'active' ) ) ?: 'text',
			'text'   => [
				'title' => (string) $this->input->post( $this->key( 'text_title' ) ),
				'url'   => (string) $this->input->post( $this->key( 'text_url' ) ),
			],
			'image'  => [
				'id'      => (int) $this->input->post( $this->key( 'image_id' ) ),
				'url'     => (string) $this->input->post( $this->key( 'image_url' ) ),
				'caption' => (string) $this->input->post( $this->key( 'image_caption' ) ),
			],
			'list'   => $lists,
			'html'   => [
				'content' => (string) $this->input->post( $this->key( 'html_content' ) ),
			],
		];

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
	public static function get_data( $object ) {
		$data = static::get_raw_data( $object );
		return $data[ $data['active'] ];
	}

	/**
	 * データをすべて取得する
	 *
	 * @param \WP_Post|\WP_Term $object Object to get data.
	 *
	 * @return mixed
	 */
	public static function get_raw_data( $object ) {
		$data = static::get_raw_data_from( $object );
		if ( $data ) {
			return $data;
		} else {
			return static::get_default_value();
		}
	}

	/**
	 * データベースからデータを取得する
	 *
	 * @param {Object} $object
	 *
	 * @return array
	 */
	public static function get_raw_data_from( $object ) {
		// Should override.
		return [];
	}

	/**
	 * デフォルトの値
	 *
	 * @return array
	 */
	protected static function get_default_value() {
		return [
			'active' => 'text',
			'text'   => [
				'title' => '',
				'url'   => '',
			],
			'image'  => [
				'id'      => 0,
				'caption' => '',
				'url'     => '',
			],
			'list'   => [ ],
			'html'   => [
				'content' => '',
			],
		];
	}

	/**
	 * キー名を取得する
	 *
	 * @param string $name
	 *
	 * @return string
	 */
	protected function key( $name ) {
		return esc_attr( static::$name . '_' . $name );
	}

	/**
	 * フォームを表示する
	 *
	 * @param \WP_Post|\WP_Term $object Object data
	 */
	public function show_ui( $object ) {
		wp_nonce_field( $this->nonce_action, $this->nonce_key, false, true );
		$data = static::get_raw_data( $object );
		?>
		<div class="freeInput__selector">
			<?php
			foreach (
				[
					'text'  => 'テキスト',
					'image' => '画像',
					'list'  => 'リスト',
					'html'  => 'HTML',
				] as $key => $label
			) :
				?>
				<label class="freeInput__label">
					<input type="radio" name="<?= $this->key( 'active' ) ?>"
					       value="<?= esc_attr( $key ) ?>" <?php checked( $key === $data['active'] ) ?> />
					<span class="freeInput__label--border"></span>
					<span class="freeInput__label--text"><?= esc_html( $label ) ?></span>
				</label>
			<?php endforeach; ?>
			<div style="clear:both;"></div>
		</div>


		<div class="freeInput__tab" data-type="text">
			<table class="form-table freeInput__table">
				<tr>
					<th>
						<label for="<?= $this->key( 'text_title' ) ?>">テキスト</label>
					</th>
					<td>
						<textarea rows="2" name="<?= $this->key( 'text_title' ) ?>"
						          id="<?= $this->key( 'text_title' ) ?>"><?= esc_textarea( $data['text']['title'] ) ?></textarea>
					</td>
				</tr>
				<tr>
					<th><label<?= $this->key( 'text_url' ) ?>>URL</label></th>
					<td>
						<?php $this->url_input( $this->key( 'text_url' ), $data['text']['url'] ) ?>
					</td>
				</tr>
			</table>
		</div><!-- // .freeInput__tab -->


		<div class="freeInput__tab" data-type="image">
			<table class="form-table freeInput__table">
				<tr>
					<th><label for="<?= $this->key( 'image_id' ) ?>">画像ファイル</label></th>
					<td>
						<?php $this->image_input( $this->key( 'image_id' ), $data['image']['id'], 1 ) ?>
					</td>
				</tr>
				<tr>
					<th><label for="<?= $this->key( 'image_url' ) ?>">URL</label></th>
					<td>
						<?php $this->url_input( $this->key( 'image_url' ), $data['image']['url'] ) ?>
					</td>
				</tr>
				<tr>
					<th>
						<label for="<?= $this->key( 'image_caption' ) ?>">
							キャプション
							<small class="description">（任意）</small>
						</label>
					</th>
					<td>
						<textarea rows="2" name="<?= $this->key( 'image_caption' ) ?>"
						          id="<?= $this->key( 'image_caption' ) ?>"><?= esc_textarea( $data['image']['caption'] ) ?></textarea>
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
								<input type="text" class="regular-text" name="<?= $this->key( 'list_text[]' ) ?>"
								       value="<?= esc_attr( $list['text'] ) ?>"/>
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
							<input type="text" class="regular-text" name="<?= $this->key( 'list_text[]' ) ?>"
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
				この項目にはHTMLを自由に入稿することができます。
				外部サービスのエンベッドタグなどを目的にしていますので、
				<strong>複雑なレイアウトのHTMLを入力しない</strong>よう注意してください。
			</p>
			<textarea class="freeInput__html" id="$data['html']['content']" rows="10"
			          name="<?= $this->key( 'html_content' ) ?>"
			          id="<?= $this->key( 'html_content' ) ?>"><?= esc_textarea( isset( $data['html']['content']) ? $data['html']['content'] :"" ) ?></textarea>
		</div><!-- // .freeInput__tab -->
		<?php
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

}
