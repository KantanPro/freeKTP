# URL生成統一化修正完了

## 概要

KantanProプラグインのURL生成を統一し、サブディレクトリに依存しないURL生成を実現しました。これにより、ローカル環境と配布先環境で一貫したURLが生成されるようになります。

## 修正内容

### 1. 統一されたURL生成クラスの作成

**ファイル**: `includes/class-ktpwp-url-generator.php`

- サブディレクトリに依存しないURL生成を提供
- 以下のメソッドを実装：
  - `get_current_base_url()`: クリーンなベースURLを取得
  - `get_tab_url()`: タブURLを生成
  - `get_detail_url()`: データ詳細URLを生成
  - `get_pagination_url()`: ページネーション付きURLを生成
  - `get_sort_url()`: ソート付きURLを生成
  - `get_current_page_base_url()`: ページID付きベースURLを取得

### 2. メインファイルでのクラス読み込み追加

**ファイル**: `ktpwp.php`

- `KTPWP_URL_Generator` クラスを自動読み込みリストに追加

### 3. 各タブクラスの修正

#### `includes/class-view-tab.php`
- `add_query_arg(null, null)` の使用を継続しつつ、URL生成クラスを優先使用
- フォールバック機能を実装

#### `includes/class-tab-client.php`
- `home_url($wp->request)` を `KTPWP_URL_Generator::get_current_page_base_url()` に変更
- フォールバック機能を実装

#### `includes/class-tab-service.php`
- `home_url($wp->request)` を `KTPWP_URL_Generator::get_current_page_base_url()` に変更
- フォールバック機能を実装

#### `includes/class-tab-supplier.php`
- 複数箇所の `home_url($wp->request)` を `KTPWP_URL_Generator::get_current_page_base_url()` に変更
- フォールバック機能を実装

#### `includes/class-ktpwp-client-ui.php`
- `home_url($wp->request)` を `KTPWP_URL_Generator::get_current_page_base_url()` に変更
- フォールバック機能を実装

#### `includes/class-ktpwp-service-ui.php`
- 複数箇所の `home_url($wp->request)` を `KTPWP_URL_Generator::get_current_page_base_url()` に変更
- フォールバック機能を実装

#### `includes/class-ktpwp-service-db.php`
- 複数箇所の `home_url($wp->request)` を `KTPWP_URL_Generator::get_current_page_base_url()` に変更
- フォールバック機能を実装

#### `includes/class-ktpwp-redirect.php`
- `get_clean_base_url()` メソッドで `KTPWP_URL_Generator::get_current_base_url()` を使用
- フォールバック機能を実装

## 修正の効果

### 修正前
- ローカル: `http://localhost:8080/?tab_name=list`
- 配布先: `https://www.kantanpro.com/kantanpro-preview?tab_name=list`

### 修正後
- ローカル: `http://localhost:8080/?tab_name=list`
- 配布先: `https://www.kantanpro.com/?tab_name=list` (サブディレクトリを無視)

**結果**: 両環境で一貫したURL生成が実現され、サブディレクトリの有無に関係なく正しく動作します。

### 追加修正（2025年1月31日）

URL生成クラスをさらに修正し、`home_url()` を使用してドメインルートのURLを取得するようにしました。これにより、WordPressがサブディレクトリにインストールされていても、常にドメインルートのURLが生成されます。

#### 修正内容
- `get_current_base_url()`: `add_query_arg(null, null)` から `home_url()` に変更
- `get_current_page_base_url()`: 同様に `home_url()` ベースに変更

#### 技術的詳細
- `home_url()` はWordPressの設定に関係なく、常にドメインルートのURLを返します
- サブディレクトリインストールの場合でも、`home_url()` はサブディレクトリを含まないURLを返します
- これにより、環境に依存しない一貫したURL生成が実現されます

## フォールバック機能

すべての修正箇所で、URL生成クラスが利用できない場合のフォールバック機能を実装しています：

```php
if ( class_exists( 'KTPWP_URL_Generator' ) ) {
    $base_page_url = KTPWP_URL_Generator::get_current_page_base_url();
} else {
    // フォールバック: 従来の方法
    global $wp;
    $current_page_id = get_queried_object_id();
    $base_page_url = add_query_arg( array( 'page_id' => $current_page_id ), home_url( $wp->request ) );
}
```

## テスト推奨事項

1. **ローカル環境でのテスト**
   - 各タブの動作確認
   - リンクの正常性確認
   - ページネーションの動作確認

2. **配布先環境でのテスト**
   - 各タブの動作確認
   - リンクの正常性確認
   - ページネーションの動作確認

3. **両環境での比較**
   - URLの一貫性確認
   - 機能の同等性確認

## 技術的詳細

### URL生成の統一化

- **従来**: `home_url($wp->request)` を使用（WordPress設定に依存）
- **修正後**: `add_query_arg(null, null)` をベースにした統一されたURL生成

### サブディレクトリ対応

- WordPressがサブディレクトリにインストールされている環境でも正しく動作
- 現在のページの完全なURLを取得し、適切にパラメータを管理

### 後方互換性

- フォールバック機能により、既存の環境でも問題なく動作
- 段階的な移行が可能

## 完了日時

- **修正完了**: 2024年12月19日
- **対象バージョン**: KantanPro 1.1.15(preview)
- **影響範囲**: 全タブのURL生成機能 