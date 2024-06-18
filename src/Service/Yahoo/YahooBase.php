<?php

namespace Tarosky\Common\Service\Yahoo;


use Tarosky\Common\Pattern\Singleton;

/**
 * Yahooの接続情報を司るクラス
 *
 * @package Tarosky\Common\Service\Yahoo
 */
abstract class YahooBase extends Singleton {

	/**
	 * @var resource FTP接続
	 */
	protected $conn = null;

	/**
	 * @var string
	 */
	protected $media_id = 'bballk';

	/**
	 * @var array 商用接続情報 'host', 'user', 'pass'を持つ
	 */
	protected $production_credential = [];

	/**
	 * @var array 接続情報 'host', 'user', 'pass'を持つ
	 */
	protected $development_credential = [];

	/**
	 * @var string FTPのルートディレクトリ
	 */
	protected $root_dir = '/';

	/**
	 * @var null|\Exception
	 */
	private $error = null;


	/**
	 * コンストラクタ
	 *
	 * @param array $settings
	 */
	protected function __construct( array $settings ) {
		if ( $this->can_sync() ) {
			// 同期を登録
			add_action( 'transition_post_status', [ $this, 'transition_post_status' ], 10, 3 );
			// 同期がオンのときだけ実行される
			$this->init();
		}
	}

	/**
	 * 同期が有効なときに実行される関数
	 */
	protected function init() {
		// Executed when limit exceeds.
	}

	/**
	 * 投稿の状態が変更した時に実行
	 *
	 * @param string $new_status
	 * @param string $old_status
	 * @param \WP_Post $post
	 *
	 * @return mixed
	 */
	abstract public function transition_post_status($new_status, $old_status, $post);

	/**
	 * 疎通の確認
	 *
	 * @return array|\WP_Error
	 */
	public function check( $type ) {
		$credential = $this->get_credential( $type );
		if ( $this->connect( $credential ) ) {
			return ftp_nlist( $this->conn, '.' );
		} else {
			return new \WP_Error( $this->error->getCode(), $this->error->getMessage() );
		}
	}

	/**
	 * 指定した認証情報を取得する
	 *
	 * @param string $type
	 *
	 * @return array
	 */
	private function get_credential( $type = '' ) {
		switch ( $type ) {
			case 'production':
			case 'development':
				// Do nothing
				break;
			default:
				$type = $this->is_production() ? 'production' : 'development';
				break;
		}
		$property = "{$type}_credential";
		return $this->{$property};
	}


	/**
	 * 接続する
	 *
	 * @param array $credential
	 * @param int $port
	 * @param int $timeout
	 *
	 * @return bool
	 */
	protected function connect( $credential = [], $port = 21, $timeout = 15 ) {
		if ( is_null( $this->conn ) ) {
			try {
				if ( ! $credential ) {
					$credential = $this->get_credential();
				}
				$this->conn = @ftp_ssl_connect( $credential['host'], $port, $timeout );
				if ( ! $this->conn ) {
					throw new \Exception( 'FTP接続を開始できませんでした。', 500 );
				}
				$logged_in = @ftp_login( $this->conn, $credential['user'], $credential['pass'] );
				if ( ! $logged_in ) {
					throw new \Exception( 'FTPへのログインに失敗しました。', 401 );
				}
				// PASVモードにする
				$this->set_pasv( true );
				// ルートに移動
				ftp_chdir( $this->conn, $this->root_dir );
			} catch ( \Exception $e ) {
				$this->error = $e;
				return false;
			}
		}
		return true;
	}

	/**
	 * 本番環境であるかどうか
	 *
	 * @return bool
	 */
	public function is_production() {
		return false !== strpos( home_url(), 'https://basketballking.jp' );
	}

	/**
	 * 同期してもよいかどうか
	 *
	 * @return bool
	 */
	public function can_sync() {
		return (bool) get_option( 'yahoo_sync', false );
	}


	/**
	 * PASVモードの設定を行う
	 *
	 * @param bool $mode
	 *
	 * @return bool
	 */
	public function set_pasv( $mode = true ) {
		return ftp_pasv( $this->conn, $mode );
	}

	/**
	 * 接続を終了する
	 */
	public function close() {
		if ( $this->conn ) {
			ftp_close( $this->conn );
		}
	}

	/**
	 * リビジョンを取得する
	 *
	 * @param null|\WP_post|int $post
	 *
	 * @return int
	 */
	public function get_revision( $post = null ) {
		$post = get_post( $post );
		return (int) get_post_meta( $post->ID, '_yahoo_revision', true );
	}

	/**
	 * リビジョン番号を保存する
	 *
	 * @param int $revision
	 * @param null|\WP_post|int $post
	 *
	 * @return bool|int
	 */
	public function save_revision( $revision, $post = null ) {
	    $ret_meta_id = 0;
		$post = get_post( $post );
		if( !empty( $post->ID ) ) {
            $ret_meta_id = update_post_meta( $post->ID, '_yahoo_revision', (int) $revision );
        }
		return $ret_meta_id;
	}

	/**
	 * リトライの場合の名前
	 *
	 * @param null $post
	 *
	 * @return mixed
	 */
	public function retry_index( $post = null ) {
		$post = get_post( $post );
		return min( 9, (int) get_post_meta( $post->ID, '_yahoo_retry', true ) );
	}

	/**
	 * リトライ回数が上限を超えていないか
	 *
	 * @param null|\WP_Post $post
	 *
	 * @return bool
	 */
	public function can_retry( $post = null ) {
		return 9 > $this->retry_index( $post );
	}

	/**
	 * リトライ回数を保存する
	 *
	 * @param int $retry
	 * @param null $post
	 *
	 * @return bool|int
	 */
	public function save_retry( $retry, $post = null ) {
		$post = get_post( $post );
		return update_post_meta( $post->ID, '_yahoo_retry', $retry );
	}

	/**
	 * テキストデータを規定に従い置換する
	 *
	 * @param string $text
	 * @return string
	 */
	public function format( $text ) {
		//半角カタカナは使用しないでください
		$text = mb_convert_kana( $text, 'K', 'UTF-8' );
		//英字、数字はすべて半角で表記してください
		$text = mb_convert_kana( $text, 'r', 'UTF-8' );
		//特殊ダブルクォート「〝」「〟」は使用しないでください
		$text = str_replace( [ '〝', '〟' ], '', $text );
		//スラッシュ「/」は半角で表記してください
		$text = str_replace( '／', '/', $text );
		//記号類(「%」「&」「#」「$」)は全角で表記してください
		$text = str_replace( '%', '％', $text );
		$text = str_replace( '&', '＆', $text );
		$text = str_replace( '#', '＃', $text );
		$text = str_replace( '$', '＄', $text );
        //  制御文字を消す
        $text = preg_replace('@[\x00-\x09\x0B\x0C\x0E-\x1F\x7F]@', '', $text);
		//後のスペースを削除
		$text = rtrim( $text, ' ' );

		return $text;
	}

	/**
	 * ファイルを保存する
	 *
	 * @param string $name
	 * @param string $string
	 *
	 * @return bool|string
	 */
	public function get_temp_path( $name, $string ) {
		$path = sys_get_temp_dir().'/'.$name;
		if ( file_put_contents( $path, $string ) ) {
			return $path;
		} else {
			return false;
		}
	}

	/**
	 * ファイルをアップロードする
	 *
	 * @param array $files
	 *
	 * @return bool
	 */
	public function upload( $files ) {
		if ( $this->connect() ) {
			$success = true;
			ftp_chdir( $this->conn, '/feed' );
			foreach ( $files as $name => $path ) {
				if ( ! ftp_put( $this->conn, $name, $path, FTP_BINARY ) ) {
					$success = false;
				}
			}
			$this->close();
			return $success;
		} else {
//			サンプルファイル吐き出し用
/*
			$success = true;
			foreach ( $files as $name => $path ) {
				$send = '/tmp/sample/'.$name;
				if ( ! copy( $path, $send ) ) {
					$success = false;
				}
			}
			return $success;
 *
 */
			return false;
		}
	}

	/**
	 * XMLファイル名を取得する
	 *
	 * @param int $revision
	 * @param null|int|\WP_Post $post
	 * @param int $retry 初期値は0
	 *
	 * @return string
	 */
	public function get_file_name( $revision, $post = null, $retry = 0 ) {
		return sprintf(
			'%s-%08d-%s-%s.xml',
			mysql2date( 'Ymd', $post->post_date ),
			$this->get_item_id( $post, min( 9, $retry ) ),
			$this->media_id,
			$revision
		);
	}

	/**
	 * 記事IDを取得する
	 *
	 * @param \WP_Post $post
	 * @param int $retry
	 *
	 * @return string
	 */
	protected function get_item_id( $post, $retry = 0 ) {
		$post_id = sk_diff_id( $post->ID ) ?: $post->ID;
		return sprintf( '%d%07d', min( 9, $retry ), $post_id );
	}

	/**
	 * ファイルをFTPでアップロードする
	 *
	 * @param \WP_Post $post
	 * @param int $retry
	 * @param int|string $revision
	 * @param bool $pay
	 * @return bool
	 */
	public function upload_file( $post, $retry, $revision, $pay = false ) {
		$content_list = [];
		$files = [];
		$date_id = mysql2date( 'Ymd', $post->post_date );
		$item_id = $this->get_item_id( $post, $retry );
		$first_created = mysql2date( 'Y-m-d\TH:i:s', $post->post_date );
		$revision_created = mysql2date( 'Y-m-d\TH:i:s', $post->post_modified );
		// 記事は更新だけどyahooへの配信は一回目の場合,最終更新日を公開日にする
		if( $revision === 1 ) {
			$revision_created = $first_created;
		}
		// タイトル
		$post_title = $this->format( $post->post_title );
		// 本文
		$content = current( explode( '【関連記事】', $post->post_content ) );
        $exp_content = explode( "<!--nextpage-->", $content );
        $content = current( $exp_content );

        $next_heading_url = $next_heading_title = '';
        if( $next_content = !empty( $exp_content[1] ) ? $exp_content[1] : NULL ) {
            //  次ページの最初の見出しをペジネーションの見出しタイトルとして扱う
            preg_match_all("@<h[1-6].+?</h[1-6]>@u", $next_content, $match);
            if ( !empty( $match[0] ) ) {
                $next_heading_url = sprintf( '%s/2', get_permalink( $post->ID ) );
                $next_heading_title = strip_tags( $match[0][0] );
            }
        }

		$custom_content = $content;
		// captionを消す
		$custom_content = preg_replace( '#\[caption[^\]]*?](.*?)\[/caption\]#u', '', $custom_content );
		// ショートコードを消す
		$custom_content = strip_shortcodes( $custom_content );

		// twitterやinstagramのブロック引用を消す
		$custom_content = preg_replace_callback( '#<blockquote([^>]*?)>(.*?)</blockquote>#us', function($matches) {
			if ( false !== strpos( $matches[1], 'twitter' ) ) {
				return '';
			} elseif ( false !== strpos( $matches[1], 'instagram-media' ) ) {
				return '';
			} else {
				return $matches[0];
			}
		}, $custom_content );
		// OembedになりそうなURLだけの行を消す
		$custom_content = implode( "\n", array_filter( explode( "\r\n", $this->format( strip_tags( $custom_content, '<h2>' ) ) ), function( $row ) {
			return ! preg_match( '#^https?://[a-zA-Z0-9\.\-_/]+$#', $row );
		} ) );

		// 4行空白が続いたら圧縮
		$custom_content = preg_replace( '/\\n{3,}/', "\n\n", $custom_content );
		//  制御文字を消す
        $custom_content = preg_replace('@[\x00-\x09\x0B\x0C\x0E-\x1F\x7F]@', '', $custom_content);

		$summary = mb_substr( str_replace( [ "\r\n", "\r", "\n" ], '', $custom_content ), 0, 200 );

		//	本文の最初に見出しがない場合は付け足す
		if( !preg_match( '@^<h2>@s', $content ) ) {
			$content = "<h2></h2>".$content;
		}
		//	h2を境に行を分ける
		if( preg_match_all('@<h2>(.*?)</h2>@s', $content, $matches ) ) {

			$count = count($matches[0]);
			$check_content = $content;
			foreach( $matches[0] as $k => $m ){
				$pattern = '';
				if( ($k + 1) < $count ) {
					$pattern = sprintf( "@%s(.*?)%s@s", preg_quote($matches[0][$k], '/'), preg_quote($matches[0][$k+1], '/') );
				}
				else {
					$pattern = sprintf( "@%s(.*?)$@s", preg_quote($matches[0][$k], '/') );
				}


				if( preg_match_all( $pattern, $check_content, $match ) ){
					$content_list[] = [ $matches[1][$k], $match[1][0] ];
					$check_content = str_replace($matches[0][$k].$match[1][0], '', $check_content);
				}
			}
		}
		else {
			$content_list[] = [ '', $content ];
		}

		// 関連リンク
		$related_link = '';

		$image_links = sk_related_links( false, $post, true );
		$links = sk_related_links( false, $post );
        if( $image_links || $links || $next_heading_url ) {
			$related_link = '<RelatedLink>';
			$link_id = 0;

            if( $next_heading_url ) {

                $link_id++;
                $related_link .= <<<XML
		<Link Id="{$link_id}" Type="photo">
			<Url><![CDATA[{$next_heading_url}]]></Url>
			<Title><![CDATA[{$next_heading_title}]]></Title>
		</Link>
XML;

            } else if ( $image_links ) {

				foreach ( $image_links as $key => $link ) {
					if( $key >= 1 ) break;
					$link_id++;
					$url = $this->format( $link['url'] );
					$title = $this->format( $link['title'] );
					$related_link .= <<<XML
		<Link Id="{$link_id}" Type="photo">
			<Url><![CDATA[{$url}]]></Url>
			<Title><![CDATA[{$title}]]></Title>
		</Link>
XML;
				}
			}
			if ( $links ) {
				foreach ( $links as $key => $link ) {
					if( $key >= 5 ) break;
					$link_id++;
					$url = $this->format( $link['url'] );
					$title = $this->format( $link['title'] );
					$related_link .= <<<XML
		<Link Id="{$link_id}" Type="pc">
			<Url><![CDATA[{$url}]]></Url>
			<Title><![CDATA[{$title}]]></Title>
		</Link>
XML;
				}
			}
			$related_link .= '</RelatedLink>';
		}
		// 有料記事か否か
		$pay_flg = $pay ? "\n<Pay>1</Pay>" : '';
		// 本文を作成する
		$xml = <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<YJNewsFeed Version="1.0">
	<Identification>
		<MediaId>{$this->media_id}</MediaId>
		<DateId>{$date_id}</DateId>
		<NewsItemId>{$item_id}</NewsItemId>
		<RevisionId>{$revision}</RevisionId>
	</Identification>
	<Management>
		<FirstCreated>{$first_created}</FirstCreated>
		<ThisRevisionCreated>{$revision_created}</ThisRevisionCreated>
	</Management>
	<MetaData>{$pay_flg}
		<ArticleType>straight</ArticleType>
		<MediaFormat>text</MediaFormat>
		<Author>BASKETBALL KING</Author>
		<Priority>1</Priority>
		<SubjectCode>
			<Code>15000000</Code>
		</SubjectCode>
		<Property>
			<Item>
				<PropertyName>amanda</PropertyName>
				<Key>old_cid</Key>
				<Value>spo</Value>
			</Item>
		</Property>
	</MetaData>
	<NewsLines>
		<Headline><![CDATA[{$post_title}]]></Headline>
		<Summary><![CDATA[{$summary}]]></Summary>
	</NewsLines>
	<Article>
XML;
	$para_count = 1;
	$img_count = 0;
	foreach( $content_list as $content_val ) :

		$first_img = $thumbnail = $caption = $img_name = $sizes = '';
		$thumbnail_id = 0;
		$content = $content_val[1];
		// captionを消す
		if( preg_match( '#\[caption[^\]]*?](.*?)\[/caption\]#u', $content, $m ) ) {
			if(preg_match( '@"attachment_.*?"@', $m[0], $am) ){
				$exp = explode('_', $am[0]);
				$thumbnail_id = !empty($exp[1]) ? $exp[1]: 0 ;
			}
			$content = preg_replace( '#\[caption[^\]]*?](.*?)\[/caption\]#u', '', $content );
		}
		// imgを消す
		if( preg_match( '#<img.*src\s*=\s*[\"|\'](.*?)[\"|\'].*>#i', $content, $m ) ) {
			if(preg_match( '@wp-image-.* @', $m[0], $am) ){
				$exp = explode('wp-image-', $am[0]);
				$thumbnail_id = !empty($exp[1]) ? trim($exp[1], "\x22 \x27"): 0 ;
			}
			$content = preg_replace( '#<img.*src\s*=\s*[\"|\'](.*?)[\"|\'].*>#u', '', $content );
		}
		// ショートコードを消す
		$content = strip_shortcodes( $content );

		// twitterやinstagramのブロック引用を消す
		$content = preg_replace_callback( '#<blockquote([^>]*?)>(.*?)</blockquote>#us', function($matches) {
			if ( false !== strpos( $matches[1], 'twitter' ) ) {
				return '';
			} elseif ( false !== strpos( $matches[1], 'instagram-media' ) ) {
				return '';
			} else {
				return $matches[0];
			}
		}, $content );
		// OembedになりそうなURLだけの行を消す
		$content = implode( "\n", array_filter( explode( "\r\n", $this->format( strip_tags( $content, '<h2>' ) ) ), function( $row ) {
			return ! preg_match( '#^https?://[a-zA-Z0-9\.\-_/]+$#', $row );
		} ) );
		// 4行空白が続いたら圧縮
		$content = preg_replace( '/\\n{3,}/', "\n\n", $content );
        //  制御文字を消す
        $content = preg_replace('@[\x00-\x09\x0B\x0C\x0E-\x1F\x7F]@', '', $content);
		// 画像
		$enclosure = '';
		if ( 'del' !== $revision ) {
			if ( $para_count == 1 ) {
				if( has_post_thumbnail( $post ) ) {
					$thumbnail_id = get_post_thumbnail_id( $post->ID );
					$thumbnail = get_post( $thumbnail_id );
					$caption = $thumbnail->post_content;
					if ( ! $caption ) {
						$caption = $thumbnail->post_excerpt;
					}
					if ( ! $caption ) {
						$caption = 'BASKETBALL KING';
					}

					$caption = mb_substr( $this->format( $caption ), 0, 120, 'utf-8' );
//					$img_name = "{$date_id}-{$item_id}-{$this->media_id}-{$revision}-00-view.jpg";
					$img_name = sprintf( "%s-%s-%s-%s-%02d-view.jpg", $date_id, $item_id, $this->media_id, $revision, $img_count );
					//画像のローカルパスを取得
					$sizes = wp_get_attachment_image_src( $thumbnail_id, '500n-post-thumbnail' );
					$img_count++;
				}
			}
			else {
				if( $thumbnail_id && $thumbnail = get_post( $thumbnail_id ) ) {
					$caption = $thumbnail ? $thumbnail->post_content : '' ;
					if ( ! $caption ) {
						$caption = $thumbnail ? $thumbnail->post_excerpt : '' ;
					}
					if ( ! $caption ) {
						$caption = 'BASKETBALL KING';
					}
					$caption = mb_substr( $this->format( $caption ), 0, 120, 'utf-8' );
					$img_name = sprintf( "%s-%s-%s-%s-%02d-view.jpg", $date_id, $item_id, $this->media_id, $revision, $img_count );
					//画像のローカルパスを取得
					$sizes = wp_get_attachment_image_src( $thumbnail_id, '500n-post-thumbnail' );

					$img_count++;
				}
			}
			if ( $sizes ) {
				$files[ $img_name ] = str_replace( home_url( '/' ), ABSPATH, $sizes[0] );
				$enclosure = <<<XML
			<Enclosure>
				<Item Seq="1">
					<Caption><![CDATA[{$caption}]]></Caption>
					<Credit><![CDATA[BASKETBALL KING]]></Credit>
					<Image>
						<Path>{$img_name}</Path>
					</Image>
				</Item>
			</Enclosure>
XML;
			}
		}

		$section_header = $content_val[0] ? sprintf( "<SectionHeader><![CDATA[%s]]></SectionHeader>", $content_val[0] ) : '' ;
		// 本文を作成する
		$xml .= <<<XML

		<Paragraph Id="{$para_count}">
			{$section_header}
			<Body>
				<![CDATA[{$content}]]>
			</Body>
XML;
	$xml .= <<<XML

{$enclosure}
XML;

	$xml .= <<<XML

		</Paragraph>
XML;
		$para_count++;
	endforeach;
		// 本文を作成する
		$xml .= <<<XML

	</Article>
{$related_link}
</YJNewsFeed>
XML;
		$xml = str_replace( [ "\r\n", "\r" ], "\n", $xml );
		$name = $this->get_file_name( $revision, $post, $retry );
		$path = $this->get_temp_path( $name, $xml );
		$files[ $name ] = $path;
		return $this->upload( $files );
	}
}
