<?php

namespace Tarosky\Common\UI\Screen;


use Tarosky\Common\Models\Replacements;
use Tarosky\Common\UI\Table\ReplacementsTable;
use Tarosky\Common\Utility\Input;

class ReplacementList extends ScreenBase {

	protected $title = '置換リスト';

	protected $parent = 'edit.php?post_type=team';

	protected $slug = 'sk_replacements';

	protected $capability = 'edit_posts';

	/**
	 * 画面を表示する
	 */
	protected function render() {
		?>
		<p class="description">
			外部から取得したデータに対し、名前の正規化に利用されるデータのリストです。
			データの提供元が変更された場合、新たな固有名詞（人名・場所名）が追加された場合にご利用ください。
		</p>
		<form id="sk-replacements" action="<?= admin_url( 'edit.php' ) ?>" method="get"
		      data-endpoint="<?= admin_url('admin-ajax.php') ?>" data-nonce="<?= wp_create_nonce('sk_edit_repl') ?>">
			<input type="hidden" name="post_type" value="team">
			<input type="hidden" name="page" value="sk_replacements">
			<input type="hidden" name="view" value="<?= esc_attr( Input::instance()->get('view') ) ?>">
		<?php
			ob_start();
			$table = new ReplacementsTable();
			$table->prepare_items();
			$table->views();
			$table->search_box( '検索', 's' );
			$table->display();
			$out = ob_get_contents();
			ob_end_clean();
			// ノンスのリファラーを削除
			$out = preg_replace('#<input type="hidden" name="_wp_http_referer"[^>]*>#', '', $out);
			echo $out;
		?>
		</form>
		<form id="sk-replacement-add">
			<hr />
			<h3>新規追加</h3>
			<table class="form-table">
				<tr>
					<th>
						<label for="sk-type">種別</label>
					</th>
					<td>
						<select id="sk-type" name="sk-type">
							<?php foreach( Replacements::instance()->types as $type => $label ) : ?>
							<option value="<?= esc_attr($type) ?>"><?= esc_html($label) ?></option>
							<?php endforeach; ?>
						</select>
					</td>
				</tr>
				<tr>
					<th>
						<label for="sk-orig">置換前</label>
					</th>
					<td>
						<input type="text" class="regular-text" name="sk-orig" id="sk-orig" value="" placeholder="ex. From 1 Stadium" />
					</td>
				</tr>
				<tr>
					<th>
						<label for="sk-replaced">置換後</label>
					</th>
					<td>
						<input type="text" class="regular-text" name="sk-replaced" id="sk-replaced" value="" placeholder="ex. フロムワン・スタジアム" />
					</td>
				</tr>
			</table>
			<?php submit_button('新規作成') ?>
		</form>
		<?php
	}

	public function admin_init() {
		if( defined('DOING_AJAX') && DOING_AJAX ){
			add_action('wp_ajax_sk_replace_edit', [$this, 'ajax']);
		}
	}

	/**
	 * 保存と削除
	 */
	public function ajax(){
		try{
			$json = [
				'success' => true,
			    'message' => '',
			];
			// 利用するインスタンスを取得
			$input = Input::instance();
			$model = Replacements::instance();
			if( ! $input->verify_nonce('sk_edit_repl') ){
				throw new \Exception('不正なアクセスです。', 400);
			}
			if( ! current_user_can('edit_posts') ) {
				throw new \Exception('あなたにその操作は許可されていません。', 403);
			}
			$type = $input->post('type');
			switch( $type ){
				case 'save':
					$id = $input->post('id');
					$from = $input->post('from');
					$to = $input->post('to');
					if( ! $model->change($id, $from, $to) ){
						throw new \Exception('保存できませんでした。', 500);
					}
					break;
				case 'delete':
					$id = $input->post('id');
					if( ! $model->get($id) ){
						throw new \Exception('該当する置換設定は存在しません。', 404);
					}
					if( ! $model->remove($id) ){
						throw new \Exception('削除できませんでした。', 500);
					}
					break;
				case 'create':
					$type = $input->post('repl_type');
					$from = $input->post('repl_from');
					$to = $input->post('repl_to');
					if( !isset( $model->types[$type] ) ){
						throw new \Exception('指定されたタイプは存在しません。', 400);
					}
					if( empty($from) || empty($to) ){
						throw new \Exception('文字列が指定されていません。', 400);
					}
					if( ! $model->create($type, $from, $to) ){
						throw new \Exception('作成に失敗しました。', 500);
					}
					break;
				default:
					throw new \Exception('正しい操作が選択されていません。', 400);
					break;
			}
			wp_send_json($json);
		}catch(\Exception $e){
			status_header($e->getCode());
			wp_send_json( [
				'success' => true,
			    'error' => $e->getMessage(),
			] );
		}
	}


	/**
	 * 読み込み
	 *
	 * @param string $page
	 */
	public function enqueue_scripts( $page ) {
		if ( false !== strpos($page, '_sk_replacements') ) {
			wp_enqueue_script('sk-replacements-helper' );
		}

	}


}
