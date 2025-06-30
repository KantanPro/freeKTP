# KantanPro スキーマ定義・マイグレーション開発者ガイド

## 概要

このガイドでは、KantanProプラグインで新しいテーブルやカラムを追加する際の開発方法を説明します。手動でマイグレーションスクリプトを作成する必要はありません。

## 新しいテーブルを追加する場合

### 1. スキーマ定義を追加

`includes/class-ktpwp-migration.php`の`init_schema_definitions()`メソッド内に新しいテーブルのスキーマ定義を追加します：

```php
// 例: ktp_clientテーブルを追加
$this->schema_definitions['ktp_client'] = array(
    'columns' => array(
        'id' => array(
            'type' => 'mediumint(9)',
            'null' => false,
            'key' => 'PRIMARY',
            'auto_increment' => true
        ),
        'company_name' => array(
            'type' => 'varchar(255)',
            'null' => false,
            'collation' => 'utf8mb4_unicode_520_ci'
        ),
        'contact_name' => array(
            'type' => 'varchar(100)',
            'null' => true,
            'default' => null,
            'collation' => 'utf8mb4_unicode_520_ci'
        ),
        'email' => array(
            'type' => 'varchar(255)',
            'null' => true,
            'default' => null
        ),
        'phone' => array(
            'type' => 'varchar(20)',
            'null' => true,
            'default' => null
        ),
        'created_at' => array(
            'type' => 'datetime',
            'null' => true,
            'default' => null
        ),
        'updated_at' => array(
            'type' => 'datetime',
            'null' => true,
            'default' => null
        )
    ),
    'indexes' => array(
        'PRIMARY' => array('id'),
        'company_name' => array('company_name')
    ),
    'engine' => 'InnoDB',
    'charset' => 'utf8mb4',
    'collation' => 'utf8mb4_unicode_520_ci'
);
```

### 2. テーブル作成処理を追加

`ktpwp.php`の`ktp_table_setup()`関数内にテーブル作成処理を追加します：

```php
function ktp_table_setup() {
    // 既存のテーブル作成処理...
    
    // 新しいクライアントテーブル作成処理
    if (class_exists('KTPWP_Client_DB')) {
        $client_db = KTPWP_Client_DB::get_instance();
        $client_db->create_table();
    }
}
```

## 既存テーブルにカラムを追加する場合

### 1. スキーマ定義を更新

`includes/class-ktpwp-migration.php`の`init_schema_definitions()`メソッド内の該当テーブルのスキーマ定義に新しいカラムを追加します：

```php
// ktp_orderテーブルに新しいカラムを追加する例
$this->schema_definitions['ktp_order'] = array(
    'columns' => array(
        // 既存のカラム...
        
        // 新しいカラムを追加
        'priority' => array(
            'type' => 'tinyint(1)',
            'null' => false,
            'default' => '1'
        ),
        'assigned_to' => array(
            'type' => 'mediumint(9)',
            'null' => true,
            'default' => null
        ),
        'estimated_hours' => array(
            'type' => 'decimal(5,2)',
            'null' => true,
            'default' => null
        )
    ),
    // 他の設定...
);
```

## スキーマ定義の詳細

### カラム定義のオプション

```php
'column_name' => array(
    'type' => 'varchar(255)',           // 必須: データ型
    'null' => false,                    // 必須: NULL許可（true/false）
    'default' => null,                  // オプション: デフォルト値
    'collation' => 'utf8mb4_unicode_520_ci', // オプション: 照合順序
    'auto_increment' => true,           // オプション: 自動増分
    'key' => 'PRIMARY'                  // オプション: キー種別
)
```

### サポートされるデータ型

- `varchar(n)` - 可変長文字列
- `text` - 長いテキスト
- `tinytext` - 短いテキスト
- `mediumint(n)` - 中程度の整数
- `bigint(n)` - 大きな整数
- `tinyint(n)` - 小さな整数
- `datetime` - 日時
- `date` - 日付
- `decimal(m,n)` - 小数点付き数値
- `boolean` - 真偽値

## マイグレーションの実行

### 自動実行

新しいスキーマ定義を追加した後、以下の場合に自動的にマイグレーションが実行されます：

1. **プラグイン有効化時**: プラグインを有効化すると自動実行
2. **プラグインアップデート時**: プラグインをアップデートすると自動実行

### 確認方法

1. **管理画面**: 「ツール」→「KTP マイグレーション」でスキーマ状態を確認
2. **ログファイル**: `wp-content/ktpwp-migration.log`で実行履歴を確認

## 開発時のベストプラクティス

### 1. スキーマ定義の管理

- すべてのテーブル定義を`init_schema_definitions()`メソッド内に集約
- コメントで各テーブルの用途を明記
- 一貫した命名規則を使用

### 2. バージョン管理

- スキーマ変更時はプラグインのバージョンを更新
- 変更履歴をドキュメントに記録

### 3. テスト

- 開発環境でスキーマ変更をテスト
- 本番環境での実行前にバックアップを取得

## 例: 完全なスキーマ定義

```php
private function init_schema_definitions() {
    // 受注テーブル
    $this->schema_definitions['ktp_order'] = array(
        'columns' => array(
            'id' => array(
                'type' => 'mediumint(9)',
                'null' => false,
                'key' => 'PRIMARY',
                'auto_increment' => true
            ),
            'client_id' => array(
                'type' => 'mediumint(9)',
                'null' => true,
                'default' => null
            ),
            'project_name' => array(
                'type' => 'varchar(255)',
                'null' => true,
                'default' => null,
                'collation' => 'utf8mb4_unicode_520_ci'
            ),
            'status' => array(
                'type' => 'varchar(50)',
                'null' => false,
                'default' => 'pending',
                'collation' => 'utf8mb4_unicode_520_ci'
            ),
            'created_at' => array(
                'type' => 'datetime',
                'null' => true,
                'default' => null
            ),
            'updated_at' => array(
                'type' => 'datetime',
                'null' => true,
                'default' => null
            )
        ),
        'indexes' => array(
            'PRIMARY' => array('id'),
            'client_id' => array('client_id'),
            'status' => array('status')
        ),
        'engine' => 'InnoDB',
        'charset' => 'utf8mb4',
        'collation' => 'utf8mb4_unicode_520_ci'
    );
    
    // クライアントテーブル
    $this->schema_definitions['ktp_client'] = array(
        'columns' => array(
            'id' => array(
                'type' => 'mediumint(9)',
                'null' => false,
                'key' => 'PRIMARY',
                'auto_increment' => true
            ),
            'company_name' => array(
                'type' => 'varchar(255)',
                'null' => false,
                'collation' => 'utf8mb4_unicode_520_ci'
            ),
            'contact_name' => array(
                'type' => 'varchar(100)',
                'null' => true,
                'default' => null,
                'collation' => 'utf8mb4_unicode_520_ci'
            ),
            'email' => array(
                'type' => 'varchar(255)',
                'null' => true,
                'default' => null
            ),
            'phone' => array(
                'type' => 'varchar(20)',
                'null' => true,
                'default' => null
            ),
            'created_at' => array(
                'type' => 'datetime',
                'null' => true,
                'default' => null
            )
        ),
        'indexes' => array(
            'PRIMARY' => array('id'),
            'company_name' => array('company_name')
        ),
        'engine' => 'InnoDB',
        'charset' => 'utf8mb4',
        'collation' => 'utf8mb4_unicode_520_ci'
    );
}
```

## トラブルシューティング

### よくある問題

1. **テーブルが見つからない**: スキーマ定義のテーブル名が正しいか確認
2. **カラム追加に失敗**: データ型や制約が正しいか確認
3. **マイグレーションが実行されない**: プラグインの有効化・アップデートが必要

### デバッグ方法

1. **ログファイル確認**: `wp-content/ktpwp-migration.log`
2. **管理画面確認**: 「ツール」→「KTP マイグレーション」
3. **WordPressデバッグログ**: `WP_DEBUG`を有効化

このシステムにより、手動でマイグレーションスクリプトを作成することなく、データベーススキーマの変更を自動的に管理できます。 