# KantanPro - WordPressビジネス管理プラグイン

[![WordPress](https://img.shields.io/badge/WordPress-5.0+-blue.svg)](https://wordpress.org/)
[![PHP](https://img.shields.io/badge/PHP-7.4+-green.svg)](https://php.net/)
[![License](https://img.shields.io/badge/License-GPL%20v2%20or%20later-red.svg)](https://www.gnu.org/licenses/gpl-2.0.html)

KantanProは、中小企業向けの包括的なビジネス管理プラグインです。受注管理から顧客管理、仕入先管理、スタッフ間コミュニケーションまで、ビジネスに必要な機能を統合的に提供します。

## 🚀 主要機能

### 📋 仕事リスト管理
- 受注案件の進捗管理（7段階ステータス）
- 納期管理とアラート機能
- 優先度設定とソート機能
- ページネーション対応

### 📄 伝票処理
- 受注書の作成・編集・印刷
- PDF保存機能
- 請求書管理
- 原価管理と利益計算

### 👥 顧客管理
- 顧客情報の一元管理
- 取引履歴の自動記録
- 顧客カテゴリ分類
- 印刷テンプレート機能

### 🛠️ サービス・商品管理
- サービス・商品マスター管理
- 価格設定と原価管理
- 画像アップロード機能
- カテゴリ分類

### 🏢 協力会社・仕入先管理
- 仕入先・外注先情報管理
- 商品・サービス能力管理
- 単価管理と頻度設定
- ページネーション対応

### 📊 レポート・分析
- 売上分析グラフ
- 進捗状況レポート
- データ集計機能
- カスタムレポート

### 💬 スタッフチャット
- 案件別スタッフ間連絡
- リアルタイムメッセージ
- 自動スクロール機能
- メッセージ履歴管理

### ⚙️ 設定・カスタマイズ
- システム設定（ロゴ、システム名等）
- デザインカスタマイズ
- SMTP設定
- スタッフ管理

### 🔗 Contact Form 7連携
- お問い合わせフォームからの自動顧客登録
- 受注データの自動作成
- フィールドマッピング機能

### 🔄 自動更新機能
- GitHubからの自動更新
- バージョン管理
- セキュリティ更新

## 🛡️ セキュリティ機能

- ログイン認証必須
- 権限ベースアクセス制御
- データサニタイゼーション
- セキュリティヘッダー設定
- 非ce検証

## 📋 システム要件

- **WordPress**: 5.0以上
- **PHP**: 7.4以上
- **MySQL**: 5.6以上 または MariaDB 10.0以上
- **推奨メモリ**: 256MB以上

## 🚀 インストール

### 1. プラグインのインストール
```bash
# WordPress管理画面からインストール
# または、プラグインファイルを /wp-content/plugins/KantanPro/ にアップロード
```

### 2. プラグインの有効化
WordPress管理画面でプラグインを有効化します。

### 3. ショートコードの設置
固定ページに以下のショートコードを挿入：
```
[ktpwp_all_tab]
```

### 4. 初期設定
管理画面の「KantanPro設定」で以下を設定：
- 一般設定（ロゴ、システム名等）
- メール・SMTP設定
- デザイン設定
- ライセンス設定
- スタッフ管理

## 📖 使用方法

### 基本的なワークフロー

1. **顧客情報の登録**
   - 「顧客」タブから顧客情報を登録

2. **サービスの登録**
   - 「サービス」タブから提供するサービス・商品を登録

3. **仕入先の登録**
   - 「協力会社」タブから仕入先・外注先情報を登録

4. **受注書の作成**
   - 「伝票処理」タブから新規受注書を作成
   - 顧客とサービスを選択

5. **進捗管理**
   - 「仕事リスト」タブで案件の進捗を管理

### ショートコード

| ショートコード | 説明 |
|---------------|------|
| `[ktpwp_all_tab]` | メインのプラグイン機能を表示 |

## 🔧 管理画面

### KantanPro設定
- **一般設定**: プラグインの基本設定
- **メール・SMTP設定**: メール送信に関する設定
- **デザイン**: 外観とデザインに関する設定
- **ライセンス設定**: アクティベーションキー管理
- **スタッフ管理**: ユーザー権限管理

## 🏗️ アーキテクチャ

### クラス構造
```
KTPWP_Main (メインクラス)
├── KTPWP_Loader (クラス読み込み)
├── KTPWP_Security (セキュリティ)
├── KTPWP_Assets (アセット管理)
├── KTPWP_Shortcodes (ショートコード)
├── KTPWP_Ajax (Ajax処理)
├── KTPWP_Database (データベース)
└── KTPWP_Migration (マイグレーション)
```

### タブ機能クラス
- `Kntan_Client_Class` - 顧客管理
- `Kntan_Order_Class` - 受注管理
- `Kntan_Service_Class` - サービス管理
- `KTPWP_Supplier_Class` - 仕入先管理
- `KTPWP_Report_Class` - レポート機能

### データベーステーブル
- `ktp_client` - 顧客情報
- `ktp_order` - 受注情報
- `ktp_service` - サービス情報
- `ktp_supplier` - 仕入先情報
- `ktp_supplier_skills` - 仕入先スキル
- `ktp_order_staff_chat` - スタッフチャット

## 🔒 セキュリティ

### 実装されているセキュリティ対策
- SQLインジェクション防止
- XSS・CSRF対策
- ファイルアップロード検証
- 権限管理・安全なDBアクセス
- データサニタイゼーション
- 非ce検証

## 🐛 トラブルシューティング

### よくある問題

**Q: プラグインが表示されない**
A: ショートコード `[ktpwp_all_tab]` が正しく挿入されているか確認してください。

**Q: 権限エラーが表示される**
A: 編集者権限（edit_posts）または専用権限（ktpwp_access）が必要です。

**Q: データベースエラーが発生する**
A: プラグインを無効化してから再度有効化してください。

**Q: Contact Form 7連携が動作しない**
A: Contact Form 7プラグインが有効化されているか確認してください。

## 📝 開発者向け情報

### フック・フィルター
```php
// カスタムCSS追加
add_filter('ktpwp_custom_css', function($css) {
    return $css . '.custom-style { color: red; }';
});

// データベース操作前
add_action('ktpwp_before_save_client', function($client_data) {
    // カスタム処理
});
```

### カスタマイズ例
```php
// テーマのfunctions.phpに追加
function custom_ktpwp_styles() {
    wp_enqueue_style('custom-ktpwp', get_template_directory_uri() . '/css/custom-ktpwp.css');
}
add_action('wp_enqueue_scripts', 'custom_ktpwp_styles');
```

## 📄 ライセンス

このプロジェクトは [GPL v2 or later](https://www.gnu.org/licenses/gpl-2.0.html) ライセンスの下で公開されています。

## 🤝 貢献

1. このリポジトリをフォーク
2. 機能ブランチを作成 (`git checkout -b feature/AmazingFeature`)
3. 変更をコミット (`git commit -m 'Add some AmazingFeature'`)
4. ブランチにプッシュ (`git push origin feature/AmazingFeature`)
5. プルリクエストを作成

## 📞 サポート

- **ウェブサイト**: https://www.kantanpro.com/
- **サポート**: https://www.kantanpro.com/support/
- **GitHub**: https://github.com/KantanPro/freeKTP

## 📈 変更履歴

### 1.2.3 (2024-12-19)
- スタッフチャット機能の改善
- ページネーション機能の追加
- セキュリティ強化
- バグ修正

### 1.2.2 (2024-12-15)
- マイグレーション機能の追加
- データベース構造の最適化
- UI/UX改善

### 1.2.1 (2024-12-10)
- Contact Form 7連携機能
- 自動更新機能
- パフォーマンス改善

### 1.2.0 (2024-12-05)
- 仕入先スキル管理機能
- レポート機能の強化
- 設定画面の改善

### 1.1.0 (2024-11-30)
- クラス構造のリファクタリング
- セキュリティ強化
- エラーハンドリング改善

### 1.0.0 (2024-11-25)
- 初回リリース
- 基本的なワークフロー管理機能

---

**KantanPro** - あなたのビジネスのハブとなるシステム