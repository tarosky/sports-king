<?php

namespace Tarosky\Common\UI\Screen;
use Tarosky\Common\UI\Helper\RichInput;


class PageBuilder extends ScreenBase {
	use RichInput;

	protected $slug = 'sk-page-builder';

	protected $capability = 'edit_others_posts';

	protected $position = 60;

	protected $title = 'ピックアップ';

	protected $menu_title = 'ページ要素管理';

	protected $icon = 'dashicons-tagcloud';

	protected $is_root = true;

	protected $submenu_title = 'ピックアップ';

	public function admin_init() {
		if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
			add_action( 'wp_ajax_sk_pickup_search', [ $this, 'ajax_search' ] );
			add_action( 'wp_ajax_sk_pickup_save', [ $this, 'ajax_save' ] );
		}
	}

	/**
	 * インクリメンタルサーチ
	 */
	public function ajax_search() {
		try {
			if ( ! current_user_can( 'edit_others_posts' ) ) {
				throw new \Exception( 'この操作をする権限がありません。', 403 );
			}
			if ( ! ( $term = $this->input->get( 'term' ) ) ) {
				throw new \Exception( '検索語が指定されていません。', 400 );
			}
			wp_send_json( array_map( function ( $post ) {
				return [
					'name'     => sprintf(
						'%s: %s（%s）%s',
						mysql2date( 'Y.m.d', $post->post_date ),
						get_the_title( $post ),
						get_post_type_object( $post->post_type )->label,
						false !== array_search( $post->post_status, [
							'publish',
							'future',
						] ) ? '' : $this->post_status( $post->post_status )
					),
					'template' => $this->get_pickup_template( $post->ID ),
				];
			}, get_posts( [
				'post_type'      => 'any',
				'posts_per_page' => 10,
				's'              => $term,
				'post_status'    => [ 'publish', 'future' ],
				'orderby'        => [
					'date' => 'DESC',
				],
			] ) ) );
		} catch ( \Exception $e ) {
			status_header( $e->getCode() );
			wp_send_json( [
				'status' => $e->getCode(),
				'message'  => $e->getMessage(),
			] );
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
			if ( ! $this->input->verify_nonce( 'save_pickup' ) ) {
				throw new \Exception( '不正なアクセスです。', 401 );
			}
			update_option( 'pickup_objects', $this->input->post( 'data' ) );
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
		$pickups = sk_get_pickups( false );
		?>
		<div>
			<div class="pickup-editor__form">
				<h3 class="pickup-editor__form--title">投稿を挿入する</h3>
				<select>
					<?php foreach ( range( 1, 3 ) as $index ) : ?>
						<option value="<?= $index ?>"><?= $index ?>列目に</option>
					<?php endforeach; ?>
				</select><br/>
				<input type="text" class="regular-text" id="pickup-editor-search"
				       data-action="<?= admin_url( 'admin-ajax.php?action=sk_pickup_search' ) ?>"
				       placeholder="入力してサイトから検索……"/>
			</div>


			<form class="pickup-editor__form" id="pickup-editor-link">
				<h3 class="pickup-editor__form--title">リンクを挿入する</h3>
				<label>
					<select>
						<?php foreach ( range( 1, 3 ) as $index ) : ?>
							<option value="<?= $index ?>"><?= $index ?>列目に</option>
						<?php endforeach; ?>
					</select>
				</label>
				<input type="submit" class="button" value="リンクを挿入"/>
				<script type="text/template" class="pickup-editor__template">
					<?= $this->get_pickup_template( [] ) ?>
				</script>
			</form>

			<hr style="clear: both;"/>
		</div>
		<div class="updated">
			<p>
				PC版ではすべてのピックアップ要素が表示されます。
				スマホではそれぞれの列より1つがランダム（等確率）で表示されます。
				<strong>ボタンを押して上下左右に動かすことができます。</strong>
			</p>
		</div>
		<div class="pickup-editor">
			<?php for ( $i = 0; $i < 3; $i ++ ) : ?>
				<div class="pickup-editor__line clearfix">
					<?php
					if ( isset( $pickups[ $i ] ) && is_array( $pickups[ $i ] ) ) {
						foreach ( $pickups[ $i ] as $object ) {
							echo $this->get_pickup_template( $object );
						}
					}
					?>
				</div><!-- //pickup-editor__line -->
			<?php endfor; ?>
		</div>

		<p class="submit">
			<button id="pickup-submit" class="button-primary" data-endpoint="<?= wp_nonce_url(admin_url('admin-ajax.php?action=sk_pickup_save'), 'save_pickup') ?>">保存</button>
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
	public function get_pickup_object( $object ) {
		if ( is_numeric( $object ) ) {
			return get_post( $object );
		} else {
			return array_merge( [
				'url'   => '',
				'image_id' => 0,
				'image' => '',
				'title' => '',
			    'external' => false,
			], (array) $object );
		}
	}

	/**
	 * データからレイアウトを作成する
	 *
	 * @param array|int $object
	 *
	 * @return string
	 */
	protected function get_pickup_template( $object ) {
		$object  = $this->get_pickup_object( $object );
		$wrapper = <<<HTML
		<div class="pickup-editor__wrap" data-type="%s">%s
			<table class="pickup-editor__controller">
			<tr>
				<td>
					<a class="button moveLeft" href="#">◀</a>&nbsp;
				</td>
				<td>
					<a class="button moveRight" href="#">▶</a>&nbsp;
				</td>
				<td>
					<a class="button-delete" href="#">削除</a>&nbsp;
				</td>
				<td>
					<a class="button moveUp" href="#">▲</a>&nbsp;
				</td>
				<td>
					<a class="button moveDown" href="#">▼</a>&nbsp;
				</td>
			</tr>
			</table>
		</div>
HTML;
		if ( ! $object ) {
			$type = 'broken';
			$html = <<<HTML
			<p class="description">この投稿は存在しません。削除されたか、あやまってデータが混入した可能性があります。</p>
HTML;
		} elseif ( is_a( $object, 'WP_Post' ) ) {
			$type = 'post';
			$html = <<<'HTML'
			<input type="hidden" class="post_id" value="%1$d" />
			%2$s
		    <p>
		    	<strong class="pickup-editor__title">%3$s</strong>
		    	<small>%4$s</small>
		    </p>
		    <p>
		    	状態: %5$s %6$s
			</p>
HTML;
			$html = sprintf(
				$html,
				$object->ID,
				has_post_thumbnail( $object ) ? get_the_post_thumbnail( $object, 'thumbnail' ) : '',
				esc_html( get_the_title( $object ) ),
				esc_html( get_post_type_object( $object->post_type )->label ),
				$this->post_status( $object->post_status ),
				mysql2date( 'Y.m.d', $object->post_date )
			);
		} else {
			$type = 'link';
			
			$image_input = $this->image_input( 'image_id', intval($object['image_id']), 1, false );
			$image_input = str_replace('class="image-selector-input"', 'class="image-selector-input pickup-editor__input"', $image_input);
			$html = <<<'HTML'
				<label class="pickup-editor__label">
					タイトル<br />
					<input type="text" class="pickup-editor__input" name="title" value="%1$s" />
				</label>
				<div class="image_input">
					画像選択<br />
					%2$s
					<span class="description">画像選択と画像URL両方に指定がある場合、画像選択が優先されます。横200pxにリサイズされた画像がサイトに表示されます。</span>
				</div>
				<label class="pickup-editor__label">
					画像URL<br />
					<input type="text" class="pickup-editor__input" name="image" value="%3$s" />
				</label>
				<label class="pickup-editor__label">
					リンク先URL<br />
					<input type="text" class="pickup-editor__input" name="url" value="%4$s" />
				</label>
				<label class="pickup-editor__label">
					<input type="checkbox" name="external" value="1" %4$s/> 新規ウィンドウで開く
				</label>
HTML;
			$html = sprintf(
				$html,
				esc_attr( $object['title'] ),
				$image_input ,
				esc_attr( $object['image'] ),
				esc_attr( $object['url'] ),
				checked( $object['external'], true, false )
			);
		}

		return sprintf( $wrapper, $type, $html );
	}

	/**
	 * 投稿ステータスを日本語にして返す
	 *
	 * @param string $status
	 *
	 * @return string
	 */
	protected function post_status( $status ) {
		switch ( $status ) {
			case 'publish':
				return '公開中';
				break;
			case 'future':
				return '公開予定';
				break;
			default:
				$statuses = get_post_statuses();

				return isset( $statuses[ $status ] ) ? $statuses[ $status ] : $status;
				break;
		}
	}

	/**
	 * スクリプトを読み込む
	 *
	 * @param string $page
	 */
	public function enqueue_scripts( $page ) {
		if ( 'toplevel_page_sk-page-builder' == $page ) {
			wp_enqueue_script( 'akagi-pickup-helper', get_template_directory_uri() . '/assets/js/admin/pickup-helper.js', [
				'jquery-ui-sortable',
				'jquery-ui-autocomplete',
				'jquery-effects-highlight',
			], sk_theme_version(), true );
		}
	}

}
