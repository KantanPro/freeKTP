<?php
/**
 * Plugin Reference Class for KTPWP
 *
 * Provides comprehensive plugin reference and documentation
 * accessible through a modal interface.
 *
 * @package KTPWP
 * @since 1.0.0
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( ! class_exists( 'KTPWP_Plugin_Reference' ) ) {

/**
 * Plugin Reference Class
 *
 * @since 1.0.0
 */
class KTPWP_Plugin_Reference {

    /**
     * Singleton instance
     *
     * @var KTPWP_Plugin_Reference
     */
    private static $instance = null;

    /**
     * Get singleton instance
     *
     * @return KTPWP_Plugin_Reference
     */
    public static function get_instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor
     */
    private function __construct() {
        add_action( 'wp_ajax_ktpwp_get_reference_content', array( $this, 'ajax_get_reference_content' ) );
        add_action( 'wp_ajax_nopriv_ktpwp_get_reference_content', array( $this, 'ajax_get_reference_content' ) );
    }

    /**
     * Get reference link HTML
     *
     * @return string HTML for reference link
     */
    public function get_reference_link() {
        return '<a href="#" class="ktpwp-reference-link" data-action="ktpwp_get_reference_content" data-nonce="' . wp_create_nonce( 'ktpwp_reference_nonce' ) . '">' . esc_html__( 'リファレンス', 'ktpwp' ) . '</a>';
    }

    /**
     * Ajax handler for reference content
     */
    public function ajax_get_reference_content() {
        // Verify nonce
        if ( ! wp_verify_nonce( $_POST['nonce'], 'ktpwp_reference_nonce' ) ) {
            wp_send_json_error( __( 'セキュリティチェックに失敗しました。', 'ktpwp' ) );
        }

        $section = isset( $_POST['section'] ) ? sanitize_text_field( $_POST['section'] ) : 'overview';

        switch ( $section ) {
            case 'overview':
                $content = $this->get_overview_content();
                break;
            case 'tabs':
                $content = $this->get_tabs_content();
                break;
            case 'shortcodes':
                $content = $this->get_shortcodes_content();
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
            default:
                $content = $this->get_overview_content();
        }

        wp_send_json_success( $content );
    }

    /**
     * Get overview content
     *
     * @since 1.0.0
     * @return string HTML content
     */
    private function get_overview_content() {
        return '<div class="ktpwp-reference-content">'
            . '<h3>KantanPro プラグイン概要</h3>'
            . '<p>KantanProは、中小企業向けの包括的なビジネス管理プラグインです。受注管理から顧客管理、仕入先管理、スタッフ間コミュニケーションまで、ビジネスに必要な機能を統合的に提供します。</p>'
            . '<h4>主要機能</h4>'
            . '<ul>'
            . '<li><strong>仕事リスト管理</strong>: 受注案件の進捗管理（7段階ステータス）、納期管理とアラート機能</li>'
            . '<li><strong>伝票処理</strong>: 受注書作成・編集・印刷・PDF保存、請求書管理、原価管理と利益計算</li>'
            . '<li><strong>顧客管理</strong>: 顧客情報の一元管理、取引履歴の自動記録、顧客カテゴリ分類</li>'
            . '<li><strong>サービス・商品管理</strong>: サービス・商品マスター管理、価格設定と原価管理、画像アップロード機能</li>'
            . '<li><strong>協力会社・仕入先管理</strong>: 仕入先・外注先情報管理、商品・サービス能力管理、単価管理と頻度設定</li>'
            . '<li><strong>レポート・分析</strong>: 売上分析グラフ、進捗状況レポート、データ集計機能</li>'
            . '<li><strong>スタッフチャット</strong>: 案件別スタッフ間連絡、リアルタイムメッセージ、自動スクロール機能</li>'
            . '<li><strong>設定・カスタマイズ</strong>: システム設定、デザインカスタマイズ、SMTP設定、スタッフ管理</li>'
            . '<li><strong>Contact Form 7連携</strong>: お問い合わせフォームからの自動顧客登録、受注データの自動作成</li>'
            . '<li><strong>自動更新機能</strong>: GitHubからの自動更新、バージョン管理、セキュリティ更新</li>'
            . '</ul>'
            . '<h4>システム要件</h4>'
            . '<ul>'
            . '<li>WordPress 5.0以上</li>'
            . '<li>PHP 7.4以上</li>'
            . '<li>MySQL 5.6以上 または MariaDB 10.0以上</li>'
            . '<li>推奨メモリ: 256MB以上</li>'
            . '</ul>'
            . '</div>';
    }

    /**
     * Get tabs content
     *
     * @since 1.0.0
     * @return string HTML content
     */
    private function get_tabs_content() {
        return '<div class="ktpwp-reference-content">'
            . '<h3>タブ機能詳細説明</h3>'
            . '<div class="tab-section">'
            . '<h4>📋 仕事リスト</h4>'
            . '<ul>'
            . '<li><strong>進捗管理</strong>: 7段階ステータス（受付中→見積中→受注→完了→請求済→入金済→ボツ）</li>'
            . '<li><strong>納期管理</strong>: 希望納期・予定納期の設定とアラート機能</li>'
            . '<li><strong>ソート機能</strong>: ID、案件名、顧客名、進捗、日付でソート可能</li>'
            . '<li><strong>検索機能</strong>: 案件名、顧客名での検索</li>'
            . '<li><strong>ページネーション</strong>: 大量データの効率的な閲覧</li>'
            . '<li><strong>インライン編集</strong>: 案件名の直接編集機能</li>'
            . '</ul>'
            . '</div>'
            . '<div class="tab-section">'
            . '<h4>📄 伝票処理</h4>'
            . '<ul>'
            . '<li><strong>受注書作成</strong>: 新規受注書の作成と編集</li>'
            . '<li><strong>顧客・サービス選択</strong>: 登録済み顧客とサービスから選択</li>'
            . '<li><strong>請求項目管理</strong>: 商品・サービスの追加・編集・削除</li>'
            . '<li><strong>原価項目管理</strong>: 仕入先・外注先の原価管理</li>'
            . '<li><strong>印刷機能</strong>: 受注書・請求書の印刷とPDF保存</li>'
            . '<li><strong>プレビュー機能</strong>: 印刷前の確認表示</li>'
            . '<li><strong>同時編集防止</strong>: 複数ユーザーによる同時編集を防止</li>'
            . '</ul>'
            . '</div>'
            . '<div class="tab-section">'
            . '<h4>👥 顧客管理</h4>'
            . '<ul>'
            . '<li><strong>顧客情報管理</strong>: 会社名、担当者名、連絡先等の基本情報</li>'
            . '<li><strong>取引履歴</strong>: 過去の受注履歴の自動記録と表示</li>'
            . '<li><strong>カテゴリ分類</strong>: 顧客の分類管理</li>'
            . '<li><strong>印刷テンプレート</strong>: 顧客情報の印刷機能</li>'
            . '<li><strong>検索・ソート</strong>: 会社名、カテゴリ、登録日での検索・ソート</li>'
            . '<li><strong>ページネーション</strong>: 大量顧客データの効率的な管理</li>'
            . '</ul>'
            . '</div>'
            . '<div class="tab-section">'
            . '<h4>🛠️ サービス・商品管理</h4>'
            . '<ul>'
            . '<li><strong>サービス・商品登録</strong>: 提供サービス・商品の基本情報</li>'
            . '<li><strong>価格設定</strong>: 単価と原価の設定</li>'
            . '<li><strong>画像管理</strong>: 商品画像のアップロードと管理</li>'
            . '<li><strong>カテゴリ分類</strong>: サービス・商品の分類管理</li>'
            . '<li><strong>検索・ソート</strong>: 商品名、カテゴリ、価格での検索・ソート</li>'
            . '<li><strong>複製機能</strong>: 既存商品の複製による効率的な登録</li>'
            . '</ul>'
            . '</div>'
            . '<div class="tab-section">'
            . '<h4>🏢 協力会社・仕入先管理</h4>'
            . '<ul>'
            . '<li><strong>仕入先情報管理</strong>: 会社情報、連絡先、支払い条件等</li>'
            . '<li><strong>スキル・商品管理</strong>: 各仕入先の提供商品・サービス管理</li>'
            . '<li><strong>単価管理</strong>: 商品別単価と頻度設定</li>'
            . '<li><strong>検索・ソート</strong>: 会社名、カテゴリ、商品名での検索・ソート</li>'
            . '<li><strong>ページネーション</strong>: 大量仕入先データの効率的な管理</li>'
            . '<li><strong>商品追加・編集</strong>: 仕入先別商品の動的追加・編集</li>'
            . '</ul>'
            . '</div>'
            . '<div class="tab-section">'
            . '<h4>📊 レポート・分析</h4>'
            . '<ul>'
            . '<li><strong>売上分析</strong>: 期間別売上グラフと分析</li>'
            . '<li><strong>進捗状況</strong>: 案件進捗の統計表示</li>'
            . '<li><strong>顧客別分析</strong>: 顧客別売上・取引状況</li>'
            . '<li><strong>データ集計</strong>: 各種統計データの表示</li>'
            . '<li><strong>アクティベーション機能</strong>: ライセンスキーによる機能制限</li>'
            . '</ul>'
            . '</div>'
            . '<div class="tab-section">'
            . '<h4>💬 スタッフチャット</h4>'
            . '<ul>'
            . '<li><strong>案件別チャット</strong>: 受注案件ごとの専用チャット</li>'
            . '<li><strong>リアルタイム更新</strong>: Ajaxによるリアルタイムメッセージ更新</li>'
            . '<li><strong>自動スクロール</strong>: 新着メッセージへの自動スクロール</li>'
            . '<li><strong>メッセージ履歴</strong>: 過去のメッセージ履歴表示</li>'
            . '<li><strong>ユーザー表示</strong>: 送信者のユーザー名表示</li>'
            . '<li><strong>初期メッセージ</strong>: 受注作成時の自動初期メッセージ</li>'
            . '</ul>'
            . '</div>'
            . '</div>';
    }

    /**
     * Get shortcode content
     *
     * @since 1.0.0
     * @return string HTML content
     */
    private function get_shortcodes_content() {
        return '<div class="ktpwp-reference-content">'
            . '<h3>ショートコード使用方法</h3>'
            . '<p>メインのプラグイン機能を表示するには、以下のショートコードを固定ページに挿入してください：</p>'
            . '<div class="shortcode-example">'
            . '<code>[ktpwp_all_tab]</code>'
            . '</div>'
            . '<h4>設置方法</h4>'
            . '<ol>'
            . '<li>WordPress管理画面で「固定ページ」→「新規追加」をクリック</li>'
            . '<li>ページタイトルを入力（例：「ワークフロー管理」など）</li>'
            . '<li>エディタに <code>[ktpwp_all_tab]</code> を挿入</li>'
            . '<li>「公開」または「更新」をクリック</li>'
            . '</ol>'
            . '<h4>ショートコード一覧</h4>'
            . '<table class="shortcode-table">'
            . '<thead>'
            . '<tr><th>ショートコード</th><th>説明</th><th>使用例</th></tr>'
            . '</thead>'
            . '<tbody>'
            . '<tr>'
            . '<td><code>[ktpwp_all_tab]</code></td>'
            . '<td>メインのプラグイン機能を表示（推奨）</td>'
            . '<td><code>[ktpwp_all_tab]</code></td>'
            . '</tr>'
            . '<tr>'
            . '<td><code>[kantanAllTab]</code></td>'
            . '<td>旧ショートコード（後方互換性）</td>'
            . '<td><code>[kantanAllTab]</code></td>'
            . '</tr>'
            . '</tbody>'
            . '</table>'
            . '<h4>注意事項</h4>'
            . '<ul>'
            . '<li>ログインユーザーのみがプラグイン機能にアクセス可能です</li>'
            . '<li>ページのパーマリンクは分かりやすいものに設定することを推奨します</li>'
            . '<li>ページのテンプレートは「デフォルト」または「全幅」を推奨します</li>'
            . '<li>Ajaxリクエスト中はショートコードの出力が抑制されます</li>'
            . '<li>権限チェックにより、適切な権限を持つユーザーのみが機能にアクセスできます</li>'
            . '</ul>'
            . '<h4>カスタマイズ</h4>'
            . '<p>ショートコードの動作をカスタマイズするには、以下のフィルターを使用できます：</p>'
            . '<pre><code>// ショートコードの出力をカスタマイズ
add_filter(\'ktpwp_shortcode_output\', function($output, $atts) {
    // カスタム処理
    return $output;
}, 10, 2);</code></pre>'
            . '</div>';
    }

    /**
     * Get settings content
     *
     * @since 1.0.0
     * @return string HTML content
     */
    private function get_settings_content() {
        return '<div class="ktpwp-reference-content">'
            . '<h3>設定・管理画面</h3>'
            . '<h4>管理画面メニュー</h4>'
            . '<p>WordPress管理画面の「KantanPro設定」メニューから以下の設定が可能です：</p>'
            . '<div class="settings-section">'
            . '<h5>⚙️ 一般設定</h5>'
            . '<ul>'
            . '<li><strong>ロゴ画像</strong>: システムロゴの設定</li>'
            . '<li><strong>システム名</strong>: システムの表示名</li>'
            . '<li><strong>システム説明</strong>: システムの説明文</li>'
            . '<li><strong>仕事リスト表示件数</strong>: 1ページあたりの表示件数（デフォルト: 20件）</li>'
            . '<li><strong>納期警告日数</strong>: 納期警告を表示する日数（デフォルト: 3日）</li>'
            . '<li><strong>会社情報</strong>: 印刷用の会社情報</li>'
            . '</ul>'
            . '</div>'
            . '<div class="settings-section">'
            . '<h5>📧 メール・SMTP設定</h5>'
            . '<ul>'
            . '<li><strong>SMTPホスト</strong>: メールサーバーのホスト名</li>'
            . '<li><strong>SMTPポート</strong>: メールサーバーのポート番号</li>'
            . '<li><strong>SMTP認証</strong>: ユーザー名・パスワード設定</li>'
            . '<li><strong>暗号化方式</strong>: SSL/TLS設定</li>'
            . '<li><strong>送信者名</strong>: メール送信者の表示名</li>'
            . '<li><strong>テストメール送信</strong>: 設定の動作確認</li>'
            . '</ul>'
            . '</div>'
            . '<div class="settings-section">'
            . '<h5>🎨 デザイン設定</h5>'
            . '<ul>'
            . '<li><strong>タブアクティブ色</strong>: アクティブタブの背景色</li>'
            . '<li><strong>タブ非アクティブ色</strong>: 非アクティブタブの背景色</li>'
            . '<li><strong>タブボーダー色</strong>: タブのボーダー色</li>'
            . '<li><strong>奇数行色</strong>: テーブル奇数行の背景色</li>'
            . '<li><strong>偶数行色</strong>: テーブル偶数行の背景色</li>'
            . '<li><strong>ヘッダー背景画像</strong>: システムヘッダーの背景画像</li>'
            . '<li><strong>カスタムCSS</strong>: 追加のCSSカスタマイズ</li>'
            . '</ul>'
            . '</div>'
            . '<div class="settings-section">'
            . '<h5>🔑 ライセンス設定</h5>'
            . '<ul>'
            . '<li><strong>アクティベーションキー</strong>: ライセンスキーの登録</li>'
            . '<li><strong>機能制限</strong>: ライセンスによる機能制限</li>'
            . '<li><strong>更新確認</strong>: ライセンス状態の確認</li>'
            . '</ul>'
            . '</div>'
            . '<div class="settings-section">'
            . '<h5>👥 スタッフ管理</h5>'
            . '<ul>'
            . '<li><strong>スタッフ追加</strong>: 新規スタッフの登録</li>'
            . '<li><strong>権限管理</strong>: スタッフの権限設定</li>'
            . '<li><strong>スタッフ削除</strong>: スタッフの削除</li>'
            . '<li><strong>アクティビティ追跡</strong>: ログイン履歴の管理</li>'
            . '</ul>'
            . '</div>'
            . '<h4>設定の保存</h4>'
            . '<p>各設定は「設定を保存」ボタンをクリックすることで保存されます。設定変更後は、キャッシュのクリアを推奨します。</p>'
            . '<h4>デフォルト設定への復元</h4>'
            . '<p>デザイン設定では「デフォルトに戻す」ボタンにより、すべての設定をデフォルト値にリセットできます。</p>'
            . '</div>';
    }

    /**
     * Get security content
     *
     * @since 1.0.0
     * @return string HTML content
     */
    private function get_security_content() {
        return '<div class="ktpwp-reference-content">'
            . '<h3>セキュリティ機能</h3>'
            . '<p>KantanProは、WordPressのセキュリティベストプラクティスに従い、包括的なセキュリティ対策を実装しています。</p>'
            . '<h4>実装されているセキュリティ対策</h4>'
            . '<div class="security-section">'
            . '<h5>🔐 認証・認可</h5>'
            . '<ul>'
            . '<li><strong>ログイン認証必須</strong>: プラグイン機能へのアクセスにはログインが必要</li>'
            . '<li><strong>権限ベースアクセス制御</strong>: 編集者権限（edit_posts）または専用権限（ktpwp_access）が必要</li>'
            . '<li><strong>ユーザー権限チェック</strong>: 各機能で適切な権限チェックを実施</li>'
            . '<li><strong>セッション管理</strong>: WordPress標準のセッション管理を活用</li>'
            . '</ul>'
            . '</div>'
            . '<div class="security-section">'
            . '<h5>🛡️ データ保護</h5>'
            . '<ul>'
            . '<li><strong>SQLインジェクション防止</strong>: WordPress標準の$wpdb->prepare()を使用</li>'
            . '<li><strong>XSS対策</strong>: データサニタイゼーションとエスケープ処理</li>'
            . '<li><strong>CSRF対策</strong>: WordPress標準のnonce検証</li>'
            . '<li><strong>データサニタイゼーション</strong>: 入力データの適切な検証とサニタイゼーション</li>'
            . '</ul>'
            . '</div>'
            . '<div class="security-section">'
            . '<h5>📁 ファイルセキュリティ</h5>'
            . '<ul>'
            . '<li><strong>直接アクセス防止</strong>: ABSPATH定数による直接ファイルアクセス防止</li>'
            . '<li><strong>ファイルアップロード検証</strong>: アップロードファイルの種類・サイズ制限</li>'
            . '<li><strong>画像処理セキュリティ</strong>: 画像アップロード時のセキュリティチェック</li>'
            . '<li><strong>一時ファイル管理</strong>: 一時ファイルの自動削除機能</li>'
            . '</ul>'
            . '</div>'
            . '<div class="security-section">'
            . '<h5>🌐 ネットワークセキュリティ</h5>'
            . '<ul>'
            . '<li><strong>セキュリティヘッダー設定</strong>: XSS Protection、Content Security Policy等</li>'
            . '<li><strong>REST API制限</strong>: 未認証ユーザーのREST APIアクセス制限</li>'
            . '<li><strong>HTTPS強制</strong>: 管理画面でのHTTPS使用推奨</li>'
            . '<li><strong>リダイレクト制限</strong>: 安全なリダイレクト処理</li>'
            . '</ul>'
            . '</div>'
            . '<div class="security-section">'
            . '<h5>🔍 監査・ログ</h5>'
            . '<ul>'
            . '<li><strong>エラーログ</strong>: セキュリティ関連エラーの記録</li>'
            . '<li><strong>デバッグログ</strong>: 開発時のデバッグ情報記録</li>'
            . '<li><strong>ユーザーアクティビティ追跡</strong>: ログイン履歴の記録</li>'
            . '<li><strong>データベース操作ログ</strong>: 重要なデータベース操作の記録</li>'
            . '</ul>'
            . '</div>'
            . '<h4>セキュリティ推奨事項</h4>'
            . '<ul>'
            . '<li>WordPressを最新版に保つ</li>'
            . '<li>強力なパスワードを使用する</li>'
            . '<li>定期的なデータベースバックアップを実施する</li>'
            . '<li>HTTPSを使用する</li>'
            . '<li>不要なプラグインを削除する</li>'
            . '<li>セキュリティプラグインの併用を検討する</li>'
            . '</ul>'
            . '<h4>セキュリティ関連設定</h4>'
            . '<p>管理画面の「KantanPro設定」で以下のセキュリティ関連設定が可能です：</p>'
            . '<ul>'
            . '<li>SMTP設定による安全なメール送信</li>'
            . '<li>スタッフ権限の管理</li>'
            . '<li>アクティビティログの確認</li>'
            . '</ul>'
            . '</div>';
    }

    /**
     * Get troubleshooting content
     *
     * @since 1.0.0
     * @return string HTML content
     */
    private function get_troubleshooting_content() {
        return '<div class="ktpwp-reference-content">'
            . '<h3>トラブルシューティング</h3>'
            . '<p>KantanProプラグインでよく発生する問題とその解決方法をご紹介します。</p>'
            . '<h4>よくある問題と解決方法</h4>'
            . '<div class="troubleshooting-section">'
            . '<h5>❌ プラグインが表示されない</h5>'
            . '<p><strong>症状</strong>: ショートコードを挿入しても何も表示されない</p>'
            . '<p><strong>原因</strong>: ショートコードの記述ミス、権限不足、プラグイン未有効化</p>'
            . '<p><strong>解決方法</strong>:</p>'
            . '<ol>'
            . '<li>ショートコード <code>[ktpwp_all_tab]</code> が正しく記述されているか確認</li>'
            . '<li>プラグインが有効化されているか確認</li>'
            . '<li>ログインしているか確認</li>'
            . '<li>適切な権限（編集者権限以上）があるか確認</li>'
            . '<li>ブラウザのキャッシュをクリア</li>'
            . '</ol>'
            . '</div>'
            . '<div class="troubleshooting-section">'
            . '<h5>🔒 権限エラーが表示される</h5>'
            . '<p><strong>症状</strong>: 「このコンテンツを表示する権限がありません」と表示される</p>'
            . '<p><strong>原因</strong>: ユーザー権限不足</p>'
            . '<p><strong>解決方法</strong>:</p>'
            . '<ol>'
            . '<li>WordPress管理画面でユーザー権限を確認</li>'
            . '<li>編集者権限（edit_posts）以上に設定</li>'
            . '<li>または専用権限（ktpwp_access）を付与</li>'
            . '<li>管理者に権限設定を依頼</li>'
            . '</ol>'
            . '</div>'
            . '<div class="troubleshooting-section">'
            . '<h5>💾 データベースエラーが発生する</h5>'
            . '<p><strong>症状</strong>: データベースエラーメッセージが表示される</p>'
            . '<p><strong>原因</strong>: テーブル未作成、データベース接続エラー、権限不足</p>'
            . '<p><strong>解決方法</strong>:</p>'
            . '<ol>'
            . '<li>プラグインを無効化してから再度有効化</li>'
            . '<li>データベース接続設定を確認</li>'
            . '<li>データベースユーザーの権限を確認</li>'
            . '<li>WordPressのデバッグモードを有効化して詳細エラーを確認</li>'
            . '</ol>'
            . '</div>'
            . '<div class="troubleshooting-section">'
            . '<h5>📧 Contact Form 7連携が動作しない</h5>'
            . '<p><strong>症状</strong>: お問い合わせフォーム送信時に顧客・受注データが作成されない</p>'
            . '<p><strong>原因</strong>: Contact Form 7未有効化、フィールドマッピング不備</p>'
            . '<p><strong>解決方法</strong>:</p>'
            . '<ol>'
            . '<li>Contact Form 7プラグインが有効化されているか確認</li>'
            . '<li>フォームフィールド名が正しく設定されているか確認</li>'
            . '<li>デバッグログでエラー内容を確認</li>'
            . '<li>フィールドマッピング設定を確認</li>'
            . '</ol>'
            . '</div>'
            . '<div class="troubleshooting-section">'
            . '<h5>🔄 自動更新が動作しない</h5>'
            . '<p><strong>症状</strong>: GitHubからの自動更新が実行されない</p>'
            . '<p><strong>原因</strong>: ネットワーク接続問題、権限不足、設定不備</p>'
            . '<p><strong>解決方法</strong>:</p>'
            . '<ol>'
            . '<li>サーバーのネットワーク接続を確認</li>'
            . '<li>WordPressの自動更新権限を確認</li>'
            . '<li>GitHubリポジトリの設定を確認</li>'
            . '<li>手動でプラグインを更新</li>'
            . '</ol>'
            . '</div>'
            . '<div class="troubleshooting-section">'
            . '<h5>💬 スタッフチャットが動作しない</h5>'
            . '<p><strong>症状</strong>: チャットメッセージが送信・表示されない</p>'
            . '<p><strong>原因</strong>: JavaScriptエラー、Ajax通信エラー、データベース問題</p>'
            . '<p><strong>解決方法</strong>:</p>'
            . '<ol>'
            . '<li>ブラウザの開発者ツールでJavaScriptエラーを確認</li>'
            . '<li>Ajax通信のネットワークタブでエラーを確認</li>'
            . '<li>データベーステーブルが正しく作成されているか確認</li>'
            . '<li>プラグインを再有効化</li>'
            . '</ol>'
            . '</div>'
            . '<h4>デバッグ方法</h4>'
            . '<p>問題の詳細を確認するには、WordPressのデバッグモードを有効化してください：</p>'
            . '<pre><code>// wp-config.phpに追加
define(\'WP_DEBUG\', true);
define(\'WP_DEBUG_LOG\', true);
define(\'WP_DEBUG_DISPLAY\', false);</code></pre>'
            . '<p>デバッグログは <code>wp-content/debug.log</code> に記録されます。</p>'
            . '<h4>サポート情報</h4>'
            . '<p>上記の解決方法で問題が解決しない場合は、以下の情報を添えてサポートにお問い合わせください：</p>'
            . '<ul>'
            . '<li>WordPressバージョン</li>'
            . '<li>PHPバージョン</li>'
            . '<li>KantanProプラグインバージョン</li>'
            . '<li>エラーメッセージの詳細</li>'
            . '<li>デバッグログの内容</li>'
            . '<li>使用しているテーマ・プラグイン</li>'
            . '</ul>'
            . '<p><strong>サポート連絡先</strong>: <a href="https://www.kantanpro.com/support/" target="_blank">https://www.kantanpro.com/support/</a></p>'
            . '</div>';
    }
}

} // class_exists
