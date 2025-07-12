<?php
/**
 * Donation management class for KTPDone plugin
 *
 * Handles donation functionality including Stripe integration,
 * donation tracking, and frontend display.
 *
 * @package KTPDone
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

/**
 * Donation management class
 *
 * @since 1.0.0
 */
class KTPDone_Donation {
    
    /**
     * Single instance of the class
     *
     * @var KTPDone_Donation
     */
    private static $instance = null;
    
    /**
     * Get singleton instance
     *
     * @since 1.0.0
     * @return KTPDone_Donation
     */
    public static function get_instance() {
        if ( null === self::$instance ) {
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
        $this->init_hooks();
    }
    
    /**
     * Initialize hooks
     *
     * @since 1.0.0
     */
    private function init_hooks() {
        // ショートコード登録
        add_shortcode( 'ktpdone_donation', array( $this, 'render_donation_form' ) );
        
        // AJAX処理
        add_action( 'wp_ajax_ktpdone_create_payment_intent', array( $this, 'create_payment_intent' ) );
        add_action( 'wp_ajax_nopriv_ktpdone_create_payment_intent', array( $this, 'create_payment_intent' ) );
        
        add_action( 'wp_ajax_ktpdone_confirm_donation', array( $this, 'confirm_donation' ) );
        add_action( 'wp_ajax_nopriv_ktpdone_confirm_donation', array( $this, 'confirm_donation' ) );
        
        add_action( 'wp_ajax_ktpdone_get_donation_progress', array( $this, 'get_donation_progress' ) );
        add_action( 'wp_ajax_nopriv_ktpdone_get_donation_progress', array( $this, 'get_donation_progress' ) );
        
        // フロントエンド通知のAJAX処理
        add_action( 'wp_ajax_ktpdone_dismiss_donation_notice', array( $this, 'dismiss_donation_notice' ) );
        add_action( 'wp_ajax_nopriv_ktpdone_dismiss_donation_notice', array( $this, 'dismiss_donation_notice' ) );
        
        // 寄付完了確認のAJAX処理
        add_action( 'wp_ajax_ktpdone_check_donation_completion', array( $this, 'check_donation_completion' ) );
        add_action( 'wp_ajax_nopriv_ktpdone_check_donation_completion', array( $this, 'check_donation_completion' ) );
        
        // 寄付完了後のフック
        add_action( 'ktpdone_donation_completed', array( $this, 'send_thank_you_email' ) );
        
        // スクリプトとスタイルの読み込み
        add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_scripts' ) );
        
        // フロントエンド通知の表示
        add_action( 'wp_footer', array( $this, 'display_frontend_notice' ) );
        add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_frontend_notice_scripts' ) );
    }
    
    /**
     * 寄付関連テーブルの作成
     *
     * @since 1.0.0
     */
    public function create_donation_tables() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'ktpdone_donations';
        $charset_collate = $wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            amount decimal(10,2) NOT NULL,
            currency varchar(3) NOT NULL DEFAULT 'JPY',
            donor_name varchar(255) DEFAULT '',
            donor_email varchar(255) DEFAULT '',
            donor_message text,
            stripe_payment_intent_id varchar(255) DEFAULT '',
            status varchar(20) DEFAULT 'pending',
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY status (status),
            KEY created_at (created_at)
        ) $charset_collate;";
        
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta( $sql );
        
        // テーブル作成ログ
        if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
            error_log( 'KTPDone: Created donations table' );
        }
    }
    
    /**
     * スクリプトとスタイルの読み込み
     *
     * @since 1.0.0
     */
    public function enqueue_scripts() {
        // 寄付フォームがあるページでのみ読み込み
        if ( $this->has_donation_shortcode() ) {
            wp_enqueue_script( 'stripe-js', 'https://js.stripe.com/v3/', array(), null, true );
            wp_enqueue_script( 
                'ktpdone-donation', 
                KTPDONE_PLUGIN_URL . 'js/ktpdone-donation.js', 
                array( 'jquery', 'stripe-js' ), 
                KTPDONE_VERSION, 
                true 
            );
            
            // Ajax URLとnonceを渡す
            wp_localize_script( 'ktpdone-donation', 'ktpdone_donation', array(
                'ajax_url' => admin_url( 'admin-ajax.php' ),
                'nonce' => wp_create_nonce( 'ktpdone_donation_nonce' ),
                'stripe_publishable_key' => $this->get_stripe_publishable_key(),
                'currency' => 'jpy'
            ) );
            
            wp_enqueue_style( 
                'ktpdone-donation', 
                KTPDONE_PLUGIN_URL . 'css/ktpdone-donation.css', 
                array(), 
                KTPDONE_VERSION 
            );
        }
    }

    /**
     * フロントエンド通知用スクリプトとスタイルの読み込み
     *
     * @since 1.0.0
     */
    public function enqueue_frontend_notice_scripts() {
        if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
            error_log( 'KTPDone Donation: enqueue_frontend_notice_scripts() called' );
            error_log( 'KTPDone Donation: is_admin() = ' . ( is_admin() ? 'true' : 'false' ) );
            error_log( 'KTPDone Donation: should_show_frontend_notice() = ' . ( $this->should_show_frontend_notice() ? 'true' : 'false' ) );
        }
        
        // 管理画面以外で寄付通知が有効な場合のみ読み込み
        $should_enqueue = ! is_admin() && $this->should_show_frontend_notice();
        
        if ( $should_enqueue ) {
            if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
                error_log( 'KTPDone Donation: enqueuing frontend notice scripts' );
            }
            
            // 寄付通知用スクリプト
            wp_enqueue_script( 
                'ktpdone-donation-notice', 
                KTPDONE_PLUGIN_URL . 'js/ktpdone-donation-notice.js', 
                array( 'jquery' ), 
                KTPDONE_VERSION, 
                true 
            );
            
            // Ajax URLとnonceを渡す
            wp_localize_script( 'ktpdone-donation-notice', 'ktpdone_donation_notice', array(
                'ajax_url' => admin_url( 'admin-ajax.php' ),
                'nonce' => wp_create_nonce( 'ktpdone_donation_notice_nonce' ),
                'dismiss_text' => __( '閉じる', 'ktpdone' ),
                'donate_text' => __( '寄付する', 'ktpdone' ),
                'donation_url' => home_url( '/donation/' )
            ) );
            
            wp_enqueue_style( 
                'ktpdone-donation-notice', 
                KTPDONE_PLUGIN_URL . 'css/ktpdone-donation-notice.css', 
                array(), 
                KTPDONE_VERSION 
            );
        } else {
            if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
                error_log( 'KTPDone Donation: not enqueuing frontend notice scripts' );
            }
        }
    }

    /**
     * 管理画面用スクリプトとスタイルの読み込み
     *
     * @since 1.0.0
     */
    public function enqueue_admin_scripts( $hook ) {
        // 管理画面での寄付フォーム表示用
        if ( $this->has_donation_shortcode() ) {
            wp_enqueue_script( 'stripe-js', 'https://js.stripe.com/v3/', array(), null, true );
            wp_enqueue_script( 
                'ktpdone-donation', 
                KTPDONE_PLUGIN_URL . 'js/ktpdone-donation.js', 
                array( 'jquery', 'stripe-js' ), 
                KTPDONE_VERSION, 
                true 
            );
            
            // Ajax URLとnonceを渡す
            wp_localize_script( 'ktpdone-donation', 'ktpdone_donation', array(
                'ajax_url' => admin_url( 'admin-ajax.php' ),
                'nonce' => wp_create_nonce( 'ktpdone_donation_nonce' ),
                'stripe_publishable_key' => $this->get_stripe_publishable_key(),
                'currency' => 'jpy'
            ) );
            
            wp_enqueue_style( 
                'ktpdone-donation', 
                KTPDONE_PLUGIN_URL . 'css/ktpdone-donation.css', 
                array(), 
                KTPDONE_VERSION 
            );
        }
    }

    /**
     * 寄付ショートコードが存在するかチェック
     *
     * @since 1.0.0
     * @return bool
     */
    private function has_donation_shortcode() {
        global $post;
        return is_a( $post, 'WP_Post' ) && has_shortcode( $post->post_content, 'ktpdone_donation' );
    }

    /**
     * Stripe公開キーを取得
     *
     * @since 1.0.0
     * @return string
     */
    private function get_stripe_publishable_key() {
        $payment_settings = get_option( 'ktpdone_payment_settings', array() );
        return $payment_settings['stripe_publishable_key'] ?? '';
    }

    /**
     * APIキーを復号化
     *
     * @since 1.0.0
     * @param string $encrypted_key 暗号化されたキー
     * @return string
     */
    private function decrypt_api_key( $encrypted_key ) {
        if ( empty( $encrypted_key ) ) {
            return '';
        }

        // WordPressのソルトキーを使用して復号化
        $key = wp_salt( 'auth' );
        $iv = substr( hash( 'sha256', $key ), 0, 16 );
        
        $decrypted = openssl_decrypt(
            base64_decode( $encrypted_key ),
            'AES-256-CBC',
            $key,
            0,
            $iv
        );

        return $decrypted ?: '';
    }

    /**
     * 寄付フォームのレンダリング
     *
     * @since 1.0.0
     * @param array $atts ショートコードの属性
     * @return string
     */
    public function render_donation_form( $atts = array() ) {
        // Stripe設定を確認
        if ( empty( $this->get_stripe_publishable_key() ) ) {
            return '<div class="ktpdone-error">Stripe設定が完了していません。</div>';
        }

        // 寄付設定を取得
        $donation_settings = get_option( 'ktpdone_donation_settings', array() );
        $suggested_amounts = isset( $donation_settings['suggested_amounts'] ) ? 
            explode( ',', $donation_settings['suggested_amounts'] ) : array( '500', '1000', '3000', '5000' );

        ob_start();
        ?>
        <div class="ktpdone-donation-form">
            <h3><?php _e( '開発支援の寄付', 'ktpdone' ); ?></h3>
            <p><?php _e( 'システム開発を継続するために費用がかかります。よろしければご寄付をお願いいたします。', 'ktpdone' ); ?></p>
            
            <form id="ktpdone-donation-form">
                <div class="ktpdone-amount-selection">
                    <label><?php _e( '寄付金額', 'ktpdone' ); ?></label>
                    <div class="ktpdone-suggested-amounts">
                        <?php foreach ( $suggested_amounts as $amount ) : ?>
                            <button type="button" class="ktpdone-amount-btn" data-amount="<?php echo esc_attr( $amount ); ?>">
                                ¥<?php echo esc_html( number_format( $amount ) ); ?>
                            </button>
                        <?php endforeach; ?>
                    </div>
                    <input type="number" id="ktpdone-custom-amount" placeholder="<?php _e( 'その他の金額', 'ktpdone' ); ?>" min="100" step="100">
                </div>

                <div class="ktpdone-donor-info">
                    <div class="ktpdone-form-group">
                        <label for="ktpdone-donor-name"><?php _e( 'お名前', 'ktpdone' ); ?></label>
                        <input type="text" id="ktpdone-donor-name" name="donor_name" required>
                    </div>
                    
                    <div class="ktpdone-form-group">
                        <label for="ktpdone-donor-email"><?php _e( 'メールアドレス', 'ktpdone' ); ?></label>
                        <input type="email" id="ktpdone-donor-email" name="donor_email" required>
                    </div>
                    
                    <div class="ktpdone-form-group">
                        <label for="ktpdone-donor-message"><?php _e( 'メッセージ（任意）', 'ktpdone' ); ?></label>
                        <textarea id="ktpdone-donor-message" name="donor_message" rows="3"></textarea>
                    </div>
                </div>

                <div class="ktpdone-payment-section">
                    <div class="ktpdone-form-group">
                        <label><?php _e( 'カード情報', 'ktpdone' ); ?></label>
                        <div id="ktpdone-card-element"></div>
                        <div id="ktpdone-card-errors" class="ktpdone-error"></div>
                    </div>
                </div>

                <button type="submit" id="ktpdone-submit-btn" class="ktpdone-submit-btn">
                    <?php _e( '寄付する', 'ktpdone' ); ?>
                </button>
                
                <div id="ktpdone-processing" class="ktpdone-processing" style="display: none;">
                    <?php _e( '処理中...', 'ktpdone' ); ?>
                </div>
            </form>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Stripe Payment Intent作成
     *
     * @since 1.0.0
     */
    public function create_payment_intent() {
        // Nonce検証
        if ( ! wp_verify_nonce( $_POST['nonce'], 'ktpdone_donation_nonce' ) ) {
            wp_die( 'Security check failed' );
        }

        try {
            // 金額を取得
            $amount = intval( $_POST['amount'] );
            if ( $amount < 100 ) {
                throw new Exception( '寄付金額は100円以上でお願いします。' );
            }

            // Stripe設定を取得
            $stripe_settings = get_option( 'ktpdone_payment_settings', array() );
            
            if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
                error_log( 'KTPDone Donation: Payment settings: ' . wp_json_encode( $stripe_settings ) );
            }

            // シークレットキーを取得
            $encrypted_key = $stripe_settings['stripe_secret_key'] ?? '';
            if ( empty( $encrypted_key ) ) {
                if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
                    error_log( 'KTPDone Donation Error: Stripe secret key is empty in settings' );
                }
                throw new Exception( 'Stripe設定が完了していません。シークレットキーが取得できません。' );
            }

            // キーを復号化
            $secret_key = $this->decrypt_api_key( $encrypted_key );
            if ( empty( $secret_key ) ) {
                if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
                    error_log( 'KTPDone Donation Error: Failed to decrypt Stripe secret key' );
                }
                throw new Exception( 'Stripe設定が完了していません。シークレットキーが取得できません。' );
            }

            // Stripeキーの形式を確認
            if ( ! preg_match( '/^sk_(live|test)_[a-zA-Z0-9]{24}$/', $secret_key ) ) {
                if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
                    error_log( 'KTPDone Donation Error: Invalid Stripe secret key format' );
                }
                throw new Exception( 'Stripeシークレットキーの形式が正しくありません。' );
            }

            // Stripe SDK初期化
            \Stripe\Stripe::setApiKey( $secret_key );

            // Payment Intent作成
            $intent = \Stripe\PaymentIntent::create([
                'amount' => $amount,
                'currency' => 'jpy',
                'metadata' => [
                    'donor_name' => sanitize_text_field( $_POST['donor_name'] ?? '' ),
                    'donor_email' => sanitize_email( $_POST['donor_email'] ?? '' ),
                    'donor_message' => sanitize_textarea_field( $_POST['donor_message'] ?? '' ),
                ]
            ]);

            wp_send_json_success( array(
                'client_secret' => $intent->client_secret,
                'payment_intent_id' => $intent->id
            ) );

        } catch ( Exception $e ) {
            if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
                error_log( 'KTPDone Donation Error: ' . $e->getMessage() );
            }

            if ( $e instanceof \Stripe\Exception\ApiErrorException ) {
                if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
                    error_log( 'KTPDone Donation Stripe Error: ' . wp_json_encode( $e->getJsonBody() ) );
                }
                wp_send_json_error( '決済処理でエラーが発生しました: ' . $e->getMessage() );
            } else {
                wp_send_json_error( $e->getMessage() );
            }
        }
    }

    /**
     * 寄付確認処理
     *
     * @since 1.0.0
     */
    public function confirm_donation() {
        // Nonce検証
        if ( ! wp_verify_nonce( $_POST['nonce'], 'ktpdone_donation_nonce' ) ) {
            wp_die( 'Security check failed' );
        }

        try {
            $payment_intent_id = sanitize_text_field( $_POST['payment_intent_id'] );
            $amount = intval( $_POST['amount'] );
            $donor_name = sanitize_text_field( $_POST['donor_name'] );
            $donor_email = sanitize_email( $_POST['donor_email'] );
            $donor_message = sanitize_textarea_field( $_POST['donor_message'] );

            // 寄付レコードを作成
            $donation_id = $this->create_donation_record( $amount, $donor_name, $donor_email, $donor_message, $payment_intent_id );

            if ( $donation_id ) {
                // 寄付完了フックを実行
                do_action( 'ktpdone_donation_completed', $donation_id );
                
                wp_send_json_success( array(
                    'message' => '寄付が完了しました。ありがとうございます。',
                    'donation_id' => $donation_id
                ) );
            } else {
                wp_send_json_error( '寄付の記録に失敗しました。' );
            }

        } catch ( Exception $e ) {
            wp_send_json_error( $e->getMessage() );
        }
    }

    /**
     * 寄付進捗の取得
     *
     * @since 1.0.0
     */
    public function get_donation_progress() {
        // Nonce検証
        if ( ! wp_verify_nonce( $_POST['nonce'], 'ktpdone_donation_nonce' ) ) {
            wp_die( 'Security check failed' );
        }

        $monthly_total = $this->get_monthly_total();
        $monthly_goal = get_option( 'ktpdone_donation_settings', array() )['monthly_goal'] ?? 10000;
        $progress_percentage = min( 100, ( $monthly_total / $monthly_goal ) * 100 );

        wp_send_json_success( array(
            'total' => $monthly_total,
            'goal' => $monthly_goal,
            'percentage' => $progress_percentage
        ) );
    }

    /**
     * 寄付完了確認
     *
     * @since 1.0.0
     */
    public function check_donation_completion() {
        // Nonce検証
        if ( ! wp_verify_nonce( $_POST['nonce'], 'ktpdone_donation_nonce' ) ) {
            wp_die( 'Security check failed' );
        }

        $user_id = get_current_user_id();
        if ( ! $user_id ) {
            wp_send_json_error( 'ユーザーが認証されていません。' );
        }

        // Stripeでの実際の入金確認を実行
        $has_donated = $this->verify_stripe_payment( $user_id );

        if ( $has_donated ) {
            wp_send_json_success( array(
                'has_donated' => true,
                'message' => '寄付が確認されました。ありがとうございます。'
            ) );
        } else {
            wp_send_json_success( array(
                'has_donated' => false,
                'message' => '寄付がまだ確認されていません。'
            ) );
        }
    }

    /**
     * 寄付レコードを作成
     *
     * @since 1.0.0
     * @param int $amount 金額
     * @param string $donor_name 寄付者名
     * @param string $donor_email 寄付者メール
     * @param string $donor_message メッセージ
     * @param string $payment_intent_id Stripe Payment Intent ID
     * @return int|false
     */
    private function create_donation_record( $amount, $donor_name, $donor_email, $donor_message, $payment_intent_id ) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'ktpdone_donations';
        
        $result = $wpdb->insert(
            $table_name,
            array(
                'amount' => $amount,
                'currency' => 'JPY',
                'donor_name' => $donor_name,
                'donor_email' => $donor_email,
                'donor_message' => $donor_message,
                'stripe_payment_intent_id' => $payment_intent_id,
                'status' => 'pending'
            ),
            array( '%f', '%s', '%s', '%s', '%s', '%s', '%s' )
        );

        return $result ? $wpdb->insert_id : false;
    }

    /**
     * 寄付ステータスを更新
     *
     * @since 1.0.0
     * @param int $donation_id 寄付ID
     * @param string $status ステータス
     * @return bool
     */
    private function update_donation_status( $donation_id, $status ) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'ktpdone_donations';
        
        return $wpdb->update(
            $table_name,
            array( 'status' => $status ),
            array( 'id' => $donation_id ),
            array( '%s' ),
            array( '%d' )
        );
    }

    /**
     * 月間寄付総額を取得
     *
     * @since 1.0.0
     * @return float
     */
    public function get_monthly_total() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'ktpdone_donations';
        
        $current_month = date( 'Y-m' );
        
        $total = $wpdb->get_var( $wpdb->prepare(
            "SELECT SUM(amount) FROM $table_name 
             WHERE status = 'completed' 
             AND DATE_FORMAT(created_at, '%%Y-%%m') = %s",
            $current_month
        ) );
        
        return $total ? floatval( $total ) : 0;
    }

    /**
     * 月間進捗を取得
     *
     * @since 1.0.0
     * @return array
     */
    public function get_monthly_progress() {
        $total = $this->get_monthly_total();
        $goal = get_option( 'ktpdone_donation_settings', array() )['monthly_goal'] ?? 10000;
        
        return array(
            'total' => $total,
            'goal' => $goal,
            'percentage' => min( 100, ( $total / $goal ) * 100 )
        );
    }

    /**
     * お礼メールを送信
     *
     * @since 1.0.0
     * @param int $donation_id 寄付ID
     */
    public function send_thank_you_email( $donation_id ) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'ktpdone_donations';
        $donation = $wpdb->get_row( $wpdb->prepare(
            "SELECT * FROM $table_name WHERE id = %d",
            $donation_id
        ) );

        if ( ! $donation ) {
            return;
        }

        $to = $donation->donor_email;
        $subject = 'ご寄付ありがとうございます - KantanPro';
        $message = sprintf(
            "お名前: %s\n寄付金額: ¥%s\n\nご寄付ありがとうございます。\nシステム開発の継続に活用させていただきます。",
            $donation->donor_name,
            number_format( $donation->amount )
        );

        wp_mail( $to, $subject, $message );
    }

    /**
     * フロントエンド通知の表示
     *
     * @since 1.0.0
     */
    public function display_frontend_notice() {
        if ( ! $this->should_show_frontend_notice() ) {
            return;
        }

        $donation_settings = get_option( 'ktpdone_donation_settings', array() );
        $message = $donation_settings['notice_message'] ?? 'システム開発を継続するために費用がかかります。よろしければご寄付をお願いいたします。';

        ?>
        <div id="ktpdone-notice" class="ktpdone-notice">
            <div class="ktpdone-notice-content">
                <p><?php echo esc_html( $message ); ?></p>
                <div class="ktpdone-notice-buttons">
                    <a href="<?php echo esc_url( home_url( '/donation/' ) ); ?>" class="ktpdone-donate-btn">
                        <?php _e( '寄付する', 'ktpdone' ); ?>
                    </a>
                    <button class="ktpdone-dismiss-btn">
                        <?php _e( '閉じる', 'ktpdone' ); ?>
                    </button>
                </div>
            </div>
        </div>
        <?php
    }

    /**
     * 寄付通知を非表示にする
     *
     * @since 1.0.0
     */
    public function dismiss_donation_notice() {
        // Nonce検証
        if ( ! wp_verify_nonce( $_POST['nonce'], 'ktpdone_donation_notice_nonce' ) ) {
            wp_die( 'Security check failed' );
        }

        $user_id = get_current_user_id();
        if ( $user_id ) {
            update_user_meta( $user_id, 'ktpdone_notice_dismissed', current_time( 'timestamp' ) );
        }

        wp_send_json_success();
    }

    /**
     * フロントエンド通知を表示すべきかチェック
     *
     * @since 1.0.0
     * @return bool
     */
    private function should_show_frontend_notice() {
        // 管理画面では表示しない
        if ( is_admin() ) {
            return false;
        }

        // 寄付設定を確認
        $donation_settings = get_option( 'ktpdone_donation_settings', array() );
        if ( empty( $donation_settings['enabled'] ) ) {
            return false;
        }

        // ログインユーザーの場合
        $user_id = get_current_user_id();
        if ( $user_id ) {
            // 既に寄付済みの場合は表示しない
            if ( $this->user_has_donated( $user_id ) ) {
                return false;
            }

            // 通知を非表示にした場合は表示しない
            if ( $this->user_has_dismissed_notice( $user_id ) ) {
                return false;
            }
        }

        return true;
    }

    /**
     * ユーザーが寄付済みかチェック
     *
     * @since 1.0.0
     * @param int $user_id ユーザーID
     * @return bool
     */
    private function user_has_donated( $user_id ) {
        // Stripeでの実際の入金確認を実行
        $has_donated = $this->verify_stripe_payment( $user_id );

        if ( $has_donated ) {
            // 寄付済みフラグを保存
            update_user_meta( $user_id, 'ktpdone_has_donated', true );
            return true;
        }

        return false;
    }

    /**
     * Stripeでの実際の入金確認を実行
     *
     * @since 1.0.0
     * @param int $user_id ユーザーID
     * @return bool
     */
    private function verify_stripe_payment( $user_id ) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'ktpdone_donations';
        
        // 最近の寄付を取得
        $recent_donation = $wpdb->get_row( $wpdb->prepare(
            "SELECT * FROM $table_name 
             WHERE donor_email = %s 
             AND status = 'pending'
             ORDER BY created_at DESC 
             LIMIT 1",
            get_userdata( $user_id )->user_email
        ) );

        if ( ! $recent_donation ) {
            return false;
        }

        try {
            // Stripe設定を取得
            $stripe_settings = get_option( 'ktpdone_payment_settings', array() );
            $encrypted_key = $stripe_settings['stripe_secret_key'] ?? '';
            
            if ( empty( $encrypted_key ) ) {
                if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
                    error_log( 'KTPDone Donation: Stripe secret key not found' );
                }
                return false;
            }

            // キーを復号化
            $secret_key = $this->decrypt_api_key( $encrypted_key );
            if ( empty( $secret_key ) ) {
                if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
                    error_log( 'KTPDone Donation: Decrypted Stripe key is empty' );
                }
                return false;
            }

            // Stripe SDK初期化
            \Stripe\Stripe::setApiKey( $secret_key );

            // Payment Intentを取得
            $payment_intent = \Stripe\PaymentIntent::retrieve( $recent_donation->stripe_payment_intent_id );

            if ( $payment_intent->status === 'succeeded' ) {
                // ステータスを完了に更新
                $this->update_donation_status( $recent_donation->id, 'completed' );
                return true;
            }

        } catch ( Exception $e ) {
            if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
                error_log( 'KTPDone Donation Error: ' . $e->getMessage() );
            }
        }

        return false;
    }

    /**
     * ユーザーが通知を非表示にしたかチェック
     *
     * @since 1.0.0
     * @param int $user_id ユーザーID
     * @return bool
     */
    private function user_has_dismissed_notice( $user_id ) {
        $dismissed_time = get_user_meta( $user_id, 'ktpdone_notice_dismissed', true );
        
        if ( ! $dismissed_time ) {
            return false;
        }

        // 30日経過していれば再表示
        $donation_settings = get_option( 'ktpdone_donation_settings', array() );
        $interval = $donation_settings['notice_display_interval'] ?? 30;
        
        return ( current_time( 'timestamp' ) - $dismissed_time ) < ( $interval * DAY_IN_SECONDS );
    }
} 