<?php
/**
 * アセット管理クラス
 *
 * プラグインのCSS・JavaScriptファイルの読み込みを管理
 *
 * @package KTPWP
 * @since 1.0.0
 */

// セキュリティ: 直接アクセスを防止
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * アセット管理クラス
 */
class KTPWP_Assets {

    /**
     * CSSファイルリスト
     *
     * @var array
     */
    private $styles = array();

    /**
     * JavaScriptファイルリスト
     *
     * @var array
     */
    private $scripts = array();

    /**
     * コンストラクタ
     */
    public function __construct() {
        $this->setup_assets();
    }

    /**
     * 初期化
     */
    public function init() {
        $this->init_hooks();
    }

    /**
     * フック初期化
     */
    private function init_hooks() {
        add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_frontend_assets' ) );
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_assets' ) );
        add_action( 'wp_head', array( $this, 'add_preload_links' ), 1 );
        add_action( 'wp_head', array( $this, 'output_ajax_config' ), 99 );
        add_action( 'wp_footer', array( $this, 'output_ajax_config_fallback' ), 1 );
    }

    /**
     * アセット設定
     */
    private function setup_assets() {
        $this->setup_styles();
        $this->setup_scripts();
    }

    /**
     * CSSファイル設定
     */
    private function setup_styles() {
        $this->styles = array(
            'ktp-css' => array(
                'src'    => 'css/styles.css',
                'deps'   => array(),
                'ver'    => KANTANPRO_PLUGIN_VERSION,
                'media'  => 'all',
                'admin'  => false,
            ),
            'ktp-styles-fixed' => array(
                'src'    => 'css/styles-fixed.css',
                'deps'   => array( 'ktp-css' ),
                'ver'    => KTPWP_PLUGIN_VERSION,
                'media'  => 'all',
                'admin'  => false,
            ),
            'ktp-progress-select' => array(
                'src'    => 'css/progress-select.css',
                'deps'   => array( 'ktp-css', 'ktp-styles-fixed' ),
                'ver'    => KTPWP_PLUGIN_VERSION,
                'media'  => 'all',
                'admin'  => false,
            ),
            'ktp-setting-tab' => array(
                'src'    => 'css/ktp-setting-tab.css',
                'deps'   => array( 'ktp-css' ),
                'ver'    => KTPWP_PLUGIN_VERSION,
                'media'  => 'all',
                'admin'  => false,
            ),
            'ktp-admin-settings' => array(
                'src'    => 'css/ktp-admin-settings.css',
                'deps'   => array(),
                'ver'    => KTPWP_PLUGIN_VERSION,
                'media'  => 'all',
                'admin'  => true,
            ),
            'material-symbols-outlined' => array(
                'src'    => 'https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@24,400,0,0',
                'deps'   => array(),
                'ver'    => KTPWP_PLUGIN_VERSION,
                'media'  => 'all',
                'admin'  => false,
            ),
        );
    }

    /**
     * JavaScriptファイル設定
     */
    private function setup_scripts() {
        $this->scripts = array(
            'ktp-js' => array(
                'src'       => 'js/ktp-js.js',
                'deps'      => array( 'jquery' ),
                'ver'       => KTPWP_PLUGIN_VERSION,
                'in_footer' => true,
                'admin'     => false,
                'localize'  => array(
                    'object' => 'ktpwpDebugMode',
                    'data'   => $this->get_debug_mode(),
                ),
            ),
            'ktp-progress-select' => array(
                'src'       => 'js/progress-select.js',
                'deps'      => array( 'jquery' ),
                'ver'       => KTPWP_PLUGIN_VERSION,
                'in_footer' => true,
                'admin'     => false,
            ),
            'ktp-order-inline-projectname' => array(
                'src'       => 'js/ktp-order-inline-projectname.js',
                'deps'      => array( 'jquery' ),
                'ver'       => KTPWP_PLUGIN_VERSION,
                'in_footer' => true,
                'admin'     => false,
                'localize'  => array(
                    'object' => 'ktpwp_inline_edit_nonce',
                    'data'   => array(
                        'nonce' => wp_create_nonce( 'ktp_update_project_name' )
                    ),
                    'capability' => 'manage_options',
                ),
            ),
            'ktp-invoice-items' => array(
                'src'       => 'js/ktp-invoice-items.js',
                'deps'      => array( 'jquery', 'jquery-ui-sortable' ),
                'ver'       => KTPWP_PLUGIN_VERSION,
                'in_footer' => true,
                'admin'     => false,
                'localize'  => array(
                    'object' => 'ktp_ajax_object',
                    'data'   => array(
                        'ajax_url' => admin_url( 'admin-ajax.php' ),
                        'nonce'    => wp_create_nonce( 'ktp_ajax_nonce' ),
                    ),
                ),
            ),
            'ktp-service-selector' => array(
                'src'       => 'js/ktp-service-selector.js',
                'deps'      => array( 'jquery', 'ktp-invoice-items' ),
                'ver'       => KTPWP_PLUGIN_VERSION,
                'in_footer' => true,
                'admin'     => false,
                'localize'  => array(
                    'object' => 'ktp_service_ajax_object',
                    'data'   => array(
                        'ajax_url' => admin_url( 'admin-ajax.php' ),
                        'nonce'    => wp_create_nonce( 'ktp_ajax_nonce' ),
                    ),
                ),
            ),
            'ktp-cost-items' => array(
                'src'       => 'js/ktp-cost-items.js',
                'deps'      => array( 'jquery', 'jquery-ui-sortable' ),
                'ver'       => KTPWP_PLUGIN_VERSION,
                'in_footer' => true,
                'admin'     => false,
                'localize'  => array(
                    'object' => 'ktp_ajax_object',
                    'data'   => array(
                        'ajax_url' => admin_url( 'admin-ajax.php' ),
                        'nonce'    => wp_create_nonce( 'ktp_ajax_nonce' ),
                    ),
                ),
            ),
            'ktp-calculation-debug' => array(
                'src'       => 'js/ktp-calculation-debug.js',
                'deps'      => array( 'jquery' ),
                'ver'       => KTPWP_PLUGIN_VERSION,
                'in_footer' => true,
                'admin'     => false,
            ),
            'ktp-calculation-test' => array(
                'src'       => 'js/ktp-calculation-test.js',
                'deps'      => array( 'jquery', 'ktp-calculation-debug' ),
                'ver'       => KTPWP_PLUGIN_VERSION,
                'in_footer' => true,
                'admin'     => false,
            ),
            'ktp-calculation-monitor' => array(
                'src'       => 'js/ktp-calculation-monitor.js',
                'deps'      => array( 'jquery', 'ktp-invoice-items', 'ktp-cost-items' ),
                'ver'       => KTPWP_PLUGIN_VERSION,
                'in_footer' => true,
                'admin'     => false,
            ),
            'ktp-email-popup' => array(
                'src'       => 'js/ktp-email-popup.js',
                'deps'      => array( 'jquery' ),
                'ver'       => KTPWP_PLUGIN_VERSION,
                'in_footer' => true,
                'admin'     => false,
                'localize'  => array(
                    'object' => 'ktp_ajax_object',
                    'data'   => array(
                        'ajax_url' => admin_url( 'admin-ajax.php' ),
                        'nonce'    => wp_create_nonce( 'ktpwp_ajax_nonce' ),
                    ),
                ),
            ),
            'ktp-order-preview' => array(
                'src'       => 'js/ktp-order-preview.js',
                'deps'      => array( 'jquery' ),
                'ver'       => KTPWP_PLUGIN_VERSION,
                'in_footer' => true,
                'admin'     => false,
            ),
            // 'ktp-skills-list-effects' => array(
            //     'src'       => 'js/skills-list-effects.js',
            //     'deps'      => array( 'jquery' ),
            //     'ver'       => KTPWP_PLUGIN_VERSION,
            //     'in_footer' => true,
            //     'admin'     => false,
            // ),
        );
    }

    /**
     * フロントエンドアセット読み込み
     */
    public function enqueue_frontend_assets() {
        // デバッグ: 一時的にすべてのユーザーに対してアセットを読み込み
        $should_load_assets = true;
        
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('KTPWP_Assets: Enqueuing frontend assets (debug mode - all users).');
        }

        $this->enqueue_styles( false );
        $this->enqueue_scripts( false );
        $this->localize_frontend_scripts();
    }

    /**
     * 管理画面アセット読み込み
     *
     * @param string $hook_suffix 現在の管理画面のフック
     */
    public function enqueue_admin_assets( $hook_suffix ) {
        // より包括的な条件でアセットを読み込み
        // KTPWPプラグインの管理画面、または固定ページの編集画面で読み込み
        if ( strpos( $hook_suffix, 'ktp-' ) !== false || 
             strpos( $hook_suffix, 'ktpwp-' ) !== false || 
             in_array( $hook_suffix, array( 'post.php', 'post-new.php', 'edit.php' ) ) ) {
            $this->enqueue_styles( true );
            $this->enqueue_scripts( true );
            $this->localize_frontend_scripts(); // 管理画面でもフロントエンド用のAJAX設定を追加
        }
    }

    /**
     * CSS読み込み
     *
     * @param bool $is_admin 管理画面かどうか
     */
    private function enqueue_styles( $is_admin = false ) {
        foreach ( $this->styles as $handle => $style ) {
            if ( $style['admin'] === $is_admin || ! $style['admin'] ) {
                $src = $this->get_asset_url( $style['src'] );
                wp_enqueue_style( $handle, $src, $style['deps'], $style['ver'], $style['media'] );
            }
        }
    }

    /**
     * JavaScript読み込み
     *
     * @param bool $is_admin 管理画面かどうか
     */
    private function enqueue_scripts( $is_admin = false ) {
        // 必要な基本スクリプトを読み込み
        wp_enqueue_script( 'jquery' );
        wp_enqueue_script( 'jquery-ui-sortable' );

        foreach ( $this->scripts as $handle => $script ) {
            if ( $script['admin'] === $is_admin || ! $script['admin'] ) {
                // 権限チェック
                if ( isset( $script['capability'] ) && ! current_user_can( $script['capability'] ) ) {
                    continue;
                }

                $src = $this->get_asset_url( $script['src'] );
                wp_enqueue_script( $handle, $src, $script['deps'], $script['ver'], $script['in_footer'] );

                // Localizeスクリプト
                if ( isset( $script['localize'] ) ) {
                    $this->localize_script( $handle, $script['localize'] );
                }

                // 管理画面でktp-jsスクリプトが読み込まれた場合、スタッフチャット用AJAX設定を追加
                if ( $is_admin && $handle === 'ktp-js' ) {
                    $ajax_data = $this->get_unified_ajax_config();

                    wp_add_inline_script( 'ktp-js', 'window.ktpwp_ajax = ' . json_encode($ajax_data) . ';', 'after' );
                    wp_add_inline_script( 'ktp-js', 'window.ktp_ajax_object = ' . json_encode($ajax_data) . ';', 'after' );
                    wp_add_inline_script( 'ktp-js', 'window.ajaxurl = ' . json_encode($ajax_data['ajax_url']) . ';', 'after' );
                    wp_add_inline_script( 'ktp-js', 'console.log("Admin: AJAX設定を出力 (unified nonce)", window.ktpwp_ajax);', 'after' );

                    if (defined('WP_DEBUG') && WP_DEBUG) {
                        error_log('KTPWP Assets: Admin AJAX config added for ktp-js with unified nonce: ' . json_encode($ajax_data));
                    }
                }
            }
        }
    }

    /**
     * フロントエンド用JavaScript設定
     */
    private function localize_frontend_scripts() {
        // デバッグログ
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('KTPWP Assets: localize_frontend_scripts called');
        }

        // 統一されたAJAX設定を使用
        $ajax_data = $this->get_unified_ajax_config();

        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('KTPWP Assets: AJAX data prepared with unified nonce: ' . json_encode($ajax_data));
        }

        wp_add_inline_script( 'ktp-js', 'var ktp_ajax_object = ' . json_encode($ajax_data) . ';' );
        wp_add_inline_script( 'ktp-js', 'var ktpwp_ajax = ' . json_encode($ajax_data) . ';' );
        wp_add_inline_script( 'ktp-js', 'var ajaxurl = ' . json_encode($ajax_data['ajax_url']) . ';' );

        // 翻訳ラベル
        wp_add_inline_script( 'ktp-js', 'var ktpwpCostShowLabel = ' . json_encode( esc_html__( '表示', 'ktpwp' ) ) . ';' );
        wp_add_inline_script( 'ktp-js', 'var ktpwpCostHideLabel = ' . json_encode( esc_html__( '非表示', 'ktpwp' ) ) . ';' );
        wp_add_inline_script( 'ktp-js', 'var ktpwpStaffChatShowLabel = ' . json_encode( esc_html__( '表示', 'ktpwp' ) ) . ';' );
        wp_add_inline_script( 'ktp-js', 'var ktpwpStaffChatHideLabel = ' . json_encode( esc_html__( '非表示', 'ktpwp' ) ) . ';' );

        // デバッグ情報
        wp_add_inline_script( 'ktp-js', 'console.log("Assets: AJAX設定を出力 (unified nonce)", window.ktpwp_ajax);' );

        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('KTPWP Assets: Inline scripts added with unified nonce');
        }
    }

    /**
     * スクリプトローカライズ
     *
     * @param string $handle スクリプトハンドル
     * @param array $localize_data ローカライズデータ
     */
    private function localize_script( $handle, $localize_data ) {
        // スクリプトが登録されているかチェック
        if ( ! wp_script_is( $handle, 'registered' ) ) {
            if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
                error_log( "KTPWP: Script handle '{$handle}' is not registered for localization." );
            }
            return;
        }

        // ローカライズデータが配列でない場合は処理しない
        if ( ! is_array( $localize_data ) ) {
            if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
                error_log( "KTPWP: Localize data for '{$handle}' must be an array." );
            }
            return;
        }

        // 複数のローカライズデータかどうかチェック（数値キーの配列で、最初の要素がobject/dataを持つ場合）
        if ( isset( $localize_data[0] ) && is_array( $localize_data[0] ) && isset( $localize_data[0]['object'] ) ) {
            // 複数のローカライズデータ
            foreach ( $localize_data as $data ) {
                if ( isset( $data['object'] ) && isset( $data['data'] ) ) {
                    // データが配列でない場合は配列に変換
                    $localize_array = is_array( $data['data'] ) ? $data['data'] : array( 'value' => $data['data'] );
                    wp_localize_script( $handle, $data['object'], $localize_array );
                }
            }
        } elseif ( isset( $localize_data['object'] ) && isset( $localize_data['data'] ) ) {
            // 単一のローカライズデータ
            // データが配列でない場合は配列に変換
            $localize_array = is_array( $localize_data['data'] ) ? $localize_data['data'] : array( 'value' => $localize_data['data'] );
            wp_localize_script( $handle, $localize_data['object'], $localize_array );
        } else {
            if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
                error_log( "KTPWP: Invalid localize data format for '{$handle}'. Expected 'object' and 'data' keys." );
            }
        }
    }

    /**
     * アセットURLの取得
     *
     * @param string $path ファイルパス
     * @return string
     */
    private function get_asset_url( $path ) {
        if ( strpos( $path, 'http' ) === 0 ) {
            // 外部URL
            return $path;
        }
        return KANTANPRO_PLUGIN_URL . $path;
    }

    /**
     * デバッグモードの取得
     *
     * @return bool
     */
    private function get_debug_mode() {
        return ( defined( 'WP_DEBUG' ) && WP_DEBUG ) || ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG );
    }

    /**
     * プリロードリンクの追加
     */
    public function add_preload_links() {
        // Google Fontsのプリロード
        echo '<link rel="preconnect" href="https://fonts.googleapis.com">' . "\n";
        echo '<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>' . "\n";
        echo '<link rel="preload" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@24,400,0,0" as="style" onload="this.onload=null;this.rel=\'stylesheet\'">' . "\n";

        // FontAwesome Webfont のプリロードは不要なため削除
    }

    /**
     * アセットを動的に追加
     *
     * @param string $handle ハンドル名
     * @param array $asset アセット設定
     * @param string $type 'style' または 'script'
     */
    public function add_asset( $handle, $asset, $type = 'script' ) {
        if ( $type === 'style' ) {
            $this->styles[ $handle ] = $asset;
        } else {
            $this->scripts[ $handle ] = $asset;
        }
    }

    /**
     * アセットを削除
     *
     * @param string $handle ハンドル名
     * @param string $type 'style' または 'script'
     */
    public function remove_asset( $handle, $type = 'script' ) {
        if ( $type === 'style' ) {
            unset( $this->styles[ $handle ] );
        } else {
            unset( $this->scripts[ $handle ] );
        }
    }

    /**
     * ナンス値を統一して取得
     *
     * @return string 統一されたstaff_chatナンス値
     */
    private function get_unified_staff_chat_nonce() {
        return KTPWP_Nonce_Manager::get_instance()->get_staff_chat_nonce();
    }

    /**
     * 統一されたAJAX設定を取得
     *
     * @return array 統一されたAJAX設定配列
     */
    private function get_unified_ajax_config() {
        return KTPWP_Nonce_Manager::get_instance()->get_unified_ajax_config();
    }

    /**
     * wp_headでAJAX設定を出力
     */
    public function output_ajax_config() {
        // デバッグ: 一時的にすべてのユーザーに対してAJAX設定を出力
        if (!wp_script_is('ktp-js', 'enqueued') && !wp_script_is('ktp-js', 'done')) {
            return;
        }

        $ajax_data = $this->get_unified_ajax_config();

        echo '<script type="text/javascript">';
        echo 'window.ktpwp_ajax = ' . json_encode($ajax_data) . ';';
        echo 'window.ktp_ajax_object = ' . json_encode($ajax_data) . ';';
        echo 'window.ajaxurl = ' . json_encode($ajax_data['ajax_url']) . ';';
        echo 'console.log("Head: AJAX設定を出力 (debug mode)", window.ktpwp_ajax);';
        echo '</script>';

        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('KTPWP Assets: AJAX config output in head (debug mode): ' . json_encode($ajax_data));
        }
    }

    /**
     * wp_footerでAJAX設定のフォールバック出力
     */
    public function output_ajax_config_fallback() {
        // デバッグ: 一時的にすべてのユーザーに対してAJAX設定を出力
        echo '<script type="text/javascript">';
        echo 'if (typeof window.ktpwp_ajax === "undefined") {';
        $ajax_data = $this->get_unified_ajax_config();
        echo 'window.ktpwp_ajax = ' . json_encode($ajax_data) . ';';
        echo 'window.ktp_ajax_object = ' . json_encode($ajax_data) . ';';
        echo 'window.ajaxurl = ' . json_encode($ajax_data['ajax_url']) . ';';
        echo 'console.log("Footer fallback: AJAX設定を出力 (debug mode)", window.ktpwp_ajax);';
        echo '}';
        echo '</script>';

        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('KTPWP Assets: Fallback AJAX config output in footer (debug mode)');
        }
    }
}
