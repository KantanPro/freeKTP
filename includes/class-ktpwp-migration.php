<?php
/**
 * KantanPro マイグレーション管理クラス
 * 
 * @package KantanPro
 * @since 1.2.2
 */

// セキュリティ: 直接アクセスを防止
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * KTPWP_Migration クラス
 * 
 * データベースマイグレーションを管理するクラス
 */
class KTPWP_Migration {
    
    /**
     * インスタンス
     * 
     * @var KTPWP_Migration
     */
    private static $instance = null;
    
    /**
     * データベーススキーマ定義
     * 
     * @var array
     */
    private $schema_definitions = array();
    
    /**
     * シングルトンインスタンスを取得
     * 
     * @return KTPWP_Migration
     */
    public static function get_instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * コンストラクタ
     */
    private function __construct() {
        add_action( 'admin_menu', array( $this, 'add_migration_menu' ) );
        add_action( 'admin_notices', array( $this, 'show_migration_notices' ) );
        
        // スキーマ定義を初期化
        $this->init_schema_definitions();
    }
    
    /**
     * スキーマ定義を初期化
     */
    private function init_schema_definitions() {
        // ktp_orderテーブルのスキーマ定義
        $this->schema_definitions['ktp_order'] = array(
            'columns' => array(
                'id' => array(
                    'type' => 'mediumint(9)',
                    'null' => false,
                    'key' => 'PRIMARY',
                    'auto_increment' => true
                ),
                'time' => array(
                    'type' => 'bigint(11)',
                    'null' => false,
                    'default' => '0'
                ),
                'client_id' => array(
                    'type' => 'mediumint(9)',
                    'null' => true,
                    'default' => null
                ),
                'company_name' => array(
                    'type' => 'varchar(255)',
                    'null' => true,
                    'default' => null,
                    'collation' => 'utf8mb4_unicode_520_ci'
                ),
                'customer_name' => array(
                    'type' => 'varchar(100)',
                    'null' => false,
                    'collation' => 'utf8mb4_unicode_520_ci'
                ),
                'user_name' => array(
                    'type' => 'tinytext',
                    'null' => true,
                    'default' => null,
                    'collation' => 'utf8mb4_unicode_520_ci'
                ),
                'project_name' => array(
                    'type' => 'varchar(255)',
                    'null' => true,
                    'default' => null,
                    'collation' => 'utf8mb4_unicode_520_ci'
                ),
                'progress' => array(
                    'type' => 'tinyint(1)',
                    'null' => false,
                    'default' => '1'
                ),
                'invoice_items' => array(
                    'type' => 'text',
                    'null' => true,
                    'default' => null,
                    'collation' => 'utf8mb4_unicode_520_ci'
                ),
                'cost_items' => array(
                    'type' => 'text',
                    'null' => true,
                    'default' => null,
                    'collation' => 'utf8mb4_unicode_520_ci'
                ),
                'memo' => array(
                    'type' => 'text',
                    'null' => true,
                    'default' => null,
                    'collation' => 'utf8mb4_unicode_520_ci'
                ),
                'search_field' => array(
                    'type' => 'text',
                    'null' => true,
                    'default' => null,
                    'collation' => 'utf8mb4_unicode_520_ci'
                ),
                'created_at' => array(
                    'type' => 'datetime',
                    'null' => true,
                    'default' => null
                ),
                'completion_date' => array(
                    'type' => 'date',
                    'null' => true,
                    'default' => null
                ),
                'updated_at' => array(
                    'type' => 'datetime',
                    'null' => true,
                    'default' => null
                ),
                'desired_delivery_date' => array(
                    'type' => 'date',
                    'null' => true,
                    'default' => null
                ),
                'expected_delivery_date' => array(
                    'type' => 'date',
                    'null' => true,
                    'default' => null
                )
            ),
            'indexes' => array(
                'PRIMARY' => array('id'),
                'client_id' => array('client_id')
            ),
            'engine' => 'InnoDB',
            'charset' => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_520_ci'
        );
        
        // 他のテーブルのスキーマ定義もここに追加
        // 例: ktp_client, ktp_service など
    }
    
    /**
     * 管理メニューにマイグレーション項目を追加
     */
    public function add_migration_menu() {
        // 管理者権限のみアクセス可能
        if ( ! current_user_can( 'manage_options' ) ) {
            return;
        }
        
        add_submenu_page(
            'tools.php', // 親メニュー
            'KantanPro マイグレーション', // ページタイトル
            'KTP マイグレーション', // メニュータイトル
            'manage_options', // 権限
            'ktpwp-migration', // メニュースラッグ
            array( $this, 'migration_page' ) // コールバック関数
        );
    }
    
    /**
     * マイグレーションページの表示
     */
    public function migration_page() {
        // 権限チェック
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( __( 'このページにアクセスする権限がありません。', 'ktpwp' ) );
        }
        
        ?>
        <div class="wrap">
            <h1><?php _e( 'KantanPro データベースマイグレーション', 'ktpwp' ); ?></h1>
            
            <div class="notice notice-info">
                <p><?php _e( 'このページでは、KantanProプラグインのデータベーステーブル構造を確認できます。', 'ktpwp' ); ?></p>
                <p><strong><?php _e( '自動マイグレーション:', 'ktpwp' ); ?></strong> <?php _e( 'プラグインの有効化時とアップデート時に自動的にマイグレーションが実行されます。', 'ktpwp' ); ?></p>
            </div>
            
            <div class="notice notice-warning">
                <p><strong><?php _e( '重要:', 'ktpwp' ); ?></strong> <?php _e( 'マイグレーションは自動実行のみです。手動での実行はできません。', 'ktpwp' ); ?></p>
            </div>
            
            <h2><?php _e( 'データベーススキーマ状態', 'ktpwp' ); ?></h2>
            <?php $this->display_schema_status(); ?>
            
            <h2><?php _e( 'マイグレーションログ', 'ktpwp' ); ?></h2>
            <?php $this->display_migration_log(); ?>
        </div>
        <?php
    }
    
    /**
     * スキーマ状態を表示
     */
    private function display_schema_status() {
        foreach ( $this->schema_definitions as $table_name => $schema ) {
            $actual_table_name = $this->detect_table_name( $table_name );
            
            if ( ! $actual_table_name ) {
                echo '<div class="notice notice-error">';
                echo '<p>' . sprintf( __( 'テーブル "%s" が見つかりませんでした。', 'ktpwp' ), $table_name ) . '</p>';
                echo '</div>';
                continue;
            }
            
            echo '<h3>' . sprintf( __( 'テーブル: %s', 'ktpwp' ), esc_html( $actual_table_name ) ) . '</h3>';
            
            $current_columns = $this->get_table_structure( $actual_table_name );
            if ( ! $current_columns ) {
                echo '<p>' . __( 'テーブル構造の取得に失敗しました。', 'ktpwp' ) . '</p>';
                continue;
            }
            
            $missing_columns = $this->get_missing_columns_by_schema( $current_columns, $schema );
            
            if ( empty( $missing_columns ) ) {
                echo '<p style="color: green;">✓ ' . __( 'すべてのカラムが存在しています', 'ktpwp' ) . '</p>';
            } else {
                echo '<p style="color: red;">⚠ ' . sprintf( __( '%d個のカラムが不足しています', 'ktpwp' ), count( $missing_columns ) ) . '</p>';
                echo '<ul style="color: red;">';
                foreach ( $missing_columns as $column_name => $column_info ) {
                    echo '<li><code>' . esc_html( $column_name ) . '</code> - ' . esc_html( $column_info['definition'] ) . '</li>';
                }
                echo '</ul>';
                echo '<p><em>' . __( 'これらのカラムは、プラグインの有効化時またはアップデート時に自動的に追加されます。', 'ktpwp' ) . '</em></p>';
            }
        }
    }
    
    /**
     * マイグレーション通知の表示
     */
    public function show_migration_notices() {
        // 自動マイグレーションの結果通知のみ表示
        $auto_migration_success = get_transient( 'ktpwp_auto_migration_success' );
        if ( $auto_migration_success ) {
            delete_transient( 'ktpwp_auto_migration_success' );
            echo '<div class="notice notice-success is-dismissible"><p>' . esc_html( $auto_migration_success ) . '</p></div>';
        }
        
        $auto_migration_error = get_transient( 'ktpwp_auto_migration_error' );
        if ( $auto_migration_error ) {
            delete_transient( 'ktpwp_auto_migration_error' );
            echo '<div class="notice notice-error is-dismissible"><p>' . esc_html( $auto_migration_error ) . '</p></div>';
        }
    }
    
    /**
     * テーブル名を動的に検出
     * 
     * @param string $base_table_name
     * @return string|false
     */
    private function detect_table_name( $base_table_name ) {
        global $wpdb;
        
        // 一般的なテーブル接頭辞のパターン
        $possible_prefixes = array( 'wp_', 'to_', 'xx_', 'ktp_', 'kantan_' );
        
        // 現在のWordPressのテーブル接頭辞を取得
        $wp_prefix = $wpdb->prefix;
        if ( ! in_array( $wp_prefix, $possible_prefixes ) ) {
            $possible_prefixes[] = $wp_prefix;
        }
        
        // 各接頭辞でテーブルが存在するかチェック
        foreach ( $possible_prefixes as $prefix ) {
            $table_name = $prefix . $base_table_name;
            $result = $wpdb->get_var( $wpdb->prepare( "SHOW TABLES LIKE %s", $table_name ) );
            
            if ( $result ) {
                return $table_name;
            }
        }
        
        // データベース内のすべてのテーブルを検索
        $all_tables = $wpdb->get_results( "SHOW TABLES", ARRAY_N );
        
        foreach ( $all_tables as $table ) {
            $table_name = $table[0];
            if ( strpos( $table_name, $base_table_name ) !== false ) {
                return $table_name;
            }
        }
        
        return false;
    }
    
    /**
     * テーブル構造を取得
     * 
     * @param string $table_name
     * @return array|false
     */
    private function get_table_structure( $table_name ) {
        global $wpdb;
        
        return $wpdb->get_results( "DESCRIBE {$table_name}" );
    }
    
    /**
     * スキーマ定義に基づいて不足しているカラムを取得
     * 
     * @param array $current_columns
     * @param array $schema
     * @return array
     */
    private function get_missing_columns_by_schema( $current_columns, $schema ) {
        $existing_columns = array_column( $current_columns, 'Field' );
        $missing_columns = array();
        
        foreach ( $schema['columns'] as $column_name => $column_def ) {
            if ( ! in_array( $column_name, $existing_columns ) ) {
                $definition = $this->build_column_definition( $column_name, $column_def );
                $missing_columns[ $column_name ] = array(
                    'definition' => $definition,
                    'schema' => $column_def
                );
            }
        }
        
        return $missing_columns;
    }
    
    /**
     * カラム定義を構築
     * 
     * @param string $column_name
     * @param array $column_def
     * @return string
     */
    private function build_column_definition( $column_name, $column_def ) {
        $definition = $column_name . ' ' . $column_def['type'];
        
        if ( isset( $column_def['collation'] ) ) {
            $definition .= ' COLLATE ' . $column_def['collation'];
        }
        
        if ( isset( $column_def['null'] ) && $column_def['null'] === false ) {
            $definition .= ' NOT NULL';
        } else {
            $definition .= ' NULL';
        }
        
        if ( isset( $column_def['default'] ) ) {
            if ( $column_def['default'] === null ) {
                $definition .= ' DEFAULT NULL';
            } else {
                $definition .= " DEFAULT '{$column_def['default']}'";
            }
        }
        
        if ( isset( $column_def['auto_increment'] ) && $column_def['auto_increment'] ) {
            $definition .= ' AUTO_INCREMENT';
        }
        
        return $definition;
    }
    
    /**
     * 自動マイグレーションを実行（アクティベーション・アップデート時）
     * 
     * @param string $trigger トリガー（'activation' または 'update'）
     * @return array
     */
    public function run_auto_migration($trigger = 'activation') {
        global $wpdb;
        
        // ログファイルに記録
        $this->log_migration_attempt($trigger);
        
        $total_success_count = 0;
        $total_error_count = 0;
        $errors = array();
        
        foreach ( $this->schema_definitions as $table_name => $schema ) {
            $actual_table_name = $this->detect_table_name( $table_name );
            
            if ( ! $actual_table_name ) {
                $errors[] = sprintf( __( 'テーブル "%s" が見つかりませんでした。', 'ktpwp' ), $table_name );
                $total_error_count++;
                continue;
            }
            
            $current_columns = $this->get_table_structure( $actual_table_name );
            if ( ! $current_columns ) {
                $errors[] = sprintf( __( 'テーブル "%s" の構造取得に失敗しました。', 'ktpwp' ), $actual_table_name );
                $total_error_count++;
                continue;
            }
            
            $missing_columns = $this->get_missing_columns_by_schema( $current_columns, $schema );
            
            if ( empty( $missing_columns ) ) {
                continue; // このテーブルには追加が必要なカラムがない
            }
            
            $success_count = 0;
            $error_count = 0;
            
            foreach ( $missing_columns as $column_name => $column_info ) {
                $sql = "ALTER TABLE {$actual_table_name} ADD COLUMN {$column_info['definition']}";
                
                $result = $wpdb->query( $sql );
                
                if ( $result !== false ) {
                    $success_count++;
                    $total_success_count++;
                } else {
                    $error_count++;
                    $total_error_count++;
                    $errors[] = sprintf( __( 'テーブル "%s" のカラム "%s" の追加に失敗: %s', 'ktpwp' ), $actual_table_name, $column_name, $wpdb->last_error );
                }
            }
        }
        
        if ( $total_error_count === 0 ) {
            $message = sprintf( __( '自動マイグレーションが正常に完了しました。%d個のカラムを追加しました。', 'ktpwp' ), $total_success_count );
            $this->log_migration_result($trigger, true, $message);
            
            // 自動マイグレーション成功通知を設定
            set_transient( 'ktpwp_auto_migration_success', $message, 60 );
            
            return array(
                'success' => true,
                'message' => $message
            );
        } else {
            $message = sprintf( __( '自動マイグレーション中にエラーが発生しました。成功: %d, 失敗: %d', 'ktpwp' ), $total_success_count, $total_error_count );
            if ( ! empty( $errors ) ) {
                $message .= "\n" . implode( "\n", $errors );
            }
            
            $this->log_migration_result($trigger, false, $message);
            
            // 自動マイグレーションエラー通知を設定
            set_transient( 'ktpwp_auto_migration_error', $message, 60 );
            
            return array(
                'success' => false,
                'message' => $message
            );
        }
    }
    
    /**
     * マイグレーション試行をログに記録
     * 
     * @param string $trigger
     */
    private function log_migration_attempt($trigger) {
        $log_entry = sprintf(
            "[%s] マイグレーション試行 - トリガー: %s, プラグインバージョン: %s\n",
            current_time('Y-m-d H:i:s'),
            $trigger,
            KANTANPRO_PLUGIN_VERSION
        );
        
        error_log($log_entry, 3, WP_CONTENT_DIR . '/ktpwp-migration.log');
    }
    
    /**
     * マイグレーション結果をログに記録
     * 
     * @param string $trigger
     * @param bool $success
     * @param string $message
     */
    private function log_migration_result($trigger, $success, $message) {
        $status = $success ? 'SUCCESS' : 'ERROR';
        $log_entry = sprintf(
            "[%s] マイグレーション結果 - トリガー: %s, ステータス: %s, メッセージ: %s\n",
            current_time('Y-m-d H:i:s'),
            $trigger,
            $status,
            $message
        );
        
        error_log($log_entry, 3, WP_CONTENT_DIR . '/ktpwp-migration.log');
    }
    
    /**
     * マイグレーションログを表示
     */
    private function display_migration_log() {
        $log_file = WP_CONTENT_DIR . '/ktpwp-migration.log';
        
        if ( ! file_exists( $log_file ) ) {
            echo '<p>' . __( 'マイグレーションログファイルが見つかりません。', 'ktpwp' ) . '</p>';
            return;
        }
        
        $log_content = file_get_contents( $log_file );
        if ( empty( $log_content ) ) {
            echo '<p>' . __( 'マイグレーションログは空です。', 'ktpwp' ) . '</p>';
            return;
        }
        
        // 最新の10行を表示
        $lines = explode( "\n", $log_content );
        $recent_lines = array_slice( array_filter( $lines ), -10 );
        
        echo '<div style="background: #f9f9f9; padding: 10px; border: 1px solid #ddd; max-height: 300px; overflow-y: auto;">';
        echo '<h4>' . __( '最新のマイグレーションログ（最新10行）', 'ktpwp' ) . '</h4>';
        echo '<pre style="margin: 0; font-size: 12px;">';
        foreach ( $recent_lines as $line ) {
            echo esc_html( $line ) . "\n";
        }
        echo '</pre>';
        echo '</div>';
        
        echo '<p><small>' . __( '完全なログファイル:', 'ktpwp' ) . ' <code>' . esc_html( $log_file ) . '</code></small></p>';
    }
    
    /**
     * 新しいテーブルスキーマを追加（開発者用）
     * 
     * @param string $table_name
     * @param array $schema
     */
    public function add_schema_definition($table_name, $schema) {
        $this->schema_definitions[$table_name] = $schema;
    }
}

// クラスの初期化
add_action( 'plugins_loaded', array( 'KTPWP_Migration', 'get_instance' ) ); 