<?php
/**
 * データベース構造確認と修正SQL生成
 *
 * @package KTPWP
 * @since 1.0.0
 */

// WordPress環境を読み込み
require_once('../../../wp-load.php');

echo "<h1>データベース構造確認と修正SQL生成</h1>";

global $wpdb;

$table_name = $wpdb->prefix . 'ktp_order';

echo "<h2>1. 現在のテーブル構造</h2>";

// テーブルの存在確認
$table_exists = $wpdb->get_var("SHOW TABLES LIKE '{$table_name}'");
if (!$table_exists) {
    echo "❌ テーブル {$table_name} が存在しません<br>";
    exit;
}

echo "✅ テーブル {$table_name} が存在します<br>";

// カラム構造を取得
$columns = $wpdb->get_results("SHOW COLUMNS FROM `{$table_name}`");

echo "<h3>現在のカラム一覧:</h3>";
echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
echo "<tr style='background-color: #f0f0f0;'>";
echo "<th>フィールド名</th><th>型</th><th>NULL</th><th>キー</th><th>デフォルト</th><th>Extra</th>";
echo "</tr>";

foreach ($columns as $column) {
    $is_target = in_array($column->Field, ['desired_delivery_date', 'expected_delivery_date']);
    $bg_color = $is_target ? '#ffffcc' : '';
    
    echo "<tr style='background-color: {$bg_color};'>";
    echo "<td>{$column->Field}</td>";
    echo "<td>{$column->Type}</td>";
    echo "<td>{$column->Null}</td>";
    echo "<td>{$column->Key}</td>";
    echo "<td>" . ($column->Default ?? 'NULL') . "</td>";
    echo "<td>{$column->Extra}</td>";
    echo "</tr>";
}

echo "</table>";

echo "<h2>2. 納期フィールドの存在確認</h2>";
$column_names = array_column($columns, 'Field');
$desired_exists = in_array('desired_delivery_date', $column_names);
$expected_exists = in_array('expected_delivery_date', $column_names);

echo "希望納期フィールド (desired_delivery_date): " . ($desired_exists ? "✅ 存在" : "❌ 不存在") . "<br>";
echo "納品予定日フィールド (expected_delivery_date): " . ($expected_exists ? "✅ 存在" : "❌ 不存在") . "<br>";

echo "<h2>3. 修正SQL生成</h2>";

if (!$desired_exists) {
    echo "<h3>希望納期フィールド追加SQL:</h3>";
    echo "<pre style='background: #f5f5f5; padding: 10px; border: 1px solid #ccc;'>";
    echo "ALTER TABLE `{$table_name}` ADD COLUMN `desired_delivery_date` DATE NULL DEFAULT NULL COMMENT '希望納期';";
    echo "</pre>";
}

if (!$expected_exists) {
    echo "<h3>納品予定日フィールド追加SQL:</h3>";
    echo "<pre style='background: #f5f5f5; padding: 10px; border: 1px solid #ccc;'>";
    echo "ALTER TABLE `{$table_name}` ADD COLUMN `expected_delivery_date` DATE NULL DEFAULT NULL COMMENT '納品予定日';";
    echo "</pre>";
}

echo "<h2>4. created_atカラムの問題確認</h2>";

$created_at_column = null;
foreach ($columns as $column) {
    if ($column->Field === 'created_at') {
        $created_at_column = $column;
        break;
    }
}

if ($created_at_column) {
    echo "<h3>created_atカラムの現在の設定:</h3>";
    echo "フィールド名: {$created_at_column->Field}<br>";
    echo "型: {$created_at_column->Type}<br>";
    echo "NULL: {$created_at_column->Null}<br>";
    echo "デフォルト: " . ($created_at_column->Default ?? 'NULL') . "<br>";
    echo "Extra: {$created_at_column->Extra}<br>";
    
    // 問題のあるデフォルト値を修正するSQL
    echo "<h3>created_atカラム修正SQL:</h3>";
    echo "<pre style='background: #f5f5f5; padding: 10px; border: 1px solid #ccc;'>";
    echo "ALTER TABLE `{$table_name}` MODIFY COLUMN `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP;";
    echo "</pre>";
    
    echo "<h3>または、created_atカラムを削除して再作成:</h3>";
    echo "<pre style='background: #f5f5f5; padding: 10px; border: 1px solid #ccc;'>";
    echo "ALTER TABLE `{$table_name}` DROP COLUMN `created_at`;\n";
    echo "ALTER TABLE `{$table_name}` ADD COLUMN `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP;";
    echo "</pre>";
} else {
    echo "created_atカラムは存在しません<br>";
}

echo "<h2>5. 安全な実行手順</h2>";
echo "<ol>";
echo "<li>まず、データベースのバックアップを取得してください</li>";
echo "<li>created_atカラムの問題を修正します（上記のSQLを使用）</li>";
echo "<li>納期フィールドを追加します（上記のSQLを使用）</li>";
echo "<li>各SQLを1つずつ実行してください</li>";
echo "</ol>";

echo "<h2>6. 一括実行SQL（注意: バックアップを先に取得してください）</h2>";

echo "<pre style='background: #f5f5f5; padding: 10px; border: 1px solid #ccc;'>";

if ($created_at_column) {
    echo "-- created_atカラムの修正\n";
    echo "ALTER TABLE `{$table_name}` MODIFY COLUMN `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP;\n\n";
}

if (!$desired_exists) {
    echo "-- 希望納期フィールドの追加\n";
    echo "ALTER TABLE `{$table_name}` ADD COLUMN `desired_delivery_date` DATE NULL DEFAULT NULL COMMENT '希望納期';\n\n";
}

if (!$expected_exists) {
    echo "-- 納品予定日フィールドの追加\n";
    echo "ALTER TABLE `{$table_name}` ADD COLUMN `expected_delivery_date` DATE NULL DEFAULT NULL COMMENT '納品予定日';\n\n";
}

echo "</pre>";

echo "<p><strong>注意:</strong> 実行前に必ずデータベースのバックアップを取得してください。</p>";

echo "<h2>データベース構造確認・修正</h2>";

// テーブル名
$table_name = $wpdb->prefix . 'ktp_client';

echo "<h3>1. テーブル存在確認</h3>";
$table_exists = $wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $table_name));
if ($table_exists) {
    echo "✅ テーブル {$table_name} は存在します。<br>";
} else {
    echo "❌ テーブル {$table_name} は存在しません。<br>";
    exit;
}

echo "<h3>2. カラム構造確認</h3>";
$columns = $wpdb->get_results("SHOW COLUMNS FROM {$table_name}");
echo "<table border='1' style='border-collapse: collapse;'>";
echo "<tr><th>カラム名</th><th>型</th><th>NULL</th><th>キー</th><th>デフォルト</th><th>その他</th></tr>";

$has_category = false;
foreach ($columns as $column) {
    echo "<tr>";
    echo "<td>{$column->Field}</td>";
    echo "<td>{$column->Type}</td>";
    echo "<td>{$column->Null}</td>";
    echo "<td>{$column->Key}</td>";
    echo "<td>{$column->Default}</td>";
    echo "<td>{$column->Extra}</td>";
    echo "</tr>";
    
    if ($column->Field === 'category') {
        $has_category = true;
    }
}
echo "</table>";

echo "<h3>3. categoryカラム確認</h3>";
if ($has_category) {
    echo "✅ categoryカラムは存在します。<br>";
} else {
    echo "❌ categoryカラムが存在しません。追加します...<br>";
    
    // categoryカラムを追加
    $result = $wpdb->query("ALTER TABLE {$table_name} ADD COLUMN category VARCHAR(255) NULL");
    
    if ($result !== false) {
        echo "✅ categoryカラムを正常に追加しました。<br>";
        
        // 追加後の確認
        $columns_after = $wpdb->get_results("SHOW COLUMNS FROM {$table_name}");
        $has_category_after = false;
        foreach ($columns_after as $column) {
            if ($column->Field === 'category') {
                $has_category_after = true;
                echo "✅ 追加されたcategoryカラム: {$column->Type}<br>";
                break;
            }
        }
        
        if (!$has_category_after) {
            echo "❌ categoryカラムの追加に失敗しました。<br>";
        }
    } else {
        echo "❌ categoryカラムの追加に失敗しました。エラー: " . $wpdb->last_error . "<br>";
    }
}

echo "<h3>4. サンプルデータ確認</h3>";
$sample_data = $wpdb->get_results("SELECT id, company_name, name, category FROM {$table_name} LIMIT 5");
if ($sample_data) {
    echo "<table border='1' style='border-collapse: collapse;'>";
    echo "<tr><th>ID</th><th>会社名</th><th>担当者名</th><th>カテゴリー</th></tr>";
    foreach ($sample_data as $row) {
        echo "<tr>";
        echo "<td>{$row->id}</td>";
        echo "<td>" . esc_html($row->company_name) . "</td>";
        echo "<td>" . esc_html($row->name) . "</td>";
        echo "<td>" . esc_html($row->category ?? '未設定') . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "データがありません。<br>";
}

echo "<h3>5. 完了</h3>";
echo "データベース構造の確認・修正が完了しました。<br>";
echo "<a href='javascript:history.back()'>戻る</a>";
?> 