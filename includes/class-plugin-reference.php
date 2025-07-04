<?php
/**
 * Plugin Reference class for KTPWP plugin
 *
 * KantanPro（KTPWP）の公式リファレンス・ヘルプを提供。
 * - モバイル対応UI、PDF出力、サービス選択ポップアップ、アバター、ヘルプボタン、セキュリティ強化など最新機能を網羅。
 * - 管理タブ・伝票処理・顧客・サービス・協力会社・レポート・チャット等の使い方を解説。
 * - 部署管理機能・利用規約管理機能の詳細説明を追加。
 * - バージョンアップ履歴・トラブルシューティングも掲載。
 * - 最新バージョン: 1.3.1(beta)
 *
 * @package KTPWP
 * @subpackage Includes
 * @since 1.2.2
 * @author Kantan Pro
 * @copyright 2024 Kantan Pro
 * @license GPL-2.0+
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( ! class_exists( 'KTPWP_Plugin_Reference' ) ) {

	/**
	 * Plugin Reference class for managing help documentation
	 *
	 * @since 1.0.0
	 */
	class KTPWP_Plugin_Reference {

		/**
		 * Single instance of the class
		 *
		 * @var KTPWP_Plugin_Reference
		 */
		private static $instance = null;

		/**
		 * Get singleton instance
		 *
		 * @since 1.0.0
		 * @return KTPWP_Plugin_Reference
		 */
		public static function get_instance() {
			if ( self::$instance === null ) {
				self::$instance = new self();
			}
			return self::$instance;
		}

		/**
		 * Constructor
		 *
		 * @since 1.0.0
		 */
		private function __construct() {
			add_action( 'wp_ajax_ktpwp_get_reference', array( $this, 'ajax_get_reference' ) );
			add_action( 'wp_ajax_nopriv_ktpwp_get_reference', array( $this, 'ajax_get_reference' ) );
			add_action( 'wp_ajax_ktpwp_clear_reference_cache', array( $this, 'ajax_clear_reference_cache' ) );
			add_action( 'wp_ajax_nopriv_ktpwp_clear_reference_cache', array( $this, 'ajax_clear_reference_cache' ) );
			add_action( 'wp_footer', array( $this, 'add_modal_html' ) );
		}

		/**
		 * Enqueue scripts and styles for reference modal
		 *
		 * Note: This method is no longer used as scripts are loaded in main ktpwp.php
		 * Kept for backward compatibility
		 *
		 * @since 1.0.0
		 * @return void
		 */
		public function enqueue_reference_scripts() {
			// Scripts are now loaded in main ktpwp.php file
			// This method is kept for backward compatibility
		}

		/**
		 * Generate reference link for header
		 *
		 * @since 1.0.0
		 * @return string HTML for reference link
		 */
		public function get_reference_link() {
			if ( ! is_user_logged_in() ) {
				return '';
			}

			$reference_icon = '<span class="material-symbols-outlined" style="font-size: 20px; vertical-align: middle;">help</span>';

			// デバッグ用：リファレンスリンクが生成されることをコンソールに記録
			$debug_script = '<script>console.log("KTPWP Reference: Link generated");</script>';

			return $debug_script . '<a href="#" id="ktpwp-reference-trigger" class="ktpwp-reference-link" '
            . 'title="' . esc_attr__( 'プラグインの使い方を確認', 'ktpwp' ) . '" '
            . 'style="color: #0073aa; text-decoration: none; margin-left: 8px; display: inline-flex; align-items: center; gap: 4px;">'
            . $reference_icon
            . '<span>' . esc_html__( 'ヘルプ', 'ktpwp' ) . '</span>'
            . '</a>';
		}

		/**
		 * Ajax handler for getting reference content
		 *
		 * @since 1.0.0
		 * @return void
		 */
		public function ajax_get_reference() {
			// Security check
			if ( ! wp_verify_nonce( $_POST['nonce'], 'ktpwp_reference_nonce' ) ) {
				wp_die( esc_html__( 'セキュリティチェックに失敗しました。', 'ktpwp' ) );
			}

			if ( ! is_user_logged_in() ) {
				wp_die( esc_html__( 'ログインが必要です。', 'ktpwp' ) );
			}

			$section = isset( $_POST['section'] ) ? sanitize_text_field( $_POST['section'] ) : 'overview';

			// Check if reference needs refresh after activation
			if ( get_option( 'ktpwp_reference_needs_refresh', false ) ) {
				delete_option( 'ktpwp_reference_needs_refresh' );
				delete_transient( 'ktpwp_reference_cache' );
			}

			$content = $this->get_reference_content( $section );

			wp_send_json_success(
                array(
					'content' => $content,
					'section' => $section,
					'last_updated' => get_option( 'ktpwp_reference_last_updated', time() ),
					'version' => KANTANPRO_PLUGIN_VERSION, // 常に最新の定数値を使用
                )
            );
		}

		/**
		 * Ajax handler for clearing reference cache
		 *
		 * @since 1.0.0
		 * @return void
		 */
		public function ajax_clear_reference_cache() {
			// Security check
			if ( ! wp_verify_nonce( $_POST['nonce'], 'ktpwp_reference_nonce' ) ) {
				wp_send_json_error( array( 'message' => esc_html__( 'セキュリティチェックに失敗しました。', 'ktpwp' ) ) );
			}

			if ( ! is_user_logged_in() ) {
				wp_send_json_error( array( 'message' => esc_html__( 'ログインが必要です。', 'ktpwp' ) ) );
			}

			// Clear all reference cache (including individual section caches)
			$sections = array( 'overview', 'tabs', 'shortcodes', 'settings', 'security', 'troubleshooting', 'departments', 'terms' );

			foreach ( $sections as $section ) {
				delete_transient( "ktpwp_reference_content_{$section}" );
			}

			// Clear main cache
			delete_transient( 'ktpwp_reference_cache' );

			// Update metadata
			update_option( 'ktpwp_reference_last_updated', current_time( 'timestamp' ) );
			update_option( 'ktpwp_reference_version', KANTANPRO_PLUGIN_VERSION );

			// Update last cleared timestamp
			update_option( 'ktpwp_reference_last_cleared', time() );

			wp_send_json_success(
                array(
					'message' => esc_html__( 'キャッシュをクリアしました。', 'ktpwp' ),
					'cleared_at' => current_time( 'mysql' ),
                )
            );
		}

		/**
		 * Get reference content by section
		 *
		 * @since 1.0.0
		 * @param string $section Reference section
		 * @return string HTML content
		 */
		private function get_reference_content( $section ) {
			// Check cache first (unless refresh is needed)
			$cache_key = "ktpwp_reference_content_{$section}";
			$cached_content = get_transient( $cache_key );

			if ( $cached_content !== false && ! get_option( 'ktpwp_reference_needs_refresh', false ) ) {
				return $cached_content;
			}

			$content = '';

			switch ( $section ) {
				case 'overview':
					$content = $this->get_overview_content();
					break;
				case 'tabs':
					$content = $this->get_tabs_content();
					break;
				case 'shortcodes':
					$content = $this->get_shortcode_content();
					break;
				case 'settings':
					$content = $this->get_settings_content();
					break;
				case 'security':
					$content = $this->get_security_content();
					break;
				case 'troubleshooting':
					$content = $this->get_troubleshooting_content();
					break;
				case 'departments':
					$content = $this->get_departments_content();
					break;
				case 'terms':
					$content = $this->get_terms_content();
					break;
				default:
					$content = $this->get_overview_content();
			}

			// Cache the content for 1 hour
			set_transient( $cache_key, $content, HOUR_IN_SECONDS );

			return $content;
		}

		/**
		 * Get overview content
		 *
		 * @since 1.0.0
		 * @return string HTML content
		 */
		private function get_overview_content() {
			return '<h2>KantanPro プラグイン概要</h2>
			<p>KantanProは、WordPressで動作する業務管理・受注進捗・請求・顧客・サービス・協力会社・レポート・スタッフチャットまで一元管理できる多機能プラグインです。</p>
			
			<h3>主要機能</h3>
			<ul>
				<li><strong>6つの管理タブ</strong>：仕事リスト・伝票処理・得意先・サービス・協力会社・レポート</li>
				<li><strong>受注案件の進捗管理</strong>：7段階（受注→進行中→完了→請求→支払い→ボツ）</li>
				<li><strong>受注書・請求書のPDF出力</strong>：個別・一括出力対応</li>
				<li><strong>顧客・サービス・協力会社のマスター管理</strong>：検索・ソート・ページネーション</li>
				<li><strong>スタッフチャット</strong>：自動スクロール・削除連動・安定化</li>
				<li><strong>部署管理機能</strong>：顧客ごとの部署・担当者管理</li>
				<li><strong>利用規約管理機能</strong>：同意ダイアログ・管理画面</li>
				<li><strong>モバイルUI・アバター表示</strong>：レスポンシブデザイン</li>
				<li><strong>ヘルプ（リファレンス）機能</strong>：使用方法の詳細解説</li>
				<li><strong>WP-CLIベースのマイグレーション管理</strong>：DB構造変更の安全な管理</li>
			</ul>
			
			<h3>セキュリティ対策</h3>
			<ul>
				<li>SQLインジェクション防止（prepare文・バインド変数）</li>
				<li>XSS・CSRF対策（サニタイズ・エスケープ・ノンス）</li>
				<li>ファイルアップロード検証（MIME型・サイズ制限）</li>
				<li>権限管理・安全なDBアクセス（ロールベースアクセス制御）</li>
				<li>gap→margin対応によるUI崩れ防止（iOS/Android実機対応）</li>
			</ul>';
		}

		/**
		 * Get tabs content
		 *
		 * @since 1.0.0
		 * @return string HTML content
		 */
		private function get_tabs_content() {
			return '<h2>管理タブの使い方</h2>
			
			<h3>1. 仕事リストタブ</h3>
			<p>受注案件の一覧表示・検索・フィルタリング機能</p>
			<ul>
				<li>進捗状況による絞り込み</li>
				<li>顧客名・案件名での検索</li>
				<li>納期・金額でのソート</li>
				<li>ページネーション機能</li>
			</ul>
			
			<h3>2. 伝票処理タブ</h3>
			<p>受注書・請求書の作成・編集・PDF出力</p>
			<ul>
				<li>サービス選択ポップアップで商品追加</li>
				<li>個別・一括PDF出力</li>
				<li>メール送信機能（ファイル添付対応）</li>
				<li>進捗状況の更新</li>
			</ul>
			
			<h3>3. 得意先タブ</h3>
			<p>顧客情報の管理・部署・担当者管理</p>
			<ul>
				<li>顧客情報の登録・編集・削除</li>
				<li>部署・担当者の追加・管理</li>
				<li>選択された部署の情報を請求書に反映</li>
				<li>顧客ごとのメールアドレス管理</li>
			</ul>
			
			<h3>4. サービスタブ</h3>
			<p>商品・サービスのマスター管理</p>
			<ul>
				<li>商品・サービスの登録・編集・削除</li>
				<li>カテゴリー・価格・単位管理</li>
				<li>検索・ソート・ページネーション</li>
			</ul>
			
			<h3>5. 協力会社タブ</h3>
			<p>協力会社・仕入先の管理</p>
			<ul>
				<li>協力会社情報の登録・編集・削除</li>
				<li>支払い条件の管理</li>
				<li>連絡先情報の管理</li>
			</ul>
			
			<h3>6. レポートタブ</h3>
			<p>売上・進捗のレポート表示</p>
			<ul>
				<li>月別売上レポート</li>
				<li>進捗状況の統計</li>
				<li>グラフ表示機能</li>
			</ul>';
		}

		/**
		 * Get shortcode content
		 *
		 * @since 1.0.0
		 * @return string HTML content
		 */
		private function get_shortcode_content() {
			return '<h2>ショートコードの使い方</h2>
			
			<h3>メインショートコード</h3>
			<p><code>[ktpwp_all_tab]</code></p>
			<p>6つの管理タブをすべて表示します。固定ページに挿入して使用してください。</p>
			
			<h3>使用例</h3>
			<pre><code>// 固定ページの本文に挿入
[ktpwp_all_tab]</code></pre>
			
			<h3>注意事項</h3>
			<ul>
				<li>ログインが必要です</li>
				<li>適切な権限が必要です</li>
				<li>モバイル対応のレスポンシブデザイン</li>
			</ul>';
		}

		/**
		 * Get settings content
		 *
		 * @since 1.0.0
		 * @return string HTML content
		 */
		private function get_settings_content() {
			return '<h2>設定画面の使い方</h2>
			
			<h3>基本設定</h3>
			<p>管理画面「KantanPro」から各種設定を行います。</p>
			
			<h3>利用規約管理</h3>
			<p>管理画面「KantanPro」→「利用規約管理」で利用規約の編集が可能です。</p>
			<ul>
				<li>開発者パスワード認証が必要</li>
				<li>Markdown形式で記述可能</li>
				<li>バージョン管理機能</li>
			</ul>
			
			<h3>WP-CLIマイグレーション</h3>
			<p>DB構造の変更はWP-CLIコマンドで安全に管理できます。</p>
			<ul>
				<li>一括実行: <code>wp ktpwp migrate_all</code></li>
				<li>個別実行: <code>wp ktpwp migrate_YYYYMMDD_...</code></li>
			</ul>';
		}

		/**
		 * Get security content
		 *
		 * @since 1.0.0
		 * @return string HTML content
		 */
		private function get_security_content() {
			return '<h2>セキュリティ機能</h2>
			
			<h3>実装されているセキュリティ対策</h3>
			<ul>
				<li><strong>SQLインジェクション防止</strong>：prepare文・バインド変数を使用</li>
				<li><strong>XSS対策</strong>：サニタイズ・エスケープ処理</li>
				<li><strong>CSRF対策</strong>：ノンス・トークンによるフォーム保護</li>
				<li><strong>ファイルアップロード検証</strong>：MIME型・サイズ制限</li>
				<li><strong>権限管理</strong>：ロールベースアクセス制御</li>
				<li><strong>REST API制限</strong>：不要なAPIアクセスの制限</li>
			</ul>
			
			<h3>推奨設定</h3>
			<ul>
				<li>WordPressの最新版を使用</li>
				<li>強力なパスワードを使用</li>
				<li>定期的なバックアップを実行</li>
				<li>セキュリティプラグインとの併用</li>
			</ul>';
		}

		/**
		 * Get troubleshooting content
		 *
		 * @since 1.0.0
		 * @return string HTML content
		 */
		private function get_troubleshooting_content() {
			return '<h2>トラブルシューティング</h2>
			
			<h3>よくある問題と解決方法</h3>
			
			<h4>1. ショートコードが表示されない</h4>
			<ul>
				<li>ログインしているか確認</li>
				<li>適切な権限があるか確認</li>
				<li>固定ページに正しく挿入されているか確認</li>
			</ul>
			
			<h4>2. PDF出力ができない</h4>
			<ul>
				<li>PHP拡張GDが有効か確認</li>
				<li>メモリ制限を確認</li>
				<li>一時ファイルの書き込み権限を確認</li>
			</ul>
			
			<h4>3. モバイルで表示が崩れる</h4>
			<ul>
				<li>ブラウザのキャッシュをクリア</li>
				<li>CSSのgap→margin対応を確認</li>
				<li>レスポンシブデザインの確認</li>
			</ul>
			
			<h4>4. データベースエラー</h4>
			<ul>
				<li>WP-CLIマイグレーションを実行</li>
				<li>データベース接続を確認</li>
				<li>テーブル構造を確認</li>
			</ul>
			
			<h3>デバッグ方法</h3>
			<ul>
				<li>WP_DEBUGを有効にしてログを確認</li>
				<li>ブラウザの開発者ツールでエラーを確認</li>
				<li>プラグインの競合を確認</li>
			</ul>';
		}

		/**
		 * Get departments content
		 *
		 * @since 1.3.1
		 * @return string HTML content
		 */
		private function get_departments_content() {
			return '<h2>部署管理機能</h2>
			
			<h3>概要</h3>
			<p>顧客ごとに複数の部署・担当者を管理できる機能です。請求書に部署情報を反映できます。</p>
			
			<h3>使い方</h3>
			
			<h4>1. 部署の追加</h4>
			<ol>
				<li>得意先タブで顧客を選択・編集</li>
				<li>部署管理セクションで部署名・担当者名・メールアドレスを入力</li>
				<li>「追加」ボタンをクリック</li>
			</ol>
			
			<h4>2. 部署の選択</h4>
			<ol>
				<li>部署一覧のチェックボックスをクリック</li>
				<li>選択された部署の情報が請求書に反映されます</li>
				<li>一度に1つの部署のみ選択可能</li>
			</ol>
			
			<h4>3. 部署の編集・削除</h4>
			<ul>
				<li>部署名・担当者名・メールアドレスは編集可能</li>
				<li>「削除」ボタンで部署を削除</li>
				<li>削除時は確認ダイアログが表示されます</li>
			</ul>
			
			<h3>請求書への反映</h3>
			<ul>
				<li>選択された部署の情報が請求書の宛先に反映</li>
				<li>部署名・担当者名が適切に表示</li>
				<li>メール送信時も選択された部署のメールアドレスが使用</li>
			</ul>
			
			<h3>注意事項</h4>
			<ul>
				<li>部署名は空欄でも登録可能</li>
				<li>担当者名・メールアドレスは必須</li>
				<li>部署の削除は取り消しできません</li>
				<li>選択された部署が削除された場合、選択状態はリセットされます</li>
			</ul>';
		}

		/**
		 * Get terms content
		 *
		 * @since 1.3.1
		 * @return string HTML content
		 */
		private function get_terms_content() {
			return '<h2>利用規約管理機能</h2>
			
			<h3>概要</h3>
			<p>プラグイン利用時の利用規約同意機能です。初回利用時に同意ダイアログが表示されます。</p>
			
			<h3>利用規約の編集</h3>
			
			<h4>1. 管理画面での編集</h4>
			<ol>
				<li>管理画面「KantanPro」→「利用規約管理」を選択</li>
				<li>開発者パスワードを入力して認証</li>
				<li>利用規約内容を編集</li>
				<li>「保存」ボタンをクリック</li>
			</ol>
			
			<h4>2. 利用規約の形式</h4>
			<ul>
				<li>Markdown形式で記述可能</li>
				<li>見出し・リスト・太字・斜体に対応</li>
				<li>バージョン管理機能あり</li>
			</ul>
			
			<h3>同意ダイアログ</h3>
			
			<h4>1. 表示タイミング</h4>
			<ul>
				<li>初回利用時</li>
				<li>利用規約更新時</li>
				<li>同意状態がリセットされた時</li>
			</ul>
			
			<h4>2. 同意手順</h4>
			<ol>
				<li>利用規約を読む</li>
				<li>「確認しました」チェックボックスをクリック</li>
				<li>「利用開始する」ボタンをクリック</li>
			</ol>
			
			<h3>同意状態の管理</h3>
			<ul>
				<li>ユーザーメタデータで同意状態を保存</li>
				<li>同意日時・利用規約バージョンを記録</li>
				<li>開発者にメール通知が送信</li>
			</ul>
			
			<h3>公開利用規約ページ</h3>
			<ul>
				<li>フッターに利用規約リンクが表示</li>
				<li>新しいウィンドウで利用規約を表示</li>
				<li>印刷・保存に対応</li>
			</ul>
			
			<h3>注意事項</h4>
			<ul>
				<li>開発者パスワードは厳重に管理</li>
				<li>利用規約の変更は慎重に行う</li>
				<li>同意状態のリセットはデバッグ用</li>
				<li>メール通知は開発者のみに送信</li>
			</ul>';
		}

		/**
		 * Render modal HTML
		 *
		 * @since 1.0.0
		 * @return void
		 */
		public static function render_modal() {
			?>
			<div id="ktpwp-reference-modal" class="ktpwp-modal" style="display: none;">
				<div class="ktpwp-modal-content">
					<div class="ktpwp-modal-header">
						<h2>KantanPro ヘルプ</h2>
						<span class="ktpwp-modal-close">&times;</span>
					</div>
					<div class="ktpwp-modal-body">
						<div class="ktpwp-reference-nav">
							<button class="ktpwp-nav-btn active" data-section="overview">概要</button>
							<button class="ktpwp-nav-btn" data-section="tabs">管理タブ</button>
							<button class="ktpwp-nav-btn" data-section="shortcodes">ショートコード</button>
							<button class="ktpwp-nav-btn" data-section="settings">設定</button>
							<button class="ktpwp-nav-btn" data-section="departments">部署管理</button>
							<button class="ktpwp-nav-btn" data-section="terms">利用規約</button>
							<button class="ktpwp-nav-btn" data-section="security">セキュリティ</button>
							<button class="ktpwp-nav-btn" data-section="troubleshooting">トラブルシューティング</button>
						</div>
						<div class="ktpwp-reference-content">
							<div class="ktpwp-loading">読み込み中...</div>
						</div>
					</div>
				</div>
			</div>
			<?php
		}

		/**
		 * Add modal HTML to footer
		 *
		 * @since 1.0.0
		 * @return void
		 */
		public function add_modal_html() {
			self::render_modal();
		}

		/**
		 * Plugin activation hook
		 *
		 * @since 1.0.0
		 * @return void
		 */
		public static function on_plugin_activation() {
			// Set flag to refresh reference cache
			update_option( 'ktpwp_reference_needs_refresh', true );
		}

		/**
		 * Clear all reference cache
		 *
		 * @since 1.0.0
		 * @return void
		 */
		public static function clear_all_cache() {
			$sections = array( 'overview', 'tabs', 'shortcodes', 'settings', 'security', 'troubleshooting', 'departments', 'terms' );

			foreach ( $sections as $section ) {
				delete_transient( "ktpwp_reference_content_{$section}" );
			}

			delete_transient( 'ktpwp_reference_cache' );
			update_option( 'ktpwp_reference_last_updated', current_time( 'timestamp' ) );
		}
	}
}
