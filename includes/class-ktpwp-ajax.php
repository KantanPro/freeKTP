<?php
/**
 * KTPWP Ajax処理管理クラス
 *
 * @package KTPWP
 * @since 0.1.0
 */

// セキュリティ: 直接アクセスを防止
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Ajax処理管理クラス
 */
class KTPWP_Ajax {

    /**
     * シングルトンインスタンス
     *
     * @var KTPWP_Ajax|null
     */
    private static $instance = null;

    /**
     * 登録されたAjaxハンドラー一覧
     *
     * @var array
     */
    private $registered_handlers = array();

    /**
     * nonce名の設定
     *
     * @var array
     */
    private $nonce_names = array(
        'auto_save' => 'ktp_ajax_nonce',
        'project_name' => 'ktp_update_project_name',
        'inline_edit' => 'ktpwp_inline_edit_nonce',
        'general' => 'ktpwp_ajax_nonce',
        'staff_chat' => 'ktpwp_staff_chat_nonce'
    );

    /**
     * シングルトンインスタンス取得
     *
     * @return KTPWP_Ajax
     */
    public static function get_instance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * コンストラクタ
     */
    private function __construct() {
        $this->init_hooks();
    }

    /**
     * フック初期化
     */
    private function init_hooks() {
        // 初期化処理
        add_action('init', array($this, 'register_ajax_handlers'), 10);

        // WordPress管理画面でのスクリプト読み込み時にnonce設定
        add_action('wp_enqueue_scripts', array($this, 'localize_ajax_scripts'), 99);
        add_action('admin_enqueue_scripts', array($this, 'localize_ajax_scripts'), 99);

        // フッターでのスクリプト出力は AJAX リクエスト中のみに制限
        add_action('wp_footer', array($this, 'conditional_ajax_localization'), 5);
        add_action('admin_footer', array($this, 'conditional_ajax_localization'), 5);
    }

    /**
     * Ajaxハンドラー登録
     */
    public function register_ajax_handlers() {
        // プロジェクト名インライン編集（管理者のみ）
        add_action('wp_ajax_ktp_update_project_name', array($this, 'ajax_update_project_name'));
        add_action('wp_ajax_nopriv_ktp_update_project_name', array($this, 'ajax_require_login'));
        $this->registered_handlers[] = 'ktp_update_project_name';

        // 受注関連のAjax処理を初期化
        $this->init_order_ajax_handlers();

        // サービス関連のAjax処理を初期化
        $this->init_service_ajax_handlers();

        // スタッフチャット関連のAjax処理を初期化
        $this->init_staff_chat_ajax_handlers();

        // ログイン中ユーザー取得
        add_action('wp_ajax_get_logged_in_users', array($this, 'ajax_get_logged_in_users'));
        add_action('wp_ajax_nopriv_get_logged_in_users', array($this, 'ajax_get_logged_in_users'));
        $this->registered_handlers[] = 'get_logged_in_users';

        // メール内容取得
        add_action('wp_ajax_get_email_content', array($this, 'ajax_get_email_content'));
        add_action('wp_ajax_nopriv_get_email_content', array($this, 'ajax_require_login'));
        $this->registered_handlers[] = 'get_email_content';

        // メール送信
        add_action('wp_ajax_send_order_email', array($this, 'ajax_send_order_email'));
        add_action('wp_ajax_nopriv_send_order_email', array($this, 'ajax_require_login'));
        $this->registered_handlers[] = 'send_order_email';

        // 最新の受注書プレビューデータ取得
        add_action('wp_ajax_ktp_get_order_preview', array($this, 'get_order_preview'));
        add_action('wp_ajax_nopriv_ktp_get_order_preview', array($this, 'ajax_require_login'));
        $this->registered_handlers[] = 'ktp_get_order_preview';
    }

    /**
     * 受注関連Ajaxハンドラー初期化
     */
    private function init_order_ajax_handlers() {
        // 受注クラスファイルの読み込み
        $order_class_file = KTPWP_PLUGIN_DIR . 'includes/class-tab-order.php';

        if (file_exists($order_class_file)) {
            require_once $order_class_file;

            if (class_exists('Kntan_Order_Class')) {
                // 直接このクラスのメソッドを使用して循環参照を回避
                // 自動保存
                add_action('wp_ajax_ktp_auto_save_item', array($this, 'ajax_auto_save_item'));
                add_action('wp_ajax_nopriv_ktp_auto_save_item', array($this, 'ajax_auto_save_item'));
                $this->registered_handlers[] = 'ktp_auto_save_item';

                // 新規アイテム作成
                add_action('wp_ajax_ktp_create_new_item', array($this, 'ajax_create_new_item'));
                add_action('wp_ajax_nopriv_ktp_create_new_item', array($this, 'ajax_create_new_item'));
                $this->registered_handlers[] = 'ktp_create_new_item';

                // アイテム削除
                add_action('wp_ajax_ktp_delete_item', array($this, 'ajax_delete_item'));
                add_action('wp_ajax_nopriv_ktp_delete_item', array($this, 'ajax_require_login')); // 非ログインユーザーはエラー
                $this->registered_handlers[] = 'ktp_delete_item';

                // アイテム並び順更新
                add_action('wp_ajax_ktp_update_item_order', array($this, 'ajax_update_item_order'));
                add_action('wp_ajax_nopriv_ktp_update_item_order', array($this, 'ajax_require_login')); // 非ログインユーザーはエラー
                $this->registered_handlers[] = 'ktp_update_item_order';
            }
        }
    }

    /**
     * スタッフチャット関連Ajaxハンドラー初期化
     */
    private function init_staff_chat_ajax_handlers() {
        // スタッフチャットクラスファイルの読み込み
        $staff_chat_class_file = KTPWP_PLUGIN_DIR . 'includes/class-ktpwp-staff-chat.php';

        if (file_exists($staff_chat_class_file)) {
            require_once $staff_chat_class_file;

            if (class_exists('KTPWP_Staff_Chat')) {
                // 最新チャットメッセージ取得
                add_action('wp_ajax_get_latest_staff_chat', array($this, 'ajax_get_latest_staff_chat'));
                add_action('wp_ajax_nopriv_get_latest_staff_chat', array($this, 'ajax_require_login'));
                $this->registered_handlers[] = 'get_latest_staff_chat';

                // チャットメッセージ送信
                add_action('wp_ajax_send_staff_chat_message', array($this, 'ajax_send_staff_chat_message'));
                add_action('wp_ajax_nopriv_send_staff_chat_message', array($this, 'ajax_send_staff_chat_message')); // For testing nopriv
                $this->registered_handlers[] = 'send_staff_chat_message';
            }
        }
    }

    /**
     * サービス関連Ajaxハンドラー初期化
     */
    private function init_service_ajax_handlers() {
        // サービス一覧取得
        add_action('wp_ajax_ktp_get_service_list', array($this, 'ajax_get_service_list'));
        add_action('wp_ajax_nopriv_ktp_get_service_list', array($this, 'ajax_get_service_list'));
        $this->registered_handlers[] = 'ktp_get_service_list';
    }

    /**
     * Ajaxスクリプトの設定
     */
    public function localize_ajax_scripts() {
        // 基本的なAjax URL設定
        $ajax_data = array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonces' => array(),
            'settings' => array()
        );

        // 一般設定の値を追加
        if (class_exists('KTP_Settings')) {
            $ajax_data['settings']['work_list_range'] = KTP_Settings::get_work_list_range();
        } else {
            $ajax_data['settings']['work_list_range'] = 20;
        }

        // 現在のユーザー情報を追加
        if (is_user_logged_in()) {
            $current_user = wp_get_current_user();
            $ajax_data['current_user'] = $current_user->display_name ? $current_user->display_name : $current_user->user_login;
        }

        // 各種nonceの設定
        foreach ($this->nonce_names as $action => $nonce_name) {
            if ($action === 'staff_chat') {
                // スタッフチャットnonceは必ず統一マネージャーから取得
                if (!class_exists('KTPWP_Nonce_Manager')) {
                    require_once KTPWP_PLUGIN_DIR . 'includes/class-ktpwp-nonce-manager.php';
                }
                $ajax_data['nonces'][$action] = KTPWP_Nonce_Manager::get_instance()->get_staff_chat_nonce();
            } elseif ($action === 'project_name' && current_user_can('manage_options')) {
                $ajax_data['nonces'][$action] = wp_create_nonce($nonce_name);
            } elseif ($action !== 'project_name') {
                $ajax_data['nonces'][$action] = wp_create_nonce($nonce_name);
            }
        }

        // JavaScriptファイルがエンキューされている場合のみlocalizeを実行
        global $wp_scripts;

        if (isset($wp_scripts->registered['ktp-js'])) {
            wp_add_inline_script('ktp-js', 'var ktp_ajax_object = ' . json_encode($ajax_data) . ';');
            wp_add_inline_script('ktp-js', 'var ktpwp_ajax = ' . json_encode($ajax_data) . ';');
            wp_add_inline_script('ktp-js', 'var ajaxurl = ' . json_encode($ajax_data['ajax_url']) . ';');
        }

        if (isset($wp_scripts->registered['ktp-invoice-items'])) {
            wp_add_inline_script('ktp-invoice-items', 'var ktp_ajax_nonce = ' . json_encode($ajax_data['nonces']['auto_save']) . ';');
            wp_add_inline_script('ktp-invoice-items', 'var ajaxurl = ' . json_encode($ajax_data['ajax_url']) . ';');
        }

        if (isset($wp_scripts->registered['ktp-cost-items'])) {
            wp_add_inline_script('ktp-cost-items', 'var ktp_ajax_nonce = ' . json_encode($ajax_data['nonces']['auto_save']) . ';');
            wp_add_inline_script('ktp-cost-items', 'var ajaxurl = ' . json_encode($ajax_data['ajax_url']) . ';');
        }

        // サービス選択機能専用のAJAX設定
        if (isset($wp_scripts->registered['ktp-service-selector'])) {
            wp_add_inline_script('ktp-service-selector', 'var ktp_service_ajax_object = ' . json_encode(array(
                'ajax_url' => $ajax_data['ajax_url'],
                'nonce' => $ajax_data['nonces']['auto_save'],
                'settings' => $ajax_data['settings']
            )) . ';');
        }

        if (isset($wp_scripts->registered['ktp-order-inline-projectname']) && current_user_can('manage_options')) {
            wp_add_inline_script('ktp-order-inline-projectname', 'var ktpwp_inline_edit_nonce = ' . json_encode(array(
                'nonce' => $ajax_data['nonces']['project_name']
            )) . ';');
        }
    }

    /**
     * 条件付きAJAX設定の確保（非AJAX時の出力を防ぐ）
     */
    public function conditional_ajax_localization() {
        // AJAX リクエスト中、またはWordPressコアの処理中は何も出力しない
        if (wp_doing_ajax() || defined('DOING_AJAX') || headers_sent()) {
            return;
        }

        // ページ編集画面でない場合は実行しない
        global $pagenow;
        if (!in_array($pagenow, array('post.php', 'post-new.php', 'admin.php'))) {
            return;
        }

        // KTPWPのスクリプトが読み込まれている場合のみ実行
        if (wp_script_is('ktp-js', 'done') || wp_script_is('ktp-js', 'enqueued')) {
            $this->ensure_ajax_localization();
        }
    }

    /**
     * AJAX設定が確実にロードされるようにするフォールバック
     */
    public function ensure_ajax_localization() {
        // グローバル変数が設定されていない場合のフォールバック
        if (!wp_script_is('ktp-js', 'done') && !wp_script_is('ktp-js', 'enqueued')) {
            return;
        }

        // 基本的なAJAX設定を再確認
        $ajax_data = array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'staff_chat_nonce' => wp_create_nonce('staff_chat_nonce'),
            'auto_save_nonce' => wp_create_nonce('ktp_auto_save_nonce')
        );

        // JavaScriptで利用可能になるよう出力
        echo '<script type="text/javascript">';
        echo 'if (typeof ktpwp_ajax === "undefined") {';
        echo 'var ktpwp_ajax = ' . json_encode($ajax_data) . ';';
        echo '}';
        echo 'if (typeof ajaxurl === "undefined") {';
        echo 'var ajaxurl = "' . admin_url('admin-ajax.php') . '";';
        echo '}';
        echo '</script>';
    }

    /**
     * Ajax: プロジェクト名更新（管理者のみ）
     */
    public function ajax_update_project_name() {
        // 共通バリデーション（管理者権限必須）
        if (!$this->validate_ajax_request('ktp_update_project_name', true)) {
            return; // エラーレスポンスは既に送信済み
        }

        // POSTデータの取得とサニタイズ
        $order_id = $this->sanitize_ajax_input('order_id', 'int');
        $project_name = $this->sanitize_ajax_input('project_name', 'text');

        // バリデーション
        if ($order_id <= 0) {
            $this->log_ajax_error('Invalid order ID for project name update', array('order_id' => $order_id));
            wp_send_json_error(__('無効な受注IDです', 'ktpwp'));
        }

        // 新しいクラス構造を使用してプロジェクト名を更新
        $order_manager = KTPWP_Order::get_instance();

        try {
            $result = $order_manager->update_order($order_id, array(
                'project_name' => $project_name
            ));

            if ($result) {
                wp_send_json_success(array(
                    'message' => __('プロジェクト名を更新しました', 'ktpwp'),
                    'project_name' => $project_name
                ));
            } else {
                $this->log_ajax_error('Failed to update project name', array(
                    'order_id' => $order_id,
                    'project_name' => $project_name
                ));
                wp_send_json_error(__('更新に失敗しました', 'ktpwp'));
            }
        } catch (Exception $e) {
            $this->log_ajax_error('Exception during project name update', array(
                'message' => $e->getMessage(),
                'order_id' => $order_id
            ));
            wp_send_json_error(__('更新中にエラーが発生しました', 'ktpwp'));
        }
    }

    /**
     * Ajax: ログイン中ユーザー取得
     */
    public function ajax_get_logged_in_users() {
        // 編集者以上の権限チェック
        if (!current_user_can('edit_posts') && !current_user_can('ktpwp_access')) {
            wp_send_json_error(__('この操作を行う権限がありません。', 'ktpwp'));
            return;
        }

        // Ajax以外からのアクセスは何も返さない
        if (
            !defined('DOING_AJAX') ||
            !DOING_AJAX ||
            (empty($_SERVER['HTTP_X_REQUESTED_WITH']) || strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) !== 'xmlhttprequest')
        ) {
            wp_die();
        }

        $logged_in_users = get_users(array(
            'meta_key' => 'session_tokens',
            'meta_compare' => 'EXISTS'
        ));

        $users_names = array();
        foreach ($logged_in_users as $user) {
            $users_names[] = esc_html($user->nickname) . 'さん';
        }

        wp_send_json($users_names);
    }

    /**
     * Ajax: ログイン要求（非ログインユーザー用）
     */
    public function ajax_require_login() {
        wp_send_json_error(__('ログインが必要です', 'ktpwp'));
    }

    /**
     * Ajax: 自動保存アイテム処理
     */
    public function ajax_auto_save_item() {
        // 編集者以上の権限チェック
        if (!current_user_can('edit_posts') && !current_user_can('ktpwp_access')) {
            $this->log_ajax_error('Auto-save permission check failed');
            wp_send_json_error(__('この操作を行う権限がありません。', 'ktpwp'));
            return;
        }

        // セキュリティチェック - 複数のnonce名でチェック
        $nonce_verified = false;
        $nonce_value = '';
        
        // 複数のnonce名でチェック
        $nonce_fields = ['nonce', 'ktp_ajax_nonce', '_ajax_nonce', '_wpnonce'];
        foreach ($nonce_fields as $field) {
            if (isset($_POST[$field])) {
                $nonce_value = $_POST[$field];
                if (wp_verify_nonce($nonce_value, 'ktp_ajax_nonce')) {
                    $nonce_verified = true;
                    error_log("[AJAX_AUTO_SAVE] Nonce verified with field: {$field}");
                    break;
                }
            }
        }
        
        if (!$nonce_verified) {
            error_log("[AJAX_AUTO_SAVE] Security check failed - tried fields: " . implode(', ', $nonce_fields));
            error_log("[AJAX_AUTO_SAVE] Available POST fields: " . implode(', ', array_keys($_POST)));
            $this->log_ajax_error('Auto-save security check failed', $_POST);
            wp_send_json_error(__('セキュリティ検証に失敗しました', 'ktpwp'));
        }

        // POSTデータの取得とサニタイズ
        $item_type = $this->sanitize_ajax_input('item_type', 'text');
        $item_id = $this->sanitize_ajax_input('item_id', 'int');
        $field_name = $this->sanitize_ajax_input('field_name', 'text');
        $field_value = $this->sanitize_ajax_input('field_value', 'text');
        $order_id = $this->sanitize_ajax_input('order_id', 'int');

        // バリデーション
        if (!in_array($item_type, array('invoice', 'cost'), true)) {
            $this->log_ajax_error('Invalid item type', array('type' => $item_type));
            wp_send_json_error(__('無効なアイテムタイプです', 'ktpwp'));
        }

        if ($item_id <= 0 || $order_id <= 0) {
            $this->log_ajax_error('Invalid ID values', array('item_id' => $item_id, 'order_id' => $order_id));
            wp_send_json_error(__('無効なIDです', 'ktpwp'));
        }

        // 新しいクラス構造を使用してアイテムを更新
        $order_items = KTPWP_Order_Items::get_instance();

        try {
            if ($item_type === 'invoice') {
                $result = $order_items->update_item_field('invoice', $item_id, $field_name, $field_value);
            } else {
                $result = $order_items->update_item_field('cost', $item_id, $field_name, $field_value);
            }

            if ($result) {
                wp_send_json_success(array(
                    'message' => __('正常に保存されました', 'ktpwp')
                ));
            } else {
                $this->log_ajax_error('Failed to update item', array(
                    'type' => $item_type,
                    'item_id' => $item_id,
                    'field' => $field_name
                ));
                wp_send_json_error(__('保存に失敗しました', 'ktpwp'));
            }
        } catch (Exception $e) {
            $this->log_ajax_error('Exception during auto-save', array(
                'message' => $e->getMessage(),
                'type' => $item_type,
                'item_id' => $item_id
            ));
            wp_send_json_error(__('保存中にエラーが発生しました', 'ktpwp'));
        }
    }

    /**
     * Ajax: 新規アイテム作成処理（強化版）
     */
    public function ajax_create_new_item() {
        // 編集者以上の権限チェック
        if (!current_user_can('edit_posts') && !current_user_can('ktpwp_access')) {
            $this->log_ajax_error('Create new item permission check failed');
            wp_send_json_error(__('この操作を行う権限がありません。', 'ktpwp'));
            return;
        }

        // 安全性の初期チェック（Adminer警告対策）
        if (!is_array($_POST) || empty($_POST)) {
            error_log('KTPWP Ajax: $_POST is not array or empty');
            wp_send_json_error(__('リクエストデータが無効です', 'ktpwp'));
            return;
        }

        // セキュリティチェック - 複数のnonce名でチェック
        $nonce_verified = false;
        $nonce_value = '';
        
        // 複数のnonce名でチェック
        $nonce_fields = ['nonce', 'ktp_ajax_nonce', '_ajax_nonce', '_wpnonce'];
        foreach ($nonce_fields as $field) {
            if (isset($_POST[$field])) {
                $nonce_value = $_POST[$field];
                if (wp_verify_nonce($nonce_value, 'ktp_ajax_nonce')) {
                    $nonce_verified = true;
                    error_log("[AJAX_CREATE_NEW_ITEM] Nonce verified with field: {$field}");
                    break;
                }
            }
        }
        
        if (!$nonce_verified) {
            error_log("[AJAX_CREATE_NEW_ITEM] Security check failed - tried fields: " . implode(', ', $nonce_fields));
            error_log("[AJAX_CREATE_NEW_ITEM] Available POST fields: " . implode(', ', array_keys($_POST)));
            $this->log_ajax_error('Create new item security check failed', $_POST);
            wp_send_json_error(__('セキュリティ検証に失敗しました', 'ktpwp'));
        }

        // POSTデータの取得とサニタイズ（強化版）
        $item_type = $this->sanitize_ajax_input('item_type', 'text');
        $field_name = $this->sanitize_ajax_input('field_name', 'text');
        $field_value = $this->sanitize_ajax_input('field_value', 'text');
        $order_id = $this->sanitize_ajax_input('order_id', 'int');

        // バリデーション
        if (!in_array($item_type, array('invoice', 'cost'), true)) {
            $this->log_ajax_error('Invalid item type for creation', array('type' => $item_type));
            wp_send_json_error(__('無効なアイテムタイプです', 'ktpwp'));
        }

        if ($order_id <= 0) {
            $this->log_ajax_error('Invalid order ID for creation', array('order_id' => $order_id));
            wp_send_json_error(__('無効な受注IDです', 'ktpwp'));
        }

        // 新しいクラス構造を使用してアイテムを作成
        $order_items = KTPWP_Order_Items::get_instance();

        try {

            // 新しいアイテムを作成
            $new_item_id = $order_items->create_new_item($item_type, $order_id);

            // 指定されたフィールド値を設定（アイテム作成後に更新）
            if (!empty($field_name) && !empty($field_value) && $new_item_id) {
                $update_result = $order_items->update_item_field($item_type, $new_item_id, $field_name, $field_value);
                if (!$update_result) {
                    error_log("KTPWP: Failed to update field {$field_name} for new item {$new_item_id}");
                }
            }

            if ($new_item_id) {
                wp_send_json_success(array(
                    'item_id' => $new_item_id,
                    'message' => __('新しいアイテムが作成されました', 'ktpwp')
                ));
            } else {
                $this->log_ajax_error('Failed to create new item', array(
                    'type' => $item_type,
                    'order_id' => $order_id
                ));
                wp_send_json_error(__('アイテムの作成に失敗しました', 'ktpwp'));
            }
        } catch (Exception $e) {
            $this->log_ajax_error('Exception during item creation', array(
                'message' => $e->getMessage(),
                'type' => $item_type,
                'order_id' => $order_id
            ));
            wp_send_json_error(__('作成中にエラーが発生しました', 'ktpwp'));
        }
    }

    /**
     * Ajax: アイテム削除処理
     */
    public function ajax_delete_item() {
        // 編集者以上の権限チェック
        if (!current_user_can('edit_posts') && !current_user_can('ktpwp_access')) {
            $this->log_ajax_error('Delete item permission check failed', $_POST);
            wp_send_json_error(__( 'この操作を行う権限がありません。', 'ktpwp' ));
            return;
        }

        // 受け取ったパラメータをログに出力
        error_log('[AJAX_DELETE_ITEM] Received params: ' . print_r($_POST, true));

        // セキュリティチェック - 複数のnonce名でチェック
        $nonce_verified = false;
        $nonce_value = '';
        
        // 複数のnonce名でチェック
        $nonce_fields = ['nonce', 'ktp_ajax_nonce', '_ajax_nonce', '_wpnonce'];
        foreach ($nonce_fields as $field) {
            if (isset($_POST[$field])) {
                $nonce_value = $_POST[$field];
                if (wp_verify_nonce($nonce_value, 'ktp_ajax_nonce')) {
                    $nonce_verified = true;
                    error_log("[AJAX_DELETE_ITEM] Nonce verified with field: {$field}");
                    break;
                }
            }
        }
        
        if (!$nonce_verified) {
            error_log("[AJAX_DELETE_ITEM] Security check failed - tried fields: " . implode(', ', $nonce_fields));
            error_log("[AJAX_DELETE_ITEM] Available POST fields: " . implode(', ', array_keys($_POST)));
            $this->log_ajax_error('Delete item security check failed', $_POST);
            wp_send_json_error(__( 'セキュリティ検証に失敗しました', 'ktpwp' ));
        }

        // POSTデータの取得とサニタイズ
        $item_type = $this->sanitize_ajax_input('item_type', 'text');
        $item_id = $this->sanitize_ajax_input('item_id', 'int');
        $order_id = $this->sanitize_ajax_input('order_id', 'int');

        // パラメータのログ出力（サニタイズ後）
        error_log("[AJAX_DELETE_ITEM] Sanitized params: item_type={$item_type}, item_id={$item_id}, order_id={$order_id}");

        // バリデーション
        if (!in_array($item_type, array('invoice', 'cost'), true)) {
            $this->log_ajax_error('Invalid item type for deletion', array('type' => $item_type));
            wp_send_json_error(__( '無効なアイテムタイプです', 'ktpwp' ));
        }

        if ($item_id <= 0) {
            $this->log_ajax_error('Invalid item ID for deletion', array('item_id' => $item_id, 'item_type' => $item_type, 'order_id' => $order_id));
            wp_send_json_error(__( '無効なアイテムIDです', 'ktpwp' ));
        }

        if ($order_id <= 0) {
            $this->log_ajax_error('Invalid order ID for deletion', array('order_id' => $order_id, 'item_type' => $item_type, 'item_id' => $item_id));
            wp_send_json_error(__( '無効な受注IDです', 'ktpwp' ));
        }

        // KTPWP_Order_Items クラスのインスタンスを取得
        // クラスが存在するか確認
        if (!class_exists('KTPWP_Order_Items')) {
            $this->log_ajax_error('KTPWP_Order_Items class not found');
            wp_send_json_error(__( '必要なクラスが見つかりません。', 'ktpwp' ));
            return; // ここで処理を中断
        }
        $order_items = KTPWP_Order_Items::get_instance();

        try {
            error_log("[AJAX_DELETE_ITEM] Calling KTPWP_Order_Items::delete_item({$item_type}, {$item_id}, {$order_id})");
            $result = $order_items->delete_item($item_type, $item_id, $order_id);
            error_log("[AJAX_DELETE_ITEM] delete_item result: " . print_r($result, true));

            if ($result) {
                error_log("[AJAX_DELETE_ITEM] Success: item deleted successfully");
                wp_send_json_success(array(
                    'message' => __('アイテムを削除しました', 'ktpwp')
                ));
            } else {
                error_log("[AJAX_DELETE_ITEM] Failure: delete_item returned false");
                $this->log_ajax_error('Failed to delete item from database (KTPWP_Order_Items::delete_item returned false)', array(
                    'item_type' => $item_type,
                    'item_id' => $item_id,
                    'order_id' => $order_id
                ));
                wp_send_json_error(__( 'データベースからのアイテム削除に失敗しました（詳細エラーログ確認）', 'ktpwp' ));
            }
        } catch (Exception $e) {
            $this->log_ajax_error('Exception during item deletion: ' . $e->getMessage() . ' Stack trace: ' . $e->getTraceAsString(), array(
                'message' => $e->getMessage(),
                'item_type' => $item_type,
                'item_id' => $item_id,
                'order_id' => $order_id,
                'trace' => $e->getTraceAsString()
            ));
            wp_send_json_error(__( 'アイテム削除中に予期せぬエラーが発生しました（詳細エラーログ確認）', 'ktpwp' ));
        }
    }

    /**
     * Ajax: アイテムの並び順更新処理
     */
    public function ajax_update_item_order() {
        error_log('[AJAX_UPDATE_ITEM_ORDER] Received params: ' . print_r($_POST, true));

        // 編集者以上の権限チェック
        if (!current_user_can('edit_posts') && !current_user_can('ktpwp_access')) {
            $this->log_ajax_error('Update item order permission check failed', $_POST);
            wp_send_json_error(__( 'この操作を行う権限がありません。', 'ktpwp' ));
            return;
        }

        // セキュリティチェック - 複数のnonce名でチェック
        $nonce_verified = false;
        $nonce_value = '';
        
        // 複数のnonce名でチェック
        $nonce_fields = ['nonce', 'ktp_ajax_nonce', '_ajax_nonce', '_wpnonce'];
        foreach ($nonce_fields as $field) {
            if (isset($_POST[$field])) {
                $nonce_value = sanitize_text_field($_POST[$field]);
                if (wp_verify_nonce($nonce_value, 'ktp_ajax_nonce')) {
                    $nonce_verified = true;
                    error_log("[AJAX_UPDATE_ITEM_ORDER] Nonce verified with field: {$field}, value: " . substr($nonce_value, 0, 10) . "...");
                    break;
                }
            }
        }
        
        if (!$nonce_verified) {
            error_log("[AJAX_UPDATE_ITEM_ORDER] Security check failed - tried fields: " . implode(', ', $nonce_fields));
            error_log("[AJAX_UPDATE_ITEM_ORDER] Available POST fields: " . implode(', ', array_keys($_POST)));
            foreach ($_POST as $key => $value) {
                if (strpos($key, 'nonce') !== false || strpos($key, '_wp') !== false) {
                    error_log("[AJAX_UPDATE_ITEM_ORDER] Found nonce-like field: {$key} = " . substr($value, 0, 10) . "...");
                }
            }
            $this->log_ajax_error('Update item order security check failed', $_POST);
            wp_send_json_error(__( 'セキュリティ検証に失敗しました', 'ktpwp' ));
        }

        // POSTデータの取得とサニタイズ
        $order_id = $this->sanitize_ajax_input('order_id', 'int');
        $items_data = isset($_POST['items']) && is_array($_POST['items']) ? $_POST['items'] : array();
        $item_type = $this->sanitize_ajax_input('item_type', 'text'); // 'invoice' or 'cost'

        // サニタイズされたパラメータのログ
        error_log("[AJAX_UPDATE_ITEM_ORDER] Sanitized params: order_id={$order_id}, item_type={$item_type}, items_count=" . count($items_data));

        // バリデーション
        if ($order_id <= 0) {
            $this->log_ajax_error('Invalid order ID for updating item order', array('order_id' => $order_id));
            wp_send_json_error(__( '無効な受注IDです', 'ktpwp' ));
        }

        if (!in_array($item_type, array('invoice', 'cost'), true)) {
            $this->log_ajax_error('Invalid item type for updating item order', array('item_type' => $item_type));
            wp_send_json_error(__( '無効なアイテムタイプです', 'ktpwp' ));
        }

        if (empty($items_data)) {
            $this->log_ajax_error('No items data provided for updating order', $_POST);
            wp_send_json_error(__( '更新するアイテムデータがありません', 'ktpwp' ));
        }

        // KTPWP_Order_Items クラスのインスタンスを取得
        if (!class_exists('KTPWP_Order_Items')) {
            $this->log_ajax_error('KTPWP_Order_Items class not found for updating item order');
            wp_send_json_error(__( '必要なクラスが見つかりません。', 'ktpwp' ));
            return;
        }
        $order_items_manager = KTPWP_Order_Items::get_instance();

        try {
            error_log("[AJAX_UPDATE_ITEM_ORDER] Calling KTPWP_Order_Items::update_items_order({$item_type}, {$order_id}, ...)");
            $result = $order_items_manager->update_items_order($item_type, $order_id, $items_data);
            error_log("[AJAX_UPDATE_ITEM_ORDER] update_items_order result: " . print_r($result, true));

            if ($result) {
                wp_send_json_success(array(
                    'message' => __( 'アイテムの並び順を更新しました', 'ktpwp' )
                ));
            } else {
                $this->log_ajax_error('Failed to update item order (KTPWP_Order_Items::update_items_order returned false)', array(
                    'item_type' => $item_type,
                    'order_id' => $order_id,
                    'items_data_count' => count($items_data)
                ));
                wp_send_json_error(__( 'データベースでのアイテム並び順更新に失敗しました（詳細エラーログ確認）', 'ktpwp' ));
            }
        } catch (Exception $e) {
            $this->log_ajax_error('Exception during item order update: ' . $e->getMessage() . ' Stack trace: ' . $e->getTraceAsString(), array(
                'message' => $e->getMessage(),
                'item_type' => $item_type,
                'order_id' => $order_id,
                'trace' => $e->getTraceAsString()
            ));
            wp_send_json_error(__( 'アイテムの並び順更新中に予期せぬエラーが発生しました（詳細エラーログ確認）', 'ktpwp' ));
        }
    }

    /**
     * Ajax: サービス一覧取得
     */
    public function ajax_get_service_list() {

        
        // 権限チェック
        if (!current_user_can('edit_posts') && !current_user_can('ktpwp_access')) {
            error_log('[AJAX] サービス一覧取得: 権限不足');
            wp_send_json_error(__('この操作を行う権限がありません', 'ktpwp'));
            return;
        }

        // デバッグログ
        error_log('[AJAX] サービス一覧取得開始: ' . print_r($_POST, true));

        // 簡単なnonceチェック
        if (!check_ajax_referer('ktp_ajax_nonce', 'nonce', false)) {
            error_log('[AJAX] サービス一覧取得: nonce検証失敗');
            wp_send_json_error(__('セキュリティチェックに失敗しました', 'ktpwp'));
            return;
        }

        error_log('[AJAX] サービス一覧取得: nonce検証成功');

        // パラメータ取得
        $page = isset($_POST['page']) ? absint($_POST['page']) : 1;
        
        // 一般設定から表示件数を取得
        if (isset($_POST['limit']) && $_POST['limit'] === 'auto') {
            // 一般設定から表示件数を取得（設定クラスが利用可能な場合）
            if (class_exists('KTP_Settings')) {
                $limit = KTP_Settings::get_work_list_range();
                error_log('[AJAX] 一般設定から表示件数を取得: ' . $limit);
            } else {
                $limit = 20; // フォールバック値
                error_log('[AJAX] KTP_Settingsクラスなし - フォールバック値を使用: ' . $limit);
            }
        } else {
            $limit = isset($_POST['limit']) ? absint($_POST['limit']) : 20;
            error_log('[AJAX] POSTパラメータから表示件数を取得: ' . $limit);
        }
        
        $search = isset($_POST['search']) ? sanitize_text_field($_POST['search']) : '';
        $category = isset($_POST['category']) ? sanitize_text_field($_POST['category']) : '';

        if ($limit > 500) {
            $limit = 500; // 一般設定の最大値に合わせる
        }

        $offset = ($page - 1) * $limit;

        error_log('[AJAX] パラメータ取得完了: page=' . $page . ', limit=' . $limit);

        try {
            error_log('[AJAX] サービスDB処理開始');
            
            // サービスDBクラスのインスタンスを取得
            if (!class_exists('KTPWP_Service_DB')) {
                $service_db_file = KTPWP_PLUGIN_DIR . 'includes/class-ktpwp-service-db.php';
                error_log('[AJAX] サービスDBファイル: ' . $service_db_file . ' (存在: ' . (file_exists($service_db_file) ? 'Yes' : 'No') . ')');
                require_once $service_db_file;
            }

            if (!class_exists('KTPWP_Service_DB')) {
                error_log('[AJAX] KTPWP_Service_DBクラスが見つかりません');
                wp_send_json_error(__('サービスDBクラスが見つかりません', 'ktpwp'));
                return;
            }

            $service_db = KTPWP_Service_DB::get_instance();
            if (!$service_db) {
                error_log('[AJAX] サービスDBインスタンスの取得に失敗');
                
                // ダミーデータで代替
                $dummy_services = array(
                    array(
                        'id' => 1,
                        'service_name' => 'テストサービス1（DBエラー時）',
                        'price' => 10000,
                        'unit' => '個',
                        'category' => 'カテゴリ1'
                    )
                );

                $response_data = array(
                    'services' => $dummy_services,
                    'pagination' => array(
                        'current_page' => $page,
                        'total_pages' => 1,
                        'total_items' => 1,
                        'items_per_page' => $limit
                    ),
                    'debug_message' => 'DBインスタンス取得失敗のためダミーデータを使用'
                );

                error_log('[AJAX] ダミーデータ送信（DBエラー時）: ' . print_r($response_data, true));
                wp_send_json_success($response_data);
                return;
            }

            error_log('[AJAX] 検索パラメータ: page=' . $page . ', limit=' . $limit . ', search=' . $search . ', category=' . $category);

            // 検索条件の配列
            $search_args = array(
                'limit' => $limit,
                'offset' => $offset,
                'order_by' => 'frequency',
                'order' => 'DESC',
                'search' => $search,
                'category' => $category
            );

            // サービス一覧を取得
            error_log('[AJAX] サービス一覧取得呼び出し');
            $services = $service_db->get_services('service', $search_args);
            error_log('[AJAX] サービス一覧取得結果: ' . print_r($services, true));
            
            $total_services = $service_db->get_services_count('service', $search_args);
            error_log('[AJAX] サービス総数: ' . $total_services);

            if ($services === null || empty($services)) {
                error_log('[AJAX] サービス一覧が空のためダミーデータに切り替え');
                
                // テスト用のダミーデータを返す
                $dummy_services = array(
                    array(
                        'id' => 1,
                        'service_name' => 'サンプルサービス1',
                        'price' => 10000,
                        'unit' => '個',
                        'category' => 'カテゴリ1'
                    ),
                    array(
                        'id' => 2,
                        'service_name' => 'サンプルサービス2', 
                        'price' => 20000,
                        'unit' => '時間',
                        'category' => 'カテゴリ2'
                    ),
                    array(
                        'id' => 3,
                        'service_name' => 'サンプルサービス3', 
                        'price' => 5000,
                        'unit' => '回',
                        'category' => 'カテゴリ1'
                    )
                );

                $services = $dummy_services;
                $total_services = count($dummy_services);
            }

            $total_pages = ceil($total_services / $limit);

            // レスポンスデータ
            $response_data = array(
                'services' => $services,
                'pagination' => array(
                    'current_page' => $page,
                    'total_pages' => $total_pages,
                    'total_items' => $total_services,
                    'items_per_page' => $limit
                )
            );

            error_log('[AJAX] サービス一覧取得成功: ' . count($services) . '件');
            wp_send_json_success($response_data);

        } catch (Exception $e) {
            error_log('[AJAX] サービス一覧取得例外エラー: ' . $e->getMessage());
            
            // エラー時はダミーデータで代替
            $dummy_services = array(
                array(
                    'id' => 1,
                    'service_name' => 'エラー時サンプル',
                    'price' => 1000,
                    'unit' => '個',
                    'category' => 'エラー対応'
                )
            );

            $response_data = array(
                'services' => $dummy_services,
                'pagination' => array(
                    'current_page' => $page,
                    'total_pages' => 1,
                    'total_items' => 1,
                    'items_per_page' => $limit
                ),
                'debug_message' => 'Exception発生: ' . $e->getMessage()
            );

            wp_send_json_success($response_data);
        }
    }

    /**
     * メール内容取得のAJAX処理
     */
    public function ajax_get_email_content() {
        try {
            // セキュリティチェック
            if (!check_ajax_referer('ktpwp_ajax_nonce', 'nonce', false)) {
                throw new Exception('セキュリティ検証に失敗しました。');
            }

            // 権限チェック
            if (!current_user_can('edit_posts') && !current_user_can('ktpwp_access')) {
                throw new Exception('権限がありません。');
            }

            $order_id = isset($_POST['order_id']) ? absint($_POST['order_id']) : 0;
            if ($order_id <= 0) {
                throw new Exception('無効な受注書IDです。');
            }

            global $wpdb;
            $table_name = $wpdb->prefix . 'ktp_order';
            $client_table = $wpdb->prefix . 'ktp_client';

            // 受注書データを取得
            $order = $wpdb->get_row($wpdb->prepare(
                "SELECT * FROM `{$table_name}` WHERE id = %d",
                $order_id
            ));

            if (!$order) {
                throw new Exception('受注書が見つかりません。');
            }

            // 顧客データを取得
            $client = null;
            if (!empty($order->client_id)) {
                $client = $wpdb->get_row($wpdb->prepare(
                    "SELECT * FROM `{$client_table}` WHERE id = %d",
                    $order->client_id
                ));
            }

            // IDで見つからない場合は会社名と担当者名で検索
            if (!$client && !empty($order->customer_name) && !empty($order->user_name)) {
                $client = $wpdb->get_row($wpdb->prepare(
                    "SELECT * FROM `{$client_table}` WHERE company_name = %s AND name = %s",
                    $order->customer_name,
                    $order->user_name
                ));
            }

            // メール送信可否の判定
            if (!$client) {
                wp_send_json_error(array(
                    'message' => 'メール送信不可（顧客データなし）',
                    'error_type' => 'no_client',
                    'error_title' => '顧客データが見つかりません',
                    'error' => 'この受注書に関連する顧客データが見つかりません。顧客管理画面で顧客を登録してください。'
                ));
                return;
            }

            if ($client->category === '対象外') {
                wp_send_json_error(array(
                    'message' => 'メール送信不可（対象外顧客）',
                    'error_type' => 'excluded_client',
                    'error_title' => 'メール送信不可',
                    'error' => 'この顧客は削除済み（対象外）のため、メール送信はできません。'
                ));
                return;
            }

            // メールアドレスの取得と検証
            $email_raw = $client->email ?? '';
            $name_raw = $client->name ?? '';

            // nameフィールドにメールアドレスが入っている場合を検出
            $name_is_email = !empty($name_raw) && filter_var($name_raw, FILTER_VALIDATE_EMAIL) !== false;
            $email_is_empty = empty(trim($email_raw));

            if ($name_is_email && $email_is_empty) {
                $email_raw = $name_raw;
            }

            $email = trim($email_raw);
            $email = str_replace(["\0", "\r", "\n", "\t"], '', $email);
            $is_valid = !empty($email) && filter_var($email, FILTER_VALIDATE_EMAIL) !== false;

            if (!$is_valid) {
                wp_send_json_error(array(
                    'message' => 'メール送信不可（メールアドレス未設定または無効）',
                    'error_type' => 'no_email',
                    'error_title' => 'メールアドレス未設定',
                    'error' => 'この顧客のメールアドレスが未設定または無効です。顧客管理画面でメールアドレスを登録してください。'
                ));
                return;
            }

            $to = sanitize_email($email);

            // 自社情報取得
            $smtp_settings = get_option('ktp_smtp_settings', array());
            $my_email = !empty($smtp_settings['email_address']) ? sanitize_email($smtp_settings['email_address']) : '';

            $my_company = '';
            if (class_exists('KTP_Settings')) {
                $my_company = KTP_Settings::get_company_info();
            }

            // 旧システムからも取得（後方互換性）
            if (empty($my_company)) {
                $setting_table = $wpdb->prefix . 'ktp_setting';
                $setting = $wpdb->get_row($wpdb->prepare(
                    "SELECT * FROM `{$setting_table}` WHERE id = %d",
                    1
                ));
                if ($setting) {
                    $my_company = sanitize_text_field(strip_tags($setting->my_company_content));
                }
            }

            if (empty($my_email) && $setting) {
                $my_email = sanitize_email($setting->email_address);
            }

            // 請求項目リストを取得
            $order_class = new Kntan_Order_Class();
            $invoice_items_from_db = $order_class->Get_Invoice_Items($order->id);
            $amount = 0;
            $invoice_list = '';

            if (!empty($invoice_items_from_db)) {
                $invoice_list = "\n";
                $max_length = 0;
                $item_lines = array();

                foreach ($invoice_items_from_db as $item) {
                    $product_name = isset($item['product_name']) ? sanitize_text_field($item['product_name']) : '';
                    $item_amount = isset($item['amount']) ? floatval($item['amount']) : 0;
                    $price = isset($item['price']) ? floatval($item['price']) : 0;
                    $quantity = isset($item['quantity']) ? floatval($item['quantity']) : 0;
                    $unit = isset($item['unit']) ? sanitize_text_field($item['unit']) : '';
                    $amount += $item_amount;

                    if (!empty(trim($product_name))) {
                        $line = $product_name . '：' . number_format($price) . '円 × ' . $quantity . $unit . ' = ' . number_format($item_amount) . "円";
                        $item_lines[] = $line;
                        $line_length = mb_strlen($line, 'UTF-8');
                        if ($line_length > $max_length) {
                            $max_length = $line_length;
                        }
                    }
                }

                foreach ($item_lines as $line) {
                    $invoice_list .= $line . "\n";
                }

                $amount_ceiled = ceil($amount);
                $total_line = "合計：" . number_format($amount_ceiled) . "円";
                $total_length = mb_strlen($total_line, 'UTF-8');

                $line_length = max($max_length, $total_length);
                if ($line_length < 30) $line_length = 30;
                if ($line_length > 80) $line_length = 80;

                $invoice_list .= str_repeat('-', $line_length) . "\n";
                $invoice_list .= $total_line;
            } else {
                $invoice_list = '（請求項目未入力）';
            }

            // 進捗ごとに件名・本文を生成
            $progress = absint($order->progress);
            $project_name = $order->project_name ? sanitize_text_field($order->project_name) : '';
            $customer_name = sanitize_text_field($order->customer_name);
            $user_name = sanitize_text_field($order->user_name);

            // 進捗に応じた帳票タイトルと件名を設定
            $document_titles = [
                1 => '見積り書',
                2 => '注文受書', 
                3 => '納品書',
                4 => '請求書',
                5 => '領収書',
                6 => '案件完了'
            ];

            $document_messages = [
                1 => "につきましてお見積りいたします。",
                2 => "につきましてご注文をお受けしました。",
                3 => "につきまして完了しました。",
                4 => "につきまして請求申し上げます。",
                5 => "につきましてお支払いを確認しました。",
                6 => "につきましては全て完了しています。"
            ];

            $document_title = isset($document_titles[$progress]) ? $document_titles[$progress] : '受注書';
            $document_message = isset($document_messages[$progress]) ? $document_messages[$progress] : '';

            // 日付フォーマット
            $order_date = date('Y年m月d日', $order->time);

            // 件名と本文の統一フォーマット
            $subject = "{$document_title}：{$project_name}";
            $body = "{$customer_name}\n{$user_name} 様\n\nお世話になります。\n\n＜{$document_title}＞ ID: {$order->id} [{$order_date}]\n「{$project_name}」{$document_message}\n\n請求項目\n{$invoice_list}\n\n--\n{$my_company}";

            wp_send_json_success(array(
                'to' => $to,
                'subject' => $subject,
                'body' => $body,
                'order_id' => $order_id,
                'project_name' => $project_name
            ));

        } catch (Exception $e) {
            error_log('KTPWP Ajax get_email_content Error: ' . $e->getMessage());
            wp_send_json_error(array(
                'message' => $e->getMessage()
            ));
        }
    }

    /**
     * メール送信のAJAX処理（ファイル添付対応）
     */
    public function ajax_send_order_email() {
        try {
            // セキュリティチェック
            if (!check_ajax_referer('ktpwp_ajax_nonce', 'nonce', false)) {
                throw new Exception('セキュリティ検証に失敗しました。');
            }

            // 権限チェック
            if (!current_user_can('edit_posts') && !current_user_can('ktpwp_access')) {
                throw new Exception('権限がありません。');
            }

            $order_id = isset($_POST['order_id']) ? absint($_POST['order_id']) : 0;
            $to = isset($_POST['to']) ? sanitize_email($_POST['to']) : '';
            $subject = isset($_POST['subject']) ? sanitize_text_field($_POST['subject']) : '';
            $body = isset($_POST['body']) ? sanitize_textarea_field($_POST['body']) : '';

            if ($order_id <= 0) {
                throw new Exception('無効な受注書IDです。');
            }

            if (empty($to) || !filter_var($to, FILTER_VALIDATE_EMAIL)) {
                throw new Exception('有効なメールアドレスが指定されていません。');
            }

            if (empty($subject) || empty($body)) {
                throw new Exception('件名と本文を入力してください。');
            }

            // 自社メールアドレスを取得
            $smtp_settings = get_option('ktp_smtp_settings', array());
            $my_email = !empty($smtp_settings['email_address']) ? sanitize_email($smtp_settings['email_address']) : '';

            // 旧システムからも取得（後方互換性）
            if (empty($my_email)) {
                global $wpdb;
                $setting_table = $wpdb->prefix . 'ktp_setting';
                $setting = $wpdb->get_row($wpdb->prepare(
                    "SELECT * FROM `{$setting_table}` WHERE id = %d",
                    1
                ));
                if ($setting) {
                    $my_email = sanitize_email($setting->email_address);
                }
            }

            // ヘッダー設定
            $headers = array();
            if ($my_email) {
                $headers[] = 'From: ' . $my_email;
            }

            // ファイル添付処理
            $attachments = array();
            $temp_files = array(); // 一時ファイルの記録（後でクリーンアップ）
            
            if (!empty($_FILES['attachments'])) {
                if (defined('WP_DEBUG') && WP_DEBUG) {
                    error_log('KTPWP Email: Processing file attachments - ' . print_r($_FILES['attachments'], true));
                }
                
                $uploaded_files = $_FILES['attachments'];
                
                // ファイルの基本設定
                $max_file_size = 10 * 1024 * 1024; // 10MB
                $max_total_size = 50 * 1024 * 1024; // 50MB
                $allowed_types = array(
                    'application/pdf',
                    'image/jpeg', 'image/jpg', 'image/png', 'image/gif',
                    'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                    'application/vnd.ms-excel', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                    'application/zip', 'application/x-rar-compressed', 'application/x-zip-compressed'
                );
                $allowed_extensions = array('.pdf', '.jpg', '.jpeg', '.png', '.gif', '.doc', '.docx', '.xls', '.xlsx', '.zip', '.rar', '.7z');
                
                $total_size = 0;
                $file_count = is_array($uploaded_files['name']) ? count($uploaded_files['name']) : 1;
                
                if (defined('WP_DEBUG') && WP_DEBUG) {
                    error_log("KTPWP Email: Processing {$file_count} files");
                }
                
                for ($i = 0; $i < $file_count; $i++) {
                    $file_name = is_array($uploaded_files['name']) ? $uploaded_files['name'][$i] : $uploaded_files['name'];
                    $file_tmp = is_array($uploaded_files['tmp_name']) ? $uploaded_files['tmp_name'][$i] : $uploaded_files['tmp_name'];
                    $file_size = is_array($uploaded_files['size']) ? $uploaded_files['size'][$i] : $uploaded_files['size'];
                    $file_type = is_array($uploaded_files['type']) ? $uploaded_files['type'][$i] : $uploaded_files['type'];
                    $file_error = is_array($uploaded_files['error']) ? $uploaded_files['error'][$i] : $uploaded_files['error'];
                    
                    if (defined('WP_DEBUG') && WP_DEBUG) {
                        error_log("KTPWP Email: Processing file {$i}: {$file_name} ({$file_size} bytes, type: {$file_type})");
                    }
                    
                    // ファイルエラーチェック
                    if ($file_error !== UPLOAD_ERR_OK) {
                        throw new Exception("ファイル「{$file_name}」のアップロードでエラーが発生しました。（エラーコード: {$file_error}）");
                    }
                    
                    // ファイルサイズチェック
                    if ($file_size > $max_file_size) {
                        throw new Exception("ファイル「{$file_name}」は10MBを超えています。");
                    }
                    
                    $total_size += $file_size;
                    if ($total_size > $max_total_size) {
                        throw new Exception('合計ファイルサイズが50MBを超えています。');
                    }
                    
                    // ファイル形式チェック
                    $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
                    $is_allowed_type = in_array($file_type, $allowed_types);
                    $is_allowed_ext = in_array('.' . $file_ext, $allowed_extensions);
                    
                    if (!$is_allowed_type && !$is_allowed_ext) {
                        throw new Exception("ファイル「{$file_name}」は対応していない形式です。");
                    }
                    
                    // ファイル名のサニタイズ
                    $safe_filename = sanitize_file_name($file_name);
                    
                    // 一時ディレクトリにファイルを保存
                    $upload_dir = wp_upload_dir();
                    $temp_dir = $upload_dir['basedir'] . '/ktp-email-temp/';
                    
                    // 一時ディレクトリが存在しない場合は作成
                    if (!file_exists($temp_dir)) {
                        wp_mkdir_p($temp_dir);
                        if (defined('WP_DEBUG') && WP_DEBUG) {
                            error_log("KTPWP Email: Created temp directory: {$temp_dir}");
                        }
                    }
                    
                    // ユニークなファイル名を生成
                    $unique_filename = uniqid() . '_' . $safe_filename;
                    $temp_file_path = $temp_dir . $unique_filename;
                    
                    // ファイルを移動
                    if (move_uploaded_file($file_tmp, $temp_file_path)) {
                        $attachments[] = $temp_file_path;
                        $temp_files[] = $temp_file_path; // クリーンアップ用
                        if (defined('WP_DEBUG') && WP_DEBUG) {
                            error_log("KTPWP Email: Saved attachment: {$temp_file_path}");
                        }
                    } else {
                        throw new Exception("ファイル「{$file_name}」の保存に失敗しました。");
                    }
                }
                
                if (defined('WP_DEBUG') && WP_DEBUG) {
                    error_log("KTPWP Email: Successfully processed " . count($attachments) . " attachments, total size: " . round($total_size / 1024 / 1024, 2) . "MB");
                }
            }

            // メール送信
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log("KTPWP Email: Sending email to {$to} with " . count($attachments) . " attachments");
            }
            
            $sent = wp_mail($to, $subject, $body, $headers, $attachments);

            // 一時ファイルのクリーンアップ
            foreach ($temp_files as $temp_file) {
                if (file_exists($temp_file)) {
                    unlink($temp_file);
                    if (defined('WP_DEBUG') && WP_DEBUG) {
                        error_log("KTPWP Email: Cleaned up temp file: " . basename($temp_file));
                    }
                }
            }

            if ($sent) {
                if (defined('WP_DEBUG') && WP_DEBUG) {
                    error_log("KTPWP Email: Successfully sent email to {$to} with " . count($attachments) . " attachments");
                }
                wp_send_json_success(array(
                    'message' => 'メールを送信しました。',
                    'to' => $to,
                    'attachment_count' => count($attachments)
                ));
            } else {
                throw new Exception('メール送信に失敗しました。サーバー設定を確認してください。');
            }

        } catch (Exception $e) {
            // エラー時も一時ファイルをクリーンアップ
            if (!empty($temp_files)) {
                foreach ($temp_files as $temp_file) {
                    if (file_exists($temp_file)) {
                        unlink($temp_file);
                        if (defined('WP_DEBUG') && WP_DEBUG) {
                            error_log("KTPWP Email Error: Cleaned up temp file on error: " . basename($temp_file));
                        }
                    }
                }
            }
            
            error_log('KTPWP Ajax send_order_email Error: ' . $e->getMessage());
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('KTPWP Ajax send_order_email Error Stack Trace: ' . $e->getTraceAsString());
            }
            wp_send_json_error(array(
                'message' => $e->getMessage()
            ));
        }
    }

    /**
     * Ajax: 最新スタッフチャットメッセージ取得
     */
    public function ajax_get_latest_staff_chat() {
        try {
            // 出力バッファをクリア
            while (ob_get_level()) {
                ob_end_clean();
            }
            ob_start();

            // フック干渉を無効化
            $this->disable_potentially_interfering_hooks();

            // ログインチェック
            if (!is_user_logged_in()) {
                $this->send_clean_json_response(array(
                    'success' => false,
                    'data' => __('ログインが必要です', 'ktpwp')
                ));
                return;
            }

            // Nonce検証（_ajax_nonceパラメータで送信される）
            $nonce = $_POST['_ajax_nonce'] ?? '';
            if (!wp_verify_nonce($nonce, $this->nonce_names['staff_chat'])) {
                $this->log_ajax_error('Staff chat get messages nonce verification failed', array(
                    'received_nonce' => $nonce,
                    'expected_action' => $this->nonce_names['staff_chat']
                ));
                $this->send_clean_json_response(array(
                    'success' => false,
                    'data' => __('セキュリティトークンが無効です', 'ktpwp')
                ));
                return;
            }

            // パラメータの取得とサニタイズ
            $order_id = $this->sanitize_ajax_input('order_id', 'int');
            $last_time = $this->sanitize_ajax_input('last_time', 'text');

            if (empty($order_id)) {
                $this->send_clean_json_response(array(
                    'success' => false,
                    'data' => __('注文IDが必要です', 'ktpwp')
                ));
                return;
            }

            // 権限チェック
            if (!current_user_can('read')) {
                $this->send_clean_json_response(array(
                    'success' => false,
                    'data' => __('権限がありません', 'ktpwp')
                ));
                return;
            }

            // スタッフチャットクラスのインスタンス化
            if (!class_exists('KTPWP_Staff_Chat')) {
                require_once KTPWP_PLUGIN_DIR . 'includes/class-ktpwp-staff-chat.php';
            }

            $staff_chat = KTPWP_Staff_Chat::get_instance();

            // 最新メッセージを取得
            $messages = $staff_chat->get_messages_after($order_id, $last_time);

            // バッファをクリーン
            $output = ob_get_clean();
            if (!empty($output)) {
                error_log('KTPWP Ajax get_latest_staff_chat: Unexpected output cleaned: ' . $output);
            }

            $this->send_clean_json_response(array(
                'success' => true,
                'data' => $messages
            ));

        } catch (Exception $e) {
            // バッファをクリーン
            while (ob_get_level()) {
                ob_end_clean();
            }

            $this->log_ajax_error('Exception during get latest staff chat', array(
                'message' => $e->getMessage(),
                'order_id' => $_POST['order_id'] ?? 'unknown',
            ));

            $this->send_clean_json_response(array(
                'success' => false,
                'data' => __('メッセージの取得中にエラーが発生しました', 'ktpwp')
            ));
        }
    }

    /**
     * Ajax: スタッフチャットメッセージ送信
     */
    public function ajax_send_staff_chat_message() {
        // WordPress出力バッファ完全クリア
        while (ob_get_level()) {
            ob_end_clean();
        }

        // 新しいバッファを開始（レスポンス汚染防止）
        ob_start();

        // エラー出力を抑制（JSON汚染防止）
        if (!defined('WP_DEBUG') || !WP_DEBUG) {
            error_reporting(0);
            ini_set('display_errors', 0);
        }

        // 汚染される可能性のあるWordPressフックを一時的に無効化
        $this->disable_potentially_interfering_hooks();

        try {
            // ログインチェック
            if (!is_user_logged_in()) {
                wp_send_json_error(__('ログインが必要です', 'ktpwp'));
                return;
            }

            // Nonce検証（_ajax_nonceパラメータで送信される）
            $nonce = $_POST['_ajax_nonce'] ?? '';
            $nonce_valid = wp_verify_nonce($nonce, $this->nonce_names['staff_chat']);
            // nonceが不正かつ権限もない場合のみエラー
            if (!$nonce_valid && !current_user_can('edit_posts') && !current_user_can('ktpwp_access')) {
                wp_send_json_error(__('権限がありません（nonce不正）', 'ktpwp'));
                return;
            }

            // パラメータの取得とサニタイズ
            $order_id = $this->sanitize_ajax_input('order_id', 'int');
            $message = $this->sanitize_ajax_input('message', 'text');

            if (empty($order_id) || empty($message)) {
                if (defined('WP_DEBUG') && WP_DEBUG) {
                    error_log('KTPWP StaffChat: order_idまたはmessageが空: order_id=' . print_r($order_id, true) . ' message=' . print_r($message, true));
                }
                wp_send_json_error(__('注文IDとメッセージが必要です', 'ktpwp'));
                return;
            }

            // 権限チェック
            if (!current_user_can('edit_posts') && !current_user_can('ktpwp_access')) {
                if (defined('WP_DEBUG') && WP_DEBUG) {
                    error_log('KTPWP StaffChat: 権限チェック失敗 user_id=' . get_current_user_id());
                }
                wp_send_json_error(__('権限がありません', 'ktpwp'));
                return;
            }

            // スタッフチャットクラスのインスタンス化
            if (!class_exists('KTPWP_Staff_Chat')) {
                require_once KTPWP_PLUGIN_DIR . 'includes/class-ktpwp-staff-chat.php';
            }

            $staff_chat = KTPWP_Staff_Chat::get_instance();

            // メッセージを送信
            $result = $staff_chat->add_message($order_id, $message);

            if ($result) {
                // バッファをクリーンにしてからJSONを送信
                $output = ob_get_clean();
                if (!empty($output)) {
                    // デバッグ用：予期しない出力があればログに記録
                    if (defined('WP_DEBUG') && WP_DEBUG) {
                        error_log('KTPWP Ajax: Unexpected output cleaned: ' . $output);
                    }
                }

                // 最終的なクリーンアップ
                $this->send_clean_json_response(array(
                    'success' => true,
                    'data' => array(
                        'message' => __('メッセージを送信しました', 'ktpwp'),
                    )
                ));
            } else {
                if (defined('WP_DEBUG') && WP_DEBUG) {
                    error_log('KTPWP StaffChat: add_message失敗 user_id=' . get_current_user_id() . ' order_id=' . print_r($order_id, true) . ' message=' . print_r($message, true));
                }
                ob_end_clean();
                $this->send_clean_json_response(array(
                    'success' => false,
                    'data' => __('メッセージの送信に失敗しました', 'ktpwp')
                ));
            }

        } catch (Exception $e) {
            ob_end_clean();
            $this->log_ajax_error('Exception during send staff chat message', array(
                'message' => $e->getMessage(),
                'order_id' => $_POST['order_id'] ?? 'unknown',
            ));
            $this->send_clean_json_response(array(
                'success' => false,
                'data' => __('メッセージの送信中にエラーが発生しました', 'ktpwp')
            ));
        }
    }

    /**
     * 最新の受注書プレビューデータを取得
     *
     * @since 1.0.0
     * @return void
     */
    public function get_order_preview() {
        try {
            // nonce検証
            if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'ktp_ajax_nonce')) {
                throw new Exception('セキュリティチェックに失敗しました。');
            }

            // 権限チェック
            if (!current_user_can('edit_posts') && !current_user_can('ktpwp_access')) {
                throw new Exception('この操作を実行する権限がありません。');
            }

            // パラメータ取得
            $order_id = isset($_POST['order_id']) ? absint($_POST['order_id']) : 0;
            if ($order_id <= 0) {
                throw new Exception('無効な受注書IDです。');
            }

            global $wpdb;
            $table_name = $wpdb->prefix . 'ktp_order';

            // 受注書データを取得
            $order = $wpdb->get_row($wpdb->prepare(
                "SELECT * FROM `{$table_name}` WHERE id = %d",
                $order_id
            ));

            if (!$order) {
                throw new Exception('受注書が見つかりません。');
            }

           

            // Order クラスのインスタンスを作成してプレビューHTML生成を利用
            if (!class_exists('Kntan_Order_Class')) {
                require_once MY_PLUGIN_PATH . 'includes/class-tab-order.php';
            }

            $order_class = new Kntan_Order_Class();
            
            // パブリックメソッドを使用して最新のプレビューHTMLを生成
            $preview_html = $order_class->Generate_Order_Preview_HTML_Public($order);
            
            // 進捗状況に応じた帳票タイトルを取得
            $document_info = $order_class->Get_Document_Info_By_Progress_Public($order->progress);

            wp_send_json_success(array(
                'preview_html' => $preview_html,
                'order_id' => $order_id,
                'progress' => $order->progress,
                'document_title' => $document_info['title'],
                'timestamp' => current_time('timestamp')
            ));

        } catch (Exception $e) {
            error_log('KTPWP Ajax get_order_preview Error: ' . $e->getMessage());
            wp_send_json_error(array(
                'message' => $e->getMessage()
            ));
        }
    }

    /**
     * WordPress 互換性を保持したクリーンな JSON レスポンスを送信
     * wp_send_json_* 関数の改良版（WordPress コア機能との競合を避ける）
     */
    private function send_clean_json_response($data) {
        // WordPress 標準の JSON レスポンス関数が利用可能で、
        // 既に出力が汚染されていない場合は、標準関数を使用
        if (function_exists('wp_send_json') && ob_get_length() === false) {
            wp_send_json($data);
            return;
        }

        // 出力バッファが汚染されている場合のみクリーニングを実行
        $buffer_content = '';
        $buffer_level = ob_get_level();

        if ($buffer_level > 0) {
            $buffer_content = ob_get_contents();
            // 意味のある出力があるかチェック（空白や改行のみの場合は無視）
            $meaningful_content = trim($buffer_content);

            if (!empty($meaningful_content)) {
                // 汚染されている場合のみバッファをクリア
                while (ob_get_level()) {
                    ob_end_clean();
                }
            }
        }

        // HTTPヘッダーが送信されていない場合のみ設定
        if (!headers_sent()) {
            // WordPress 標準に準拠したヘッダー設定
            status_header(200);
            header('Content-Type: application/json; charset=' . get_option('blog_charset'));

            // キャッシュ制御
            nocache_headers();

            // セキュリティヘッダー
            if (is_ssl()) {
                header('Vary: Accept-Encoding');
            }
        }

        // JSONエンコード（WordPress 標準オプション使用）
        $json = wp_json_encode($data);

        if ($json === false) {
            // JSON エンコードに失敗した場合のフォールバック
            $fallback_data = array(
                'success' => false,
                'data' => null,
                'message' => 'JSON encoding failed: ' . json_last_error_msg()
            );
            $json = wp_json_encode($fallback_data);
        }

        // JSONを出力
        echo $json;

        // WordPress 標準の終了処理を使用（shutdown フックを実行）
        wp_die('', '', array('response' => 200));
    }

    /**
     * 出力に干渉する可能性のあるWordPressフックを一時的に無効化
     */
    private function disable_potentially_interfering_hooks() {
        // デバッグバーや他のプラグインの出力を防ぐ（安全なフックのみ削除）
        remove_all_actions('wp_footer');
        remove_all_actions('admin_footer');
        remove_all_actions('in_admin_footer');
        remove_all_actions('admin_print_footer_scripts');
        remove_all_actions('wp_print_footer_scripts');

        // WordPress コアの重要な処理を破壊しないよう、shutdown フックは保持

        // 他のプラグインのAJAX干渉を防ぐ（安全な範囲で）
        if (class_exists('WP_Debug_Bar')) {
            remove_all_actions('wp_before_admin_bar_render');
            remove_all_actions('wp_after_admin_bar_render');
        }

        // より安全な方法：特定のプラグインによる出力のみを無効化
        $this->disable_specific_plugin_outputs();
    }

    /**
     * 特定のプラグインによる出力のみを無効化（WordPress コア機能は保持）
     */
    private function disable_specific_plugin_outputs() {
        global $wp_filter;

        // 問題を引き起こす可能性のある特定のプラグインフックのみを対象とする
        $problematic_hooks = array(
            'wp_footer' => array('debug_bar', 'query_monitor', 'wp_debug_bar'),
            'admin_footer' => array('debug_bar', 'query_monitor'),
            'shutdown' => array('debug_bar_output', 'query_monitor_output')
        );

        foreach ($problematic_hooks as $hook_name => $plugin_patterns) {
            if (isset($wp_filter[$hook_name])) {
                foreach ($wp_filter[$hook_name]->callbacks as $priority => $callbacks) {
                    foreach ($callbacks as $callback_id => $callback_data) {
                        // コールバック関数名または関数オブジェクトをチェック
                        $function_name = '';
                        if (is_string($callback_data['function'])) {
                            $function_name = $callback_data['function'];
                        } elseif (is_array($callback_data['function']) && count($callback_data['function']) === 2) {
                            $class_name = is_object($callback_data['function'][0]) ?
                                get_class($callback_data['function'][0]) : $callback_data['function'][0];
                            $function_name = $class_name . '::' . $callback_data['function'][1];
                        }

                        // 問題のあるパターンに一致する場合のみ削除
                        foreach ($plugin_patterns as $pattern) {
                            if (stripos($function_name, $pattern) !== false) {
                                remove_action($hook_name, $callback_data['function'], $priority);
                                break;
                            }
                        }
                    }
                }
            }
        }
    }

    /**
     * Ajax入力データのサニタイズ処理
     *
     * @param string $key 取得するPOSTキー
     * @param string $type サニタイズのタイプ
     * @param mixed $default デフォルト値
     * @return mixed サニタイズされた値
     */
    private function sanitize_ajax_input($key, $type = 'text', $default = '') {
        if (!isset($_POST[$key])) {
            return $default;
        }

        $value = $_POST[$key];

        switch ($type) {
            case 'int':
                return intval($value);
            case 'float':
                return floatval($value);
            case 'email':
                return sanitize_email($value);
            case 'url':
                return esc_url_raw($value);
            case 'textarea':
                return sanitize_textarea_field($value);
            case 'html':
                return wp_kses_post($value);
            case 'key':
                return sanitize_key($value);
            case 'title':
                return sanitize_title($value);
            case 'text':
            default:
                return sanitize_text_field($value);
        }
    }
}
