<?php
/**
 * Plugin Reference class for KTPWP plugin
 *
 * Handles plugin reference/help documentation display
 * with real-time updates and user-friendly interface.
 *
 * @package KTPWP
 * @subpackage Includes
 * @since 1.0.0
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
        
        wp_send_json_success( array(
            'content' => $content,
            'section' => $section,
            'last_updated' => get_option( 'ktpwp_reference_last_updated', time() ),
            'version' => KANTANPRO_PLUGIN_VERSION // 常に最新の定数値を使用
        ) );
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
        $sections = array( 'overview', 'tabs', 'shortcodes', 'settings', 'security', 'troubleshooting' );
        
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
        
        wp_send_json_success( array( 
            'message' => esc_html__( 'キャッシュをクリアしました。', 'ktpwp' ),
            'cleared_at' => current_time( 'mysql' )
        ) );
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
            default:
                $content = $this->get_overview_content();
                break;
        }
        
        // Cache the content for 1 hour
        if ( ! empty( $content ) ) {
            set_transient( $cache_key, $content, HOUR_IN_SECONDS );
        }
        
        return $content;
    }

    /**
     * Get overview content
     *
     * @since 1.0.0
     * @return string HTML content
     */
    private function get_overview_content() {
        return '<div class="ktpwp-reference-content">'
            . '<h3>KantanProプラグイン リファレンス</h3>'
            . '<p>KantanPro (KTPWP) は、WordPress上で動作する包括的なビジネス管理プラグインです。受注処理、顧客管理、サービス管理、仕入先管理、レポート機能、各種設定までを一元管理できます。</p>'
            . '<h4>主な機能</h4>'
            . '<ul>'
            . '<li><b>7つの管理タブで完全なワークフロー管理</b></li>'
            . '<li>受注案件の進捗管理（6段階）</li>'
            . '<li>受注書の作成・編集・印刷・プレビュー</li>'
            . '<li>顧客・サービス・仕入先のマスター管理</li>'
            . '<li>協力会社商品管理（商品名・単価・数量・単位の詳細管理）</li>'
            . '<li>スタッフ間チャット（自動スクロール・削除連動）</li>'
            . '<li>売上・進捗・顧客別レポート</li>'
            . '<li>印刷・宛名テンプレート</li>'
            . '<li>未ログインユーザー向け案内機能（登録リンク自動表示）</li>'
            . '<li>直感的なタブUI・ページネーション・ソート・検索</li>'
            . '<li>案件名インライン編集（リアルタイム更新）</li>'
            . '<li>レスポンシブデザイン</li>'
            . '</ul>'
            . '<h4>主要機能詳細</h4>'
            . '<ul>'
            . '<li><b>受注管理機能</b><br>6段階の進捗管理（受付中→見積中→作成中→完成未請求→請求済→入金済）、プロジェクト名・顧客情報・担当者・金額・進捗の一元管理、受注書の作成・編集・プレビュー・印刷機能、請求項目とコスト項目の詳細管理、スタッフ間のチャット機能、案件名インライン編集機能</li>'
            . '<li><b>顧客管理機能</b><br>会社名・担当者・連絡先・住所情報の管理、締め日・支払月・支払日・支払方法の設定、顧客別注文履歴の表示、宛名印刷テンプレートの活用、対象・対象外ステータス管理</li>'
            . '<li><b>サービス管理機能</b><br>サービス名・価格・単位・カテゴリーの管理、頻度データによる利用状況の把握、カテゴリー別分類とソート機能、受注書への簡単なサービス追加、ページネーション機能対応</li>'
            . '<li><b>仕入先管理機能</b><br>協力会社・外注先の詳細情報管理、支払条件・税区分の設定、代表者名・連絡先・住所情報、カテゴリー分類と検索機能、商品管理機能（商品名・単価・数量・単位の詳細管理）</li>'
            . '<li><b>レポート機能</b><br>売上推移グラフ、進捗別受注件数の集計、顧客別売上分析、月次・年次レポート</li>'
            . '<li><b>印刷・テンプレート機能</b><br>受注書の印刷レイアウト、宛名印刷テンプレート（郵便番号、住所、会社名等の置換機能）、プレビュー機能で印刷前確認、カスタマイズ可能なテンプレート</li>'
            . '</ul>'
            . '<li><b>仕入先管理機能</b><br>協力会社・外注先の詳細情報管理、支払条件・税区分の設定、代表者名・連絡先・住所情報、カテゴリー分類と検索機能</li>'
            . '<li><b>レポート機能</b><br>売上推移グラフ、進捗別受注件数の集計、顧客別売上分析、月次・年次レポート</li>'
            . '<li><b>印刷・テンプレート機能</b><br>受注書の印刷レイアウト、宛名印刷テンプレート（郵便番号、住所、会社名等の置換機能）、プレビュー機能で印刷前確認、カスタマイズ可能なテンプレート</li>'
            . '</ul>'
            . '<h4>セキュリティ対策</h4>'
            . '<ul>'
            . '<li>SQLインジェクション防止</li>'
            . '<li>XSS（クロスサイトスクリプティング）保護</li>'
            . '<li>CSRF（クロスサイトリクエストフォージェリ）対策</li>'
            . '<li>ファイルアップロードの検証</li>'
            . '<li>ユーザー権限の適切な制御</li>'
            . '<li>データベースアクセスの安全な処理</li>'
            . '</ul>'
            . '<h4>操作性・利便性</h4>'
            . '<ul>'
            . '<li>直感的なタブ型インターフェース</li>'
            . '<li>ページネーション機能で大量データも快適</li>'
            . '<li>ソート機能（ID、名前、日付、カテゴリー、頻度等）</li>'
            . '<li>検索・フィルタリング機能</li>'
            . '<li>リアルタイムプレビュー</li>'
            . '<li>レスポンシブデザイン対応</li>'
            . '</ul>'
            . '<h4>直近の実装・改善点</h4>'
            . '<ul>'
            . '<li>協力会社機能の大幅拡張により、協力会社ごとの商品・サービス管理が詳細に行えるようになりました</li>'
            . '<li>ページネーション機能で大量データの効率的な閲覧が可能になりました</li>'
            . '<li>協力会社リストと職能リストの各行表示最適化</li>'
            . '<li>職能テーブルの最適化とページネーションの実装</li>'
            . '<li>職能リストのソートシステムを導入</li>'
            . '<li>協力会社タブのページネーションスタイルと構造を最適化</li>'
            . '<li>協力会社の職能・サービス一覧表示の改善</li>'
            . '<li>職能管理機能を商品管理機能に拡張（商品名・単価・数量・単位の詳細管理）</li>'
            . '<li>サービス選択ポップアップにページネーション機能を実装</li>'
            . '<li>カード型リストビューでの統一されたUI設計</li>'
            . '<li>案件名インライン編集機能（Ajax対応）</li>'
            . '<li>未ログインユーザー向け表示の改善</li>'
            . '<li>ユーザー登録リンクの追加とホームページリンクの最適化</li>'
            . '<li>スタッフチャット自動スクロール・キーボードショートカット</li>'
            . '<li>オーダー削除時のチャットデータ自動削除</li>'
            . '<li>オーダー削除後の自動作成防止</li>'
            . '<li>セキュリティ強化（CSRF, SQLi, XSS, 権限制御）</li>'
            . '</ul>'
            . '<h4>インストール</h4>'
            . '<ol>'
            . '<li>プラグインファイルを <code>/wp-content/plugins/</code> ディレクトリにアップロード、または管理画面「プラグイン」→「新規追加」からインストール</li>'
            . '<li>WordPressの「プラグイン」画面でKTPWPを有効化</li>'
            . '<li>新しい固定ページを作成し、<code>[ktpwp_all_tab]</code> または <code>[kantanAllTab]</code> を挿入</li>'
            . '<li>管理画面の「KantanPro」メニューから基本設定（一般設定・メール・デザイン・ライセンス・スタッフ管理）を行ってください</li>'
            . '</ol>'
            . '<h4>使い方</h4>'
            . '<ol>'
            . '<li>固定ページに <code>[ktpwp_all_tab]</code> を挿入すると、7つのタブが表示されます</li>'
            . '<li>「得意先」タブから顧客情報を登録</li>'
            . '<li>「サービス」タブからサービス・商品を登録</li>'
            . '<li>「協力会社」タブから各協力会社の提供商品・サービスを管理</li>'
            . '<li>仕事リストで案件名をクリックするとインライン編集できます</li>'
            . '<li>「伝票処理」タブから新規受注書を作成し、顧客とサービスを選択</li>'
            . '<li>「仕事リスト」タブで案件の進捗を管理</li>'
            . '</ol>'
            . '<h4>進捗ステータス</h4>'
            . '<ol>'
            . '<li>受付中 - 新規受注、内容確認中</li>'
            . '<li>見積中 - 見積作成・提案中</li>'
            . '<li>作成中 - 作業実行中</li>'
            . '<li>完成未請求 - 作業完了、請求書未発行</li>'
            . '<li>請求済 - 請求書発行済み</li>'
            . '<li>入金済 - 支払い完了</li>'
            . '</ol>'
            . '<h4>印刷機能</h4>'
            . '<ul>'
            . '<li>受注書印刷：「伝票処理」タブでプレビュー・印刷が可能</li>'
            . '<li>宛名印刷：「設定」タブでテンプレートを設定し、顧客情報を自動置換</li>'
            . '</ul>'
            . '<h4>データの管理</h4>'
            . '<ul>'
            . '<li>ソート機能：各リストはID、名前、日付、カテゴリー等でソート可能</li>'
            . '<li>検索機能：各タブで条件検索が可能</li>'
            . '<li>ページネーション：大量データも快適に閲覧</li>'
            . '<li>削除管理：対象外設定で論理削除による安全な管理</li>'
            . '</ul>'
            . '<h4>よくある質問</h4>'
            . '<ul>'
            . '<li><b>Q: スタッフチャットは自動で最新にスクロールされますか？</b><br>A: はい。メッセージ送信時に自動スクロールします。</li>'
            . '<li><b>Q: オーダー削除時に関連チャットも消えますか？</b><br>A: はい。関連データは自動削除されます。</li>'
            . '<li><b>Q: 協力会社の商品管理機能はどのようなものですか？</b><br>A: 各協力会社ごとに商品名、単価、数量、単位を詳細に管理できます。</li>'
            . '<li><b>Q: 案件名の編集はどのように行いますか？</b><br>A: 案件名をクリックするとインライン編集が可能で、リアルタイムで更新されます。</li>'
            . '<li><b>Q: ページネーション機能はどこで使えますか？</b><br>A: サービス選択ポップアップなどで大量データを効率的に閲覧できます。</li>'
            . '<li><b>Q: セキュリティ対策は？</b><br>A: SQLi, XSS, CSRF, 権限管理などを実装済みです。</li>'
            . '<li><b>Q: モバイル対応？</b><br>A: レスポンシブデザインでスマホ・タブレットも対応済みです。</li>'
            . '<li><b>Q: 未ログインの場合はどうなりますか？</b><br>A: ログインを促すメッセージと共に、ログイン・登録・ホームページへのリンクが表示されます。ユーザー登録が可能な環境では登録リンクも自動で表示されます。</li>'
            . '<li><b>Q: このプラグインは安全ですか？</b><br>A: はい。SQLインジェクション防止、XSS保護、CSRF対策、ファイルアップロード検証など、包括的なセキュリティ対策が実装されています。</li>'
            . '<li><b>Q: 協力会社の商品管理はどのように使いますか？</b><br>A: 協力会社詳細画面で「新しい商品・サービスを追加」フォームから商品名、単価、数量、単位を入力して管理できます。各商品は個別に削除可能で、協力会社削除時には関連商品も自動削除されます。</li>'
            . '<li><b>Q: インライン編集機能はどこで使えますか？</b><br>A: 仕事リストや受注書管理画面で案件名をクリックすると、その場で編集可能になります。変更は即座にサーバーに保存され、リアルタイムで反映されます。</li>'
            . '<li><b>Q: ページネーション機能はどこで動作しますか？</b><br>A: サービス選択ポップアップ、各データ一覧画面で利用できます。一般設定で表示件数を設定すると、その値が自動的に適用されます。</li>'
            . '<li><b>Q: 既存のWordPressテーマに影響しますか？</b><br>A: いいえ。プラグイン専用のクラス名とCSSを使用しており、テーマとの競合を避ける設計になっています。</li>'
            . '<li><b>Q: データのバックアップは必要ですか？</b><br>A: はい。重要なビジネスデータを扱うため、定期的なWordPressデータベースのバックアップを推奨します。</li>'
            . '<li><b>Q: モバイルデバイスで使用できますか？</b><br>A: はい。レスポンシブデザインに対応しており、スマートフォンやタブレットでも使用できます。</li>'
            . '<li><b>Q: 複数のユーザーで使用できますか？</b><br>A: はい。WordPressのユーザー権限機能を活用し、適切な権限を持つユーザーのみがアクセスできます。</li>'
            . '<li><b>Q: データのエクスポートは可能ですか？</b><br>A: レポート機能で集計データの出力が可能です。詳細なエクスポート機能は今後のアップデートで提供予定です。</li>'
            . '<li><b>Q: 受注書のレイアウトはカスタマイズできますか？</b><br>A: はい。設定タブでテンプレートの編集が可能です。HTML/CSSの知識があればより詳細なカスタマイズができます。</li>'
            . '<li><b>Q: 税込み・税抜きの計算はどうなりますか？</b><br>A: 仕入先には税区分の設定があり、適切な税計算が行われます。詳細は設定画面で確認してください。</li>'
            . '</ul>'
            . '<h4>サポート・システム要件</h4>'
            . '<ul>'
            . '<li>公式サイト: <a href="https://www.kantanpro.com/" target="_blank">https://www.kantanpro.com/</a></li>'
            . '<li>WordPress 5.0 以上 / PHP 7.4 以上 / MySQL 5.6 以上 または MariaDB 10.0 以上 / 推奨メモリ: 256MB 以上</li>'
            . '</ul>'
            . '<h4>変更履歴</h4>'
            . '<ul>'
            . '<li>1.1.1 (beta): 協力会社リストと職能リストの各行表示最適化、職能テーブルの最適化、職能リストのページネーションの実装、職能リストのソートシステムを導入、協力会社タブのページネーションスタイルと構造を最適化、協力会社の職能・サービス一覧表示、職能リストのスタイル修正</li>'
            . '<li>1.1.0 (beta): 職能管理機能を商品管理機能に拡張（商品名・単価・数量・単位の詳細管理）、サービス選択ポップアップにページネーション機能を実装、カード型リストビューでUI統一とレスポンシブデザイン強化、案件名インライン編集機能（Ajax対応、リアルタイム更新）、データマイグレーション機能付きで既存データの安全な移行、一般設定との連携強化、デバッグログ機能とエラーハンドリングの改善</li>'
            . '<li>1.0.9 (beta): 未ログインユーザー向け表示の改善、プラグイン名表記をKTPWPからKantanProに統一、ユーザー登録リンクの追加（動的URL生成対応）、ホームページリンクをルートディレクトリに修正、UI/UX向上とユーザビリティ改善、プラグインの更新通知・自動更新オプションを追加</li>'
            . '<li>1.0.8 (beta): スタッフチャット自動スクロール・削除連動、オーダー削除後の自動作成防止、セキュリティ強化</li>'
            . '<li>1.0.7 (beta): ベータ版リリース。UI/UXを改善しました。</li>'
            . '<li>1.0.0: ベータ版リリース。プラグインリファレンス機能が追加されました。セキュリティとパフォーマンスが大幅に向上。全機能が本番環境で安定稼働。新機能のヘルプ・リファレンスモーダルを活用してください。</li>'
            . '<li>beta: 最初の本格リリース版。全機能が利用可能。本番環境での使用に最適化。</li>'
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
    . '<h3>タブ機能説明</h3>'
    . '<p>KTPWPは7つのタブで構成されており、ビジネスプロセス全体をカバーします。</p>'
    . '<div class="ktpwp-tabs-explanation">'
    . '<div class="ktpwp-tab-item"><h4>1. 仕事リスト</h4><ul>'
    . '<li>受注案件の進捗管理とステータス追跡</li>'
    . '<li>6段階の進捗（受付中→見積中→作成中→完成未請求→請求済→入金済）</li>'
    . '<li>プロジェクト名・顧客情報・担当者・金額・進捗の一元管理</li>'
    . '<li>案件名インライン編集機能（クリックで直接編集、リアルタイム更新）</li>'
    . '</ul></div>'
    . '<div class="ktpwp-tab-item"><h4>2. 伝票処理</h4><ul>'
    . '<li>受注書作成・編集・印刷・請求項目管理</li>'
    . '<li>受注書のプレビュー・印刷機能</li>'
    . '<li>請求項目とコスト項目の詳細管理</li>'
    . '</ul></div>'
    . '<div class="ktpwp-tab-item"><h4>3. 得意先</h4><ul>'
    . '<li>顧客情報管理・注文履歴表示・印刷テンプレート</li>'
    . '<li>会社名・担当者・連絡先・住所情報の管理</li>'
    . '<li>締め日・支払月・支払日・支払方法の設定</li>'
    . '</ul></div>'
    . '<div class="ktpwp-tab-item"><h4>4. サービス</h4><ul>'
    . '<li>サービス・商品マスター管理・価格設定</li>'
    . '<li>サービス名・価格・単位・カテゴリーの管理</li>'
    . '<li>頻度データによる利用状況の把握</li>'
    . '<li>ページネーション機能対応</li>'
    . '</ul></div>'
    . '<div class="ktpwp-tab-item"><h4>5. 協力会社</h4><ul>'
    . '<li>仕入先・外注先情報管理・支払条件設定</li>'
    . '<li>協力会社・外注先の詳細情報管理</li>'
    . '<li>支払条件・税区分の設定</li>'
    . '<li>商品管理機能（商品名・単価・数量・単位の詳細管理）</li>'
    . '</ul></div>'
    . '<div class="ktpwp-tab-item"><h4>6. レポート</h4><ul>'
    . '<li>売上分析・進捗状況・データ集計</li>'
    . '<li>売上推移グラフ・進捗別受注件数の集計</li>'
    . '<li>顧客別売上分析・月次・年次レポート</li>'
    . '</ul></div>'
    . '<div class="ktpwp-tab-item"><h4>7. 設定</h4><ul>'
    . '<li>宛名印刷テンプレート・システム設定</li>'
    . '<li>印刷テンプレートのカスタマイズ</li>'
    . '<li>会社情報・税率・メール設定</li>'
    . '</ul></div>'
    . '</div>'
    . '</div>';
    }

    /**
     * Get shortcode content
     *
     * @since 1.0.0
     * @return string HTML content
     */
    private function get_shortcode_content() {
        return '<div class="ktpwp-reference-content">'
            . '<h3>' . esc_html__( 'ショートコード使用方法', 'ktpwp' ) . '</h3>'
            . '<div class="ktpwp-shortcode-explanation">'
            . '<h4>' . esc_html__( '基本ショートコード', 'ktpwp' ) . '</h4>'
            . '<p>' . esc_html__( 'メインのプラグイン機能を表示するには、以下のショートコードを使用してください：', 'ktpwp' ) . '</p>'
            . '<code style="background: #f5f5f5; padding: 12px; border-radius: 4px; display: block; margin: 12px 0; font-size: 16px;">[ktpwp_all_tab]</code>'
            . '<h4>' . esc_html__( '設置方法', 'ktpwp' ) . '</h4>'
            . '<ol>'
            . '<li>' . esc_html__( 'WordPress管理画面で「固定ページ」→「新規追加」をクリック', 'ktpwp' ) . '</li>'
            . '<li>' . esc_html__( 'ページタイトルを入力（例：「ワークフロー管理」）', 'ktpwp' ) . '</li>'
            . '<li>' . esc_html__( 'エディタに [ktpwp_all_tab] を挿入', 'ktpwp' ) . '</li>'
            . '<li>' . esc_html__( '「公開」または「更新」をクリック', 'ktpwp' ) . '</li>'
            . '</ol>'
            . '<h4>' . esc_html__( '注意事項', 'ktpwp' ) . '</h4>'
            . '<ul>'
            . '<li>' . esc_html__( 'ログインユーザーのみがプラグイン機能にアクセス可能です', 'ktpwp' ) . '</li>'
            . '<li>' . esc_html__( 'ページのパーマリンクは覚えやすいものに設定することをお勧めします', 'ktpwp' ) . '</li>'
            . '<li>' . esc_html__( 'ページのテンプレートは「デフォルト」または「全幅」を推奨します', 'ktpwp' ) . '</li>'
            . '</ul>'
            . '</div>'
            . '</div>';
    }

    /**
     * Get settings content
     *
     * @since 1.0.0
     * @return string HTML content
     */
    private function get_settings_content() {
        $settings_url = admin_url( 'admin.php?page=ktp-settings' );
        
        return '<div class="ktpwp-reference-content">'
            . '<h3>' . esc_html__( '設定ガイド', 'ktpwp' ) . '</h3>'
            . '<div class="ktpwp-settings-guide">'
            . '<p>' . esc_html__( 'プラグインの設定は管理画面から行えます。', 'ktpwp' ) . '</p>'
            . '<p><a href="' . esc_url( $settings_url ) . '" target="_blank" style="color: #0073aa;">' . esc_html__( '→ 設定ページを開く', 'ktpwp' ) . '</a></p>'
            . '<h4>' . esc_html__( '一般設定', 'ktpwp' ) . '</h4>'
            . '<ul>'
            . '<li>' . esc_html__( '会社情報の登録', 'ktpwp' ) . '</li>'
            . '<li>' . esc_html__( '表示件数の設定（仕事リスト・ページネーション対応）', 'ktpwp' ) . '</li>'
            . '</ul>'
            . '<h4>' . esc_html__( 'メール・SMTP設定', 'ktpwp' ) . '</h4>'
            . '<ul>'
            . '<li>' . esc_html__( 'メール送信者アドレス設定', 'ktpwp' ) . '</li>'
            . '<li>' . esc_html__( 'SMTP サーバー設定', 'ktpwp' ) . '</li>'
            . '<li>' . esc_html__( 'テストメール送信機能', 'ktpwp' ) . '</li>'
            . '</ul>'
            . '<h4>' . esc_html__( 'デザイン', 'ktpwp' ) . '</h4>'
            . '<ul>'
            . '<li>' . esc_html__( 'プラグインの外観とデザインに関する設定', 'ktpwp' ) . '</li>'
            . '<li>' . esc_html__( 'レスポンシブデザインの最適化', 'ktpwp' ) . '</li>'
            . '</ul>'
            . '<h4>' . esc_html__( 'ライセンス設定', 'ktpwp' ) . '</h4>'
            . '<ul>'
            . '<li>' . esc_html__( 'アクティベーションキーの入力', 'ktpwp' ) . '</li>'
            . '<li>' . esc_html__( '有償機能の有効化', 'ktpwp' ) . '</li>'
            . '</ul>'
            . '<h4>' . esc_html__( 'スタッフ管理', 'ktpwp' ) . '</h4>'
            . '<ul>'
            . '<li>' . esc_html__( '管理者による権限に関わらないスタッフの追加・削除', 'ktpwp' ) . '</li>'
            . '<li>' . esc_html__( 'チーム管理とアクセス制御', 'ktpwp' ) . '</li>'
            . '</ul>'
            . '</div>'
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
            . '<h3>' . esc_html__( 'セキュリティ機能', 'ktpwp' ) . '</h3>'
            . '<div class="ktpwp-security-features">'
            . '<p>' . esc_html__( 'KTPWPプラグインは以下のセキュリティ対策を実装しています：', 'ktpwp' ) . '</p>'
            . '<h4>' . esc_html__( '実装済みセキュリティ機能', 'ktpwp' ) . '</h4>'
            . '<ul>'
            . '<li>' . esc_html__( 'SQLインジェクション防止（準備文・バインド変数使用）', 'ktpwp' ) . '</li>'
            . '<li>' . esc_html__( 'XSS攻撃防止（データサニタイズ・エスケープ処理）', 'ktpwp' ) . '</li>'
            . '<li>' . esc_html__( 'CSRF攻撃防止（WordPressノンス検証）', 'ktpwp' ) . '</li>'
            . '<li>' . esc_html__( 'ファイルアップロード検証', 'ktpwp' ) . '</li>'
            . '<li>' . esc_html__( 'ログインユーザー限定アクセス', 'ktpwp' ) . '</li>'
            . '<li>' . esc_html__( 'REST API アクセス制限', 'ktpwp' ) . '</li>'
            . '<li>' . esc_html__( 'HTTPセキュリティヘッダー設定', 'ktpwp' ) . '</li>'
            . '</ul>'
            . '<h4>' . esc_html__( 'セキュリティのベストプラクティス', 'ktpwp' ) . '</h4>'
            . '<ul>'
            . '<li>' . esc_html__( 'WordPress本体を最新版に保つ', 'ktpwp' ) . '</li>'
            . '<li>' . esc_html__( '強固なパスワードを使用する', 'ktpwp' ) . '</li>'
            . '<li>' . esc_html__( '不要なユーザーアカウントを削除する', 'ktpwp' ) . '</li>'
            . '<li>' . esc_html__( '定期的にバックアップを取る', 'ktpwp' ) . '</li>'
            . '<li>' . esc_html__( 'SSL証明書を導入する', 'ktpwp' ) . '</li>'
            . '</ul>'
            . '</div>'
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
            . '<h3>' . esc_html__( 'トラブルシューティング', 'ktpwp' ) . '</h3>'
            . '<div class="ktpwp-troubleshooting">'
            . '<h4>' . esc_html__( 'よくある問題と解決方法', 'ktpwp' ) . '</h4>'
            . '<div class="ktpwp-faq-item">'
            . '<h5>' . esc_html__( 'Q: ショートコードを挿入してもプラグインが表示されない', 'ktpwp' ) . '</h5>'
            . '<p><strong>' . esc_html__( 'A:', 'ktpwp' ) . '</strong></p>'
            . '<ul>'
            . '<li>' . esc_html__( 'ログインしているかを確認してください', 'ktpwp' ) . '</li>'
            . '<li>' . esc_html__( 'プラグインが有効化されているかを確認してください', 'ktpwp' ) . '</li>'
            . '<li>' . esc_html__( 'ショートコードが正しく記述されているかを確認してください：[ktpwp_all_tab]', 'ktpwp' ) . '</li>'
            . '</ul>'
            . '</div>'
            . '<div class="ktpwp-faq-item">'
            . '<h5>' . esc_html__( 'Q: データが保存されない', 'ktpwp' ) . '</h5>'
            . '<p><strong>' . esc_html__( 'A:', 'ktpwp' ) . '</strong></p>'
            . '<ul>'
            . '<li>' . esc_html__( 'データベースの権限を確認してください', 'ktpwp' ) . '</li>'
            . '<li>' . esc_html__( 'PHPのメモリ制限を確認してください', 'ktpwp' ) . '</li>'
            . '<li>' . esc_html__( 'プラグインを一度無効化して再有効化してください', 'ktpwp' ) . '</li>'
            . '</ul>'
            . '</div>'
            . '<div class="ktpwp-faq-item">'
            . '<h5>' . esc_html__( 'Q: 商品管理機能が表示されない', 'ktpwp' ) . '</h5>'
            . '<p><strong>' . esc_html__( 'A:', 'ktpwp' ) . '</strong></p>'
            . '<ul>'
            . '<li>' . esc_html__( 'データベースのマイグレーションが完了しているかを確認してください', 'ktpwp' ) . '</li>'
            . '<li>' . esc_html__( 'プラグインを再有効化してみてください', 'ktpwp' ) . '</li>'
            . '<li>' . esc_html__( 'ブラウザキャッシュをクリアしてください', 'ktpwp' ) . '</li>'
            . '</ul>'
            . '</div>'
            . '<div class="ktpwp-faq-item">'
            . '<h5>' . esc_html__( 'Q: インライン編集が動作しない', 'ktpwp' ) . '</h5>'
            . '<p><strong>' . esc_html__( 'A:', 'ktpwp' ) . '</strong></p>'
            . '<ul>'
            . '<li>' . esc_html__( 'JavaScriptが有効になっているかを確認してください', 'ktpwp' ) . '</li>'
            . '<li>' . esc_html__( 'ログイン権限があることを確認してください', 'ktpwp' ) . '</li>'
            . '<li>' . esc_html__( 'ブラウザのコンソールでエラーがないかを確認してください', 'ktpwp' ) . '</li>'
            . '</ul>'
            . '</div>'
            . '<div class="ktpwp-faq-item">'
            . '<h5>' . esc_html__( 'Q: ページネーションが正しく動作しない', 'ktpwp' ) . '</h5>'
            . '<p><strong>' . esc_html__( 'A:', 'ktpwp' ) . '</strong></p>'
            . '<ul>'
            . '<li>' . esc_html__( '一般設定で表示件数が正しく設定されているかを確認してください', 'ktpwp' ) . '</li>'
            . '<li>' . esc_html__( 'Ajax通信がブロックされていないかを確認してください', 'ktpwp' ) . '</li>'
            . '<li>' . esc_html__( 'ネットワーク接続が安定しているかを確認してください', 'ktpwp' ) . '</li>'
            . '</ul>'
            . '</div>'
            . '<div class="ktpwp-faq-item">'
            . '<h5>' . esc_html__( 'Q: PDF出力ができない', 'ktpwp' ) . '</h5>'
            . '<p><strong>' . esc_html__( 'A:', 'ktpwp' ) . '</strong></p>'
            . '<ul>'
            . '<li>' . esc_html__( 'サーバーのPHP拡張機能を確認してください（mbstring, gd等）', 'ktpwp' ) . '</li>'
            . '<li>' . esc_html__( 'ブラウザのポップアップブロックを無効にしてください', 'ktpwp' ) . '</li>'
            . '<li>' . esc_html__( '会社情報の設定が完了しているかを確認してください', 'ktpwp' ) . '</li>'
            . '</ul>'
            . '</div>'
            . '<div class="ktpwp-faq-item">'
            . '<h5>' . esc_html__( 'Q: タブが正しく表示されない', 'ktpwp' ) . '</h5>'
            . '<p><strong>' . esc_html__( 'A:', 'ktpwp' ) . '</strong></p>'
            . '<ul>'
            . '<li>' . esc_html__( 'ブラウザのキャッシュをクリアしてください', 'ktpwp' ) . '</li>'
            . '<li>' . esc_html__( 'テーマとの競合がないかを確認してください', 'ktpwp' ) . '</li>'
            . '<li>' . esc_html__( '他のプラグインとの競合がないかを確認してください', 'ktpwp' ) . '</li>'
            . '</ul>'
            . '</div>'
            . '<div class="ktpwp-faq-item">'
            . '<h5>' . esc_html__( 'Q: メール送信ができない', 'ktpwp' ) . '</h5>'
            . '<p><strong>' . esc_html__( 'A:', 'ktpwp' ) . '</strong></p>'
            . '<ul>'
            . '<li>' . esc_html__( 'SMTP設定を確認してください', 'ktpwp' ) . '</li>'
            . '<li>' . esc_html__( 'テストメール送信機能を使用してください', 'ktpwp' ) . '</li>'
            . '<li>' . esc_html__( 'レンタルサーバーのメール送信制限を確認してください', 'ktpwp' ) . '</li>'
            . '</ul>'
            . '</div>'
            . '<h4>' . esc_html__( 'システム要件の確認', 'ktpwp' ) . '</h4>'
            . '<ul>'
            . '<li>' . esc_html__( 'WordPress 5.0以上', 'ktpwp' ) . '</li>'
            . '<li>' . esc_html__( 'PHP 7.4以上（8.0以上推奨）', 'ktpwp' ) . '</li>'
            . '<li>' . esc_html__( 'MySQL 5.6以上', 'ktpwp' ) . '</li>'
            . '<li>' . esc_html__( 'メモリ制限: 128MB以上', 'ktpwp' ) . '</li>'
            . '</ul>'
            . '<h4>' . esc_html__( 'デバッグモード', 'ktpwp' ) . '</h4>'
            . '<p>' . esc_html__( '問題が解決しない場合は、wp-config.phpでデバッグモードを有効にしてエラーログを確認してください：', 'ktpwp' ) . '</p>'
            . '<code style="background: #f5f5f5; padding: 8px; border-radius: 4px; display: block; margin: 8px 0;">define(\'WP_DEBUG\', true);<br>define(\'WP_DEBUG_LOG\', true);</code>'
            . '</div>'
            . '</div>';
    }

    /**
     * Render reference modal HTML
     *
     * @since 1.0.0
     * @return string Modal HTML
     */
    public static function render_modal() {
        if ( ! is_user_logged_in() ) {
            return '';
        }

        return '<div id="ktpwp-reference-modal" class="ktpwp-modal" style="display: none;">'
            . '<div class="ktpwp-modal-overlay">'
            . '<div class="ktpwp-modal-content">'
            . '<div class="ktpwp-modal-header">'
            . '<h3>' . esc_html__( 'KantanProプラグインリファレンス', 'ktpwp' ) . '</h3>'
            . '<div class="ktpwp-modal-header-actions">'
            . '<button class="ktpwp-clear-cache-btn" type="button" title="' . esc_attr__( 'キャッシュをクリア', 'ktpwp' ) . '">'
            . esc_html__( 'キャッシュクリア', 'ktpwp' ) . '</button>'
            . '<button class="ktpwp-modal-close" type="button">&times;</button>'
            . '</div>'
            . '</div>'
            . '<div class="ktpwp-modal-body">'
            . '<div class="ktpwp-reference-sidebar">'
            . '<ul class="ktpwp-reference-nav">'
            . '<li><a href="#" data-section="overview" class="active">' . esc_html__( '概要', 'ktpwp' ) . '</a></li>'
            . '<li><a href="#" data-section="tabs">' . esc_html__( 'タブ機能', 'ktpwp' ) . '</a></li>'
            . '<li><a href="#" data-section="shortcodes">' . esc_html__( 'ショートコード', 'ktpwp' ) . '</a></li>'
            . '<li><a href="#" data-section="settings">' . esc_html__( '設定', 'ktpwp' ) . '</a></li>'
            . '<li><a href="#" data-section="security">' . esc_html__( 'セキュリティ', 'ktpwp' ) . '</a></li>'
            . '<li><a href="#" data-section="troubleshooting">' . esc_html__( 'トラブルシューティング', 'ktpwp' ) . '</a></li>'
            . '</ul>'
            . '</div>'
            . '<div class="ktpwp-reference-content">'
            . '<div id="ktpwp-reference-loading" style="display: none;">' . esc_html__( '読み込み中...', 'ktpwp' ) . '</div>'
            . '<div id="ktpwp-reference-text"></div>'
            . '</div>'
            . '</div>'
            . '</div>'
            . '</div>'
            . '</div>';
    }

    /**
     * Add modal HTML to footer
     *
     * @since 1.0.0
     * @return void
     */
    public function add_modal_html() {
        if ( ! is_user_logged_in() ) {
            return;
        }

        echo self::render_modal();
    }

    /**
     * Plugin activation hook for reference updates
     *
     * This method is called during plugin activation to ensure
     * reference documentation is properly initialized and updated.
     *
     * @since 1.0.0
     * @return void
     */
    public static function on_plugin_activation() {
        // Clear ALL cached reference data (individual section caches)
        $sections = array( 'overview', 'tabs', 'shortcodes', 'settings', 'security', 'troubleshooting' );
        foreach ( $sections as $section ) {
            delete_transient( "ktpwp_reference_content_{$section}" );
        }
        
        // Clear main cache
        delete_transient( 'ktpwp_reference_cache' );
        
        // Clear any existing options that might store cached data
        delete_option( 'ktpwp_reference_last_cleared' );
        
        // Update plugin reference metadata with current version
        update_option( 'ktpwp_reference_last_updated', current_time( 'timestamp' ) );
        update_option( 'ktpwp_reference_version', KANTANPRO_PLUGIN_VERSION );
        
        // Log activation event for debugging
        if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
            error_log( 'KTPWP: プラグインリファレンスが有効化時に更新されました。バージョン: ' . KANTANPRO_PLUGIN_VERSION );
        }
        
        // Force regeneration of reference content on next load
        update_option( 'ktpwp_reference_needs_refresh', true );
    }

    /**
     * Manual cache clear function for troubleshooting
     * 
     * @since 1.0.9
     * @return bool Success status
     */
    public static function clear_all_cache() {
        // Clear all individual section caches
        $sections = array( 'overview', 'tabs', 'shortcodes', 'settings', 'security', 'troubleshooting' );
        $cleared_count = 0;
        
        foreach ( $sections as $section ) {
            if ( delete_transient( "ktpwp_reference_content_{$section}" ) ) {
                $cleared_count++;
            }
        }
        
        // Clear main cache
        delete_transient( 'ktpwp_reference_cache' );
        
        // Update metadata to current version
        update_option( 'ktpwp_reference_last_updated', current_time( 'timestamp' ) );
        update_option( 'ktpwp_reference_version', KANTANPRO_PLUGIN_VERSION );
        update_option( 'ktpwp_reference_last_cleared', time() );
        update_option( 'ktpwp_reference_needs_refresh', true );
        
        // Log the action
        if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
            error_log( "KTPWP: Manual cache clear completed. Cleared {$cleared_count} section caches. Version: " . KANTANPRO_PLUGIN_VERSION );
        }
        
        return true;
    }
}

// Initialize the plugin reference only after init (not on plugin load)
add_action('init', function() {
    KTPWP_Plugin_Reference::get_instance();
});

// Add admin action to manually clear reference cache
add_action('wp_ajax_ktpwp_manual_cache_clear', function() {
    if (!current_user_can('manage_options')) {
        wp_die('権限がありません');
    }
    
    if (class_exists('KTPWP_Plugin_Reference')) {
        KTPWP_Plugin_Reference::clear_all_cache();
        wp_die('プラグインリファレンスキャッシュをクリアしました。バージョン: ' . KANTANPRO_PLUGIN_VERSION);
    } else {
        wp_die('プラグインリファレンスクラスが見つかりません');
    }
});

// Add admin menu for cache clearing (for debugging)
if (defined('WP_DEBUG') && WP_DEBUG) {
    add_action('admin_menu', function() {
        add_submenu_page(
            null, // 非表示メニュー
            'Clear Reference Cache',
            'Clear Reference Cache',
            'manage_options',
            'ktpwp-clear-cache',
            function() {
                if (isset($_GET['action']) && $_GET['action'] === 'clear') {
                    if (class_exists('KTPWP_Plugin_Reference')) {
                        KTPWP_Plugin_Reference::clear_all_cache();
                        echo '<div class="notice notice-success"><p>キャッシュをクリアしました。バージョン: ' . KANTANPRO_PLUGIN_VERSION . '</p></div>';
                    }
                }
                echo '<div class="wrap">';
                echo '<h1>KantanPro リファレンスキャッシュクリア</h1>';
                echo '<p><a href="?page=ktpwp-clear-cache&action=clear" class="button button-primary">キャッシュをクリア</a></p>';
                echo '<p>現在のバージョン: ' . KANTANPRO_PLUGIN_VERSION . '</p>';
                echo '</div>';
            }
        );
    });
}

} // End if class_exists
