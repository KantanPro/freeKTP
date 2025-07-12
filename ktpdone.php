<?php
/**
 * Plugin Name: KTPDone - KantanPro Donation System
 * Plugin URI: https://www.kantanpro.com
 * Description: 専用の寄付システム。KantanProプラグインの開発支援のための決済処理を担当します。
 * Version: 1.0.0
 * Author: KantanPro
 * Author URI: https://www.kantanpro.com
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: ktpdone
 * Domain Path: /languages
 * Requires at least: 5.0
 * Tested up to: 6.4
 * Requires PHP: 7.4
 * Network: false
 */

// 直接アクセスを防ぐ
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// プラグインの基本定数を定義
define( 'KTPDONE_VERSION', '1.0.0' );
define( 'KTPDONE_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'KTPDONE_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'KTPDONE_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );

// Composer autoload を読み込みます。これは Stripe ライブラリや他の依存関係に必要です。
if ( file_exists( KTPDONE_PLUGIN_DIR . 'vendor/autoload.php' ) ) {
    require_once KTPDONE_PLUGIN_DIR . 'vendor/autoload.php';
}

/**
 * プラグインのメインクラス
 */
class KTPDone {
    
    /**
     * インスタンス
     *
     * @var KTPDone
     */
    private static $instance = null;
    
    /**
     * 寄付クラスのインスタンス
     *
     * @var KTPDone_Donation
     */
    private $donation;
    
    /**
     * コンストラクタ
     */
    private function __construct() {
        $this->init_hooks();
    }
    
    /**
     * シングルトンインスタンスを取得
     *
     * @return KTPDone
     */
    public static function get_instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * フックを初期化
     */
    private function init_hooks() {
        // プラグインの初期化
        add_action( 'plugins_loaded', array( $this, 'init' ) );
        
        // プラグイン有効化時の処理
        register_activation_hook( __FILE__, array( $this, 'activate' ) );
        
        // プラグイン無効化時の処理
        register_deactivation_hook( __FILE__, array( $this, 'deactivate' ) );
    }
    
    /**
     * プラグインの初期化
     */
    public function init() {
        // 寄付クラスを読み込み
        require_once KTPDONE_PLUGIN_DIR . 'includes/class-ktpdone-donation.php';
        
        // 寄付クラスのインスタンスを作成
        if ( class_exists( 'KTPDone_Donation' ) ) {
            $this->donation = KTPDone_Donation::get_instance();
        }
        
        // 管理画面の初期化
        if ( is_admin() ) {
            $this->init_admin();
        }
    }
    
    /**
     * 管理画面の初期化
     */
    private function init_admin() {
        // 管理画面用のスクリプトとスタイルを読み込み
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_scripts' ) );
    }
    
    /**
     * 管理画面用のスクリプトとスタイルを読み込み
     */
    public function enqueue_admin_scripts( $hook ) {
        // 特定のページでのみ読み込み
        if ( strpos( $hook, 'ktpdone' ) !== false ) {
            wp_enqueue_script(
                'ktpdone-admin',
                KTPDONE_PLUGIN_URL . 'js/ktpdone-admin.js',
                array( 'jquery' ),
                KTPDONE_VERSION,
                true
            );
            
            wp_enqueue_style(
                'ktpdone-admin',
                KTPDONE_PLUGIN_URL . 'css/ktpdone-admin.css',
                array(),
                KTPDONE_VERSION
            );
        }
    }
    
    /**
     * プラグイン有効化時の処理
     */
    public function activate() {
        // 寄付テーブルを作成
        if ( class_exists( 'KTPDone_Donation' ) ) {
            $donation = KTPDone_Donation::get_instance();
            if ( method_exists( $donation, 'create_donation_tables' ) ) {
                $donation->create_donation_tables();
            }
        }
        
        // デフォルト設定を保存
        $this->save_default_settings();
        
        // フラッシュメッセージを設定
        add_option( 'ktpdone_activated', true );
    }
    
    /**
     * プラグイン無効化時の処理
     */
    public function deactivate() {
        // 必要に応じてクリーンアップ処理を追加
    }
    
    /**
     * デフォルト設定を保存
     */
    private function save_default_settings() {
        // 決済設定のデフォルト値を保存
        $payment_option_name = 'ktpdone_payment_settings';
        $payment_defaults = array(
            'stripe_publishable_key' => '',
            'stripe_secret_key'      => '',
        );
        
        if ( false === get_option( $payment_option_name ) ) {
            add_option( $payment_option_name, $payment_defaults );
        }
        
        // 寄付設定のデフォルト値を保存
        $donation_option_name = 'ktpdone_donation_settings';
        $donation_defaults = array(
            'enabled'                 => true,
            'monthly_goal'            => 10000,
            'suggested_amounts'       => '500,1000,3000,5000',
            'notice_message'          => 'システム開発を継続するために費用がかかります。よろしければご寄付をお願いいたします。',
        );
        
        if ( false === get_option( $donation_option_name ) ) {
            add_option( $donation_option_name, $donation_defaults );
        }
    }
}

// プラグインの初期化
function ktpdone_init() {
    return KTPDone::get_instance();
}

// プラグインを開始
ktpdone_init(); 