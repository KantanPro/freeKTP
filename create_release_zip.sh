#!/bin/zsh

# --- 設定 ---
# プラグインのソースコードが格納されているディレクトリ（現在のディレクトリを使用）
SOURCE_DIR="$(pwd)"
# 生成したZIPファイルの保存先
DEST_PARENT_DIR="/Users/kantanpro/Desktop"
# 保存先フォルダ名
DEST_DIR_NAME="KantanPro_TEST_UP"
# --- 設定ここまで ---

# ビルド用の変数を設定
DEST_DIR="${DEST_PARENT_DIR}/${DEST_DIR_NAME}"
BUILD_DIR_NAME="KantanPro"
BUILD_DIR="${DEST_DIR}/${BUILD_DIR_NAME}"

# エラーが発生した場合はスクリプトを終了する
set -e

echo "--------------------------------------------------"
echo "KantanPro プラグイン配布用ZIPファイル生成スクリプト"
echo "--------------------------------------------------"

# 1. バージョンと日付の取得
echo "[1/6] バージョン情報を取得中..."
# ktpwp.phpからバージョンを抽出 (例: "1.0.6(preview)" -> "1.0.6", "1.0.0(a)" -> "1.0.0a")
VERSION_RAW=$(grep -i "Version:" "$SOURCE_DIR/ktpwp.php" | head -n 1)
echo "  - 生のバージョン情報: ${VERSION_RAW}"
VERSION=$(echo "$VERSION_RAW" | sed -E 's/.*Version:[[:space:]]*([0-9]+\.[0-9]+\.[0-9]+)\(?([a-zA-Z0-9]*)\)?.*/\1\2/')
DATE=$(date +%Y%m%d)
ZIP_FILE_NAME="KantanPro_${VERSION}_${DATE}.zip"
FINAL_ZIP_PATH="${DEST_DIR}/${ZIP_FILE_NAME}"

echo "  - バージョン: ${VERSION}"
echo "  - 日付: ${DATE}"
echo "  - ZIPファイル名: ${ZIP_FILE_NAME}"

# 2. ビルド環境の準備
echo "\n[2/6] ビルド環境をクリーンアップ中..."
mkdir -p "${DEST_DIR}"
rm -rf "${BUILD_DIR}"
rm -f "${FINAL_ZIP_PATH}"
echo "  - 完了"

# 3. 配布に必要なファイルのみをコピー
echo "\n[3/6] 配布用ファイルをコピー中..."

# 配布に必要なディレクトリとファイルのみをコピー
mkdir -p "${BUILD_DIR}"

# メインプラグインファイル
cp "${SOURCE_DIR}/ktpwp.php" "${BUILD_DIR}/"
cp "${SOURCE_DIR}/readme.txt" "${BUILD_DIR}/"

# includesディレクトリ（プラグインのコア機能）
if [ -d "${SOURCE_DIR}/includes" ]; then
    cp -r "${SOURCE_DIR}/includes" "${BUILD_DIR}/"
fi

# jsディレクトリ（フロントエンド機能）
if [ -d "${SOURCE_DIR}/js" ]; then
    cp -r "${SOURCE_DIR}/js" "${BUILD_DIR}/"
fi

# cssディレクトリ（スタイル）
if [ -d "${SOURCE_DIR}/css" ]; then
    cp -r "${SOURCE_DIR}/css" "${BUILD_DIR}/"
fi

# imagesディレクトリ（デフォルト画像のみ）
if [ -d "${SOURCE_DIR}/images" ]; then
    cp -r "${SOURCE_DIR}/images" "${BUILD_DIR}/"
fi

# languagesディレクトリ（翻訳ファイル）
if [ -d "${SOURCE_DIR}/languages" ]; then
    cp -r "${SOURCE_DIR}/languages" "${BUILD_DIR}/"
fi

# composer.json（依存関係情報）
if [ -f "${SOURCE_DIR}/composer.json" ]; then
    cp "${SOURCE_DIR}/composer.json" "${BUILD_DIR}/"
fi

echo "  - 完了"

# 4. 不要なファイルを削除
echo "\n[4/6] 不要な開発用ファイルを削除中..."
# 削除前のファイル数を記録
BEFORE_COUNT=$(find "${BUILD_DIR}" -type f | wc -l)

# システムファイル
find "${BUILD_DIR}" -type f -name ".DS_Store" -delete
find "${BUILD_DIR}" -type f -name "Thumbs.db" -delete

# 開発設定ファイル
find "${BUILD_DIR}" -type f -name ".phpcs.xml" -delete
find "${BUILD_DIR}" -type f -name ".editorconfig" -delete
find "${BUILD_DIR}" -type f -name ".cursorrules" -delete
find "${BUILD_DIR}" -type f -name ".gitignore" -delete
find "${BUILD_DIR}" -type f -name "composer.lock" -delete

# 開発用ドキュメント
find "${BUILD_DIR}" -type f -name "README.md" -delete
find "${BUILD_DIR}" -type f -name "DEVELOPMENT-README.md" -delete
find "${BUILD_DIR}" -type f -name "QUICK-START.md" -delete
find "${BUILD_DIR}" -type f -name "SECURITY.md" -delete
find "${BUILD_DIR}" -type f -name "WP-CLI-README.md" -delete
find "${BUILD_DIR}" -type f -name "DUMMY-DATA-README.md" -delete
find "${BUILD_DIR}" -type f -name "DISTRIBUTION-README.md" -delete

# 実装完了ドキュメント
find "${BUILD_DIR}" -type f -name "*-COMPLETE.md" -delete
find "${BUILD_DIR}" -type f -name "*-IMPLEMENTATION-COMPLETE.md" -delete
find "${BUILD_DIR}" -type f -name "*-FIX-COMPLETE.md" -delete
find "${BUILD_DIR}" -type f -name "*-UPDATE-COMPLETE.md" -delete
find "${BUILD_DIR}" -type f -name "*-ENHANCEMENT-COMPLETE.md" -delete
find "${BUILD_DIR}" -type f -name "*-ERROR-FIX-COMPLETE.md" -delete
find "${BUILD_DIR}" -type f -name "*-PROPOSAL.md" -delete
find "${BUILD_DIR}" -type f -name "*-SUMMARY.md" -delete
find "${BUILD_DIR}" -type f -name "*-RESULTS.md" -delete
find "${BUILD_DIR}" -type f -name "*-CHECKLIST.md" -delete

# 開発用PHPファイル
find "${BUILD_DIR}" -type f \( -name "test-*.php" -o -name "test_*.php" -o -name "debug-*.php" -o -name "debug_*.php" -o -name "check-*.php" -o -name "check_*.php" -o -name "fix-*.php" -o -name "fix_*.php" -o -name "migrate-*.php" -o -name "migrate_*.php" -o -name "auto-*.php" -o -name "auto_*.php" -o -name "manual-*.php" -o -name "manual_*.php" -o -name "direct-*.php" -o -name "direct_*.php" -o -name "clear-*.php" -o -name "clear_*.php" -o -name "run-*.php" -o -name "run_*.php" -o -name "admin-migrate.php" -o -name "ajax_test.php" -o -name "analyze_debug_log.php" -o -name "create_dummy_data.php" -o -name "create_dummy_data.php.bak" -o -name "wp-cli-create-dummy-data.php" -o -name "test-license-reset.php" -o -name "test-report-ajax.php" -o -name "development-config.php" \) -delete

# 開発用shellスクリプト
find "${BUILD_DIR}" -type f \( -name "test-*.sh" -o -name "test_*.sh" -o -name "*_test.sh" -o -name "*-test.sh" -o -name "create_release_zip.sh" -o -name "run-dummy-data.sh" -o -name "create_release_zip.sh" -o -name "wp-cli.sh" -o -name "wp-cli-aliases.sh" -o -name "setup-wp-cli.sh" \) -delete

# 開発用JS/CSSファイル
find "${BUILD_DIR}" -type f \( -name "*-test.js" -o -name "*-debug.js" -o -name "*-fixed.js" -o -name "*-test.css" -o -name "*-debug.css" -o -name "*-fixed.css" -o -name "test-*.js" -o -name "debug-*.js" -o -name "fix-*.js" -o -name "test-*.css" -o -name "debug-*.css" -o -name "fix-*.css" -o -name "service-fix.*" -o -name "*debug-helper.js" -o -name "ktp-js-backup-*.js" -o -name "ktp-js-working.js" -o -name "ktp-js-fixed.js" -o -name "ktp-js.js.bak" -o -name "ktp-invoice-items.js.bak" -o -name "plugin-reference.js" -o -name "implementation-test.js" -o -name "ktp-calculation-*.js" -o -name "ktp-cost-toggle-*.js" -o -name "test-both-toggles.js" -o -name "test-staff-chat-scroll.js" \) -delete

# 開発用HTMLファイル
find "${BUILD_DIR}" -type f -name "debug-*.html" -delete

# 不要なディレクトリ
find "${BUILD_DIR}" -type d -name "KantanPro_temp" -exec rm -rf {} + 2>/dev/null || true
find "${BUILD_DIR}" -type d -name "wp" -exec rm -rf {} + 2>/dev/null || true
find "${BUILD_DIR}" -type d -name "wp-content" -exec rm -rf {} + 2>/dev/null || true

# アップロード画像を削除（デフォルト画像のみ保持）
if [ -d "${BUILD_DIR}/images/upload" ]; then
    find "${BUILD_DIR}/images/upload" -mindepth 1 -delete 2>/dev/null || true
fi

# 削除後のファイル数を記録
AFTER_COUNT=$(find "${BUILD_DIR}" -type f | wc -l)
DELETED_COUNT=$((BEFORE_COUNT - AFTER_COUNT))
echo "  - 削除されたファイル数: ${DELETED_COUNT}"
echo "  - 配布版ファイル数: ${AFTER_COUNT}"
echo "  - 完了"

# 5. ZIP圧縮
echo "\n[5/6] ZIPファイルを作成中..."
(cd "${BUILD_DIR}/.." && zip -r -q "${FINAL_ZIP_PATH}" "${BUILD_DIR_NAME}")

if [ $? -eq 0 ]; then
    # 6. 最終検証
    echo "\n[6/6] 最終検証を実行中..."
    
    # ZIPファイルの整合性チェック
    if unzip -t "${FINAL_ZIP_PATH}" > /dev/null 2>&1; then
        echo "  ✅ ZIPファイルの整合性: 正常"
    else
        echo "  ❌ ZIPファイルの整合性: エラー"
        exit 1
    fi
    
    # ファイルサイズチェック
    ZIP_SIZE_BYTES=$(stat -f%z "${FINAL_ZIP_PATH}")
    ZIP_SIZE_MB=$(echo "scale=2; ${ZIP_SIZE_BYTES} / 1024 / 1024" | bc)
    ZIP_SIZE=$(ls -lh "${FINAL_ZIP_PATH}" | awk '{print $5}')
    echo "  ✅ ZIPファイルサイズ: ${ZIP_SIZE} (${ZIP_SIZE_MB} MB)"
    
    # ファイルサイズが1-2MBの範囲内かチェック
    if (( $(echo "${ZIP_SIZE_MB} >= 1 && ${ZIP_SIZE_MB} <= 2" | bc -l) )); then
        echo "  ✅ ファイルサイズ: 適切な範囲内 (1-2MB)"
    else
        echo "  ⚠️  ファイルサイズ: 期待される範囲外 (1-2MB)"
    fi
    
    # 重要ファイルの存在チェック
    if unzip -l "${FINAL_ZIP_PATH}" | grep -q "ktpwp.php"; then
        echo "  ✅ メインプラグインファイル: 存在"
    else
        echo "  ❌ メインプラグインファイル: 見つかりません"
        exit 1
    fi
    
    if unzip -l "${FINAL_ZIP_PATH}" | grep -q "readme.txt"; then
        echo "  ✅ readme.txt: 存在"
    else
        echo "  ❌ readme.txt: 見つかりません"
        exit 1
    fi
    
    # 開発ファイルが除外されているかチェック
    if ! unzip -l "${FINAL_ZIP_PATH}" | grep -q "debug-"; then
        echo "  ✅ デバッグファイル: 適切に除外"
    else
        echo "  ⚠️  デバッグファイル: 一部が残っています"
    fi
    
    if ! unzip -l "${FINAL_ZIP_PATH}" | grep -q "test-"; then
        echo "  ✅ テストファイル: 適切に除外"
    else
        echo "  ⚠️  テストファイル: 一部が残っています"
    fi
    
    # クリーンアップ
    rm -rf "${BUILD_DIR}"
    echo "  ✅ 一時ファイル: クリーンアップ完了"
    
    echo "\n--------------------------------------------------"
    echo "✅ ビルドプロセスが正常に完了しました！"
    echo "ZIPファイル: ${FINAL_ZIP_PATH}"
    echo "ファイルサイズ: ${ZIP_SIZE} (${ZIP_SIZE_MB} MB)"
    echo "解凍後フォルダ: ${BUILD_DIR_NAME}"
    echo "--------------------------------------------------"
else
    echo "\n❌ ZIPファイルの作成に失敗しました。"
    exit 1
fi 