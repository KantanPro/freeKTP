<?php
/**
 * KTPWP URL生成クラス
 * 
 * サブディレクトリに依存しない統一されたURL生成を提供します。
 * 
 * @package KTPWP
 * @since 1.1.15
 */

// セキュリティ: 直接アクセスを防止
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * KTPWP URL生成クラス
 */
class KTPWP_URL_Generator {
    
    /**
     * 現在のページのベースURLを取得（サブディレクトリ対応）
     * 
     * @return string クリーンなベースURL
     */
    public static function get_current_base_url() {
        // ドメインルートのURLを取得（サブディレクトリを無視）
        $home_url = home_url();
        
        // 現在のページのパスを取得
        $current_path = $_SERVER['REQUEST_URI'] ?? '';
        
        // パスからKTPWPパラメータを除去
        $params_to_remove = array(
            'tab_name',
            'from_client',
            'customer_name',
            'user_name',
            'client_id',
            'order_id',
            'delete_order',
            'data_id',
            'view_mode',
            'query_post',
            'page_start',
            'page_stage',
            'message',
            'search_query',
            'multiple_results',
            'no_results',
            'flg',
            'sort_by',
            'sort_order',
            'order_sort_by',
            'order_sort_order',
            'chat_open',
            'message_sent',
        );
        
        // パスからクエリパラメータを除去
        $path_without_query = strtok($current_path, '?');
        
        // ドメインルートのURLを返す（サブディレクトリを無視）
        return $home_url;
    }
    
    /**
     * タブURLを生成
     * 
     * @param string $tab_name タブ名
     * @return string タブURL
     */
    public static function get_tab_url( $tab_name ) {
        $base_url = self::get_current_base_url();
        return add_query_arg( 'tab_name', $tab_name, $base_url );
    }
    
    /**
     * データ詳細URLを生成
     * 
     * @param string $tab_name タブ名
     * @param int $data_id データID
     * @param array $additional_params 追加パラメータ
     * @return string 詳細URL
     */
    public static function get_detail_url( $tab_name, $data_id, $additional_params = array() ) {
        $base_url = self::get_current_base_url();
        $params = array_merge( array(
            'tab_name' => $tab_name,
            'data_id' => $data_id,
        ), $additional_params );
        
        return add_query_arg( $params, $base_url );
    }
    
    /**
     * ページネーション付きURLを生成
     * 
     * @param string $tab_name タブ名
     * @param int $page_start 開始ページ
     * @param int $page_stage ページ段階
     * @param array $additional_params 追加パラメータ
     * @return string ページネーションURL
     */
    public static function get_pagination_url( $tab_name, $page_start, $page_stage, $additional_params = array() ) {
        $base_url = self::get_current_base_url();
        $params = array_merge( array(
            'tab_name' => $tab_name,
            'page_start' => $page_start,
            'page_stage' => $page_stage,
        ), $additional_params );
        
        return add_query_arg( $params, $base_url );
    }
    
    /**
     * ソート付きURLを生成
     * 
     * @param string $tab_name タブ名
     * @param string $sort_by ソート項目
     * @param string $sort_order ソート順
     * @param array $additional_params 追加パラメータ
     * @return string ソートURL
     */
    public static function get_sort_url( $tab_name, $sort_by, $sort_order, $additional_params = array() ) {
        $base_url = self::get_current_base_url();
        $params = array_merge( array(
            'tab_name' => $tab_name,
            'sort_by' => $sort_by,
            'sort_order' => $sort_order,
        ), $additional_params );
        
        return add_query_arg( $params, $base_url );
    }
    
    /**
     * 現在のページIDを含むベースURLを取得
     * 
     * @return string ページID付きベースURL
     */
    public static function get_current_page_base_url() {
        // ドメインルートのURLを取得（サブディレクトリを無視）
        $base_url = home_url();
        $current_page_id = get_queried_object_id();
        
        if ( $current_page_id ) {
            $base_url = add_query_arg( array( 'page_id' => $current_page_id ), $base_url );
        }
        
        return $base_url;
    }
} 