/**
 * コスト項目テーブルのJavaScript機能
 *
 * @package KTPWP
 * @since 1.0.0
 */

(function ($) {
    'use strict';

    // デバッグモードを有効化
    window.ktpDebugMode = true;

    // 重複追加防止フラグ (コスト項目専用)
    window.ktpAddingCostRow = false;

    // 単価×数量の自動計算
    function calculateAmount(row) {
        const price = parseFloat(row.find('.price').val()) || 0;
        const quantity = parseFloat(row.find('.quantity').val()) || 0;
        const amount = price * quantity;
        row.find('.amount').val(amount);

        // 金額を自動保存
        const itemId = row.find('input[name*="[id]"]').val();
        const orderId = $('input[name="order_id"]').val() || $('#order_id').val();

        if (itemId && orderId && itemId !== '0') {
            // 既存行の場合：金額を自動保存
            autoSaveItem('cost', itemId, 'amount', amount, orderId);
        }

        // 利益計算を更新
        updateProfitDisplay();
    }

    // 利益表示を更新
    function updateProfitDisplay() {
        let invoiceTotal = 0;
        let costTotal = 0;

        // 請求項目の合計を計算
        $('.invoice-items-table .amount').each(function () {
            invoiceTotal += parseFloat($(this).val()) || 0;
        });

        // コスト項目の合計を計算
        $('.cost-items-table .amount').each(function () {
            costTotal += parseFloat($(this).val()) || 0;
        });

        // 請求項目合計を切り上げ
        const invoiceTotalCeiled = Math.ceil(invoiceTotal);

        // コスト項目合計を切り上げ
        const costTotalCeiled = Math.ceil(costTotal);

        // 利益計算（切り上げ後の値を使用）
        const profit = invoiceTotalCeiled - costTotalCeiled;

        // 利益表示を更新
        const profitDisplay = $('.profit-display');
        if (profitDisplay.length > 0) {
            const profitColor = profit >= 0 ? '#28a745' : '#dc3545';
            profitDisplay.html('利益 : ' + profit.toLocaleString() + '円');
            profitDisplay.css('color', profitColor);

            // CSSクラスを更新
            profitDisplay.removeClass('positive negative');
            profitDisplay.addClass(profit >= 0 ? 'positive' : 'negative');
        }

        // コスト項目の合計表示も更新（切り上げ後の値を表示）
        const costTotalDisplay = $('.cost-items-total');
        if (costTotalDisplay.length > 0) {
            costTotalDisplay.html('合計金額 : ' + costTotalCeiled.toLocaleString() + '円');
        }
    }

    // 新しい行を追加（重複防止機能付き）
    function addNewRow(currentRow) {
        console.log('[COST] addNewRow開始');
        // 既に追加処理中の場合はスキップ
        if (window.ktpAddingCostRow === true) {
            console.log('[COST] 既に処理中のため中止 (ktpAddingCostRow is true at start)');
            return false; // 処理しなかった
        }

        // 追加処理中フラグを設定
        window.ktpAddingCostRow = true;
        console.log('[COST] ktpAddingCostRow を true に設定 (addNewRow)');

        let success = false;
        try {
            const table = currentRow.closest('table');
            const tbody = table.find('tbody');

            // 新しいインデックスを取得
            let maxIndex = -1;
            tbody.find('input[name*="cost_items["]').each(function () {
                const name = $(this).attr('name');
                const match = name.match(/cost_items\\[(\\d+)\\]/);
                if (match) {
                    const index = parseInt(match[1], 10);
                    if (index > maxIndex) {
                        maxIndex = index;
                    }
                }
            });
            const newIndex = maxIndex + 1;
            console.log('[COST] 新しいインデックス:', newIndex);

            const newRowHtml = `
                <tr class="cost-item-row" data-row-id="0" data-newly-added="true">
                    <td class="actions-column">
                        <span class="drag-handle" title="ドラッグして並び替え" tabindex="-1">&#9776;</span>
                        <button type="button" class="btn-add-row" title="行を追加" tabindex="-1">+</button>
                        <button type="button" class="btn-delete-row" title="行を削除" tabindex="-1">×</button>
                        <button type="button" class="btn-move-row" title="行を移動" tabindex="-1">></button>
                    </td>
                    <td>
                        <input type="text" name="cost_items[${newIndex}][product_name]" value="" class="cost-item-input product-name" tabindex="0" />
                        <input type="hidden" name="cost_items[${newIndex}][id]" value="0" />
                    </td>
                    <td style="text-align:left;">
                        <input type="number" name="cost_items[${newIndex}][price]" value="0" class="cost-item-input price" step="1" min="0" style="text-align:left;" disabled tabindex="0" />
                    </td>
                    <td style="text-align:left;">
                        <input type="number" name="cost_items[${newIndex}][quantity]" value="1" class="cost-item-input quantity" step="1" min="0" style="text-align:left;" disabled tabindex="0" />
                    </td>
                    <td>
                        <input type="text" name="cost_items[${newIndex}][unit]" value="式" class="cost-item-input unit" disabled tabindex="0" />
                    </td>
                    <td style="text-align:left;">
                        <input type="number" name="cost_items[${newIndex}][amount]" value="0" class="cost-item-input amount" step="1" readonly style="text-align:left;" tabindex="0" />
                    </td>
                    <td>
                        <input type="text" name="cost_items[${newIndex}][remarks]" value="" class="cost-item-input remarks" disabled tabindex="0" />
                    </td>
                </tr>
            `;
            currentRow.after(newRowHtml);
            const $newRow = currentRow.next();
            console.log('[COST] 新しい行が追加されました - インデックス:', newIndex);

            // 新しく追加された行の product_name にフォーカス
            $newRow.find('.product-name').focus();
            success = true;

        } catch (error) {
            console.error('[COST] addNewRow エラー:', error);
            success = false;
        } finally {
            // このsetTimeoutは、addNewRowが実際に処理を試みた場合にのみ実行されるべきフラグ解除
            // (つまり、ktpAddingCostRowがこの関数内でtrueに設定された場合)
            setTimeout(() => {
                window.ktpAddingCostRow = false;
                console.log('[COST] ktpAddingCostRow を false に設定 (addNewRow finally setTimeout)');
            }, 100); // 100ms後にフラグ解除
        }
        return success; // 処理の成否を返す
    }

    // 行を削除
    function deleteRow(currentRow) {
        const table = currentRow.closest('table');
        const tbody = table.find('tbody');

        // 最後の1行は削除しない
        if (tbody.find('tr').length <= 1) {
            alert('最低1行は必要です。');
            return;
        }

        if (confirm('この行を削除しますか？')) {
            const itemId = currentRow.find('input[name*="[id]"]').val();
            const orderId = $('input[name="order_id"]').val() || $('#order_id').val();
            console.log('[COST] deleteRow呼び出し', { itemId, orderId, row: currentRow });

            if (itemId && itemId !== '0' && orderId) {
                let ajaxUrl = ajaxurl;
                if (!ajaxUrl && typeof ktp_ajax_object !== 'undefined') {
                    ajaxUrl = ktp_ajax_object.ajax_url;
                } else if (!ajaxUrl) {
                    ajaxUrl = '/wp-admin/admin-ajax.php'; // Fallback
                }
                const nonce = (typeof ktp_ajax_nonce !== 'undefined') ? ktp_ajax_nonce : ((typeof ktp_ajax_object !== 'undefined' && ktp_ajax_object.nonce) ? ktp_ajax_object.nonce : '');

                const ajaxData = {
                    action: 'ktp_delete_item',
                    item_type: 'cost',
                    item_id: itemId,
                    order_id: orderId,
                    nonce: nonce
                };
                console.log('[COST] deleteRow送信', ajaxData);
                $.ajax({
                    url: ajaxUrl,
                    type: 'POST',
                    data: ajaxData,
                    success: function (response) {
                        console.log('[COST] deleteRowレスポンス', response);
                        try {
                            const result = typeof response === 'string' ? JSON.parse(response) : response;
                            if (result.success) {
                                console.log('[COST] deleteRowサーバー側削除成功');
                                currentRow.remove();
                                updateProfitDisplay(); // 合計金額と利益を更新
                            } else {
                                console.warn('[COST] deleteRowサーバー側削除失敗', result);
                                alert('行の削除に失敗しました。サーバーからの応答: ' + (result.data && result.data.message ? result.data.message : (result.message || '不明なエラー')));
                            }
                        } catch (e) {
                            console.error('[COST] deleteRowレスポンスパースエラー', e, response);
                            alert('行削除の応答処理中にエラーが発生しました。');
                        }
                    },
                    error: function (xhr, status, error) {
                        console.error('[COST] deleteRowエラー', { status, error, responseText: xhr.responseText });
                        alert('行の削除中にサーバーエラーが発生しました。');
                    }
                });
            } else if (itemId === '0') {
                // サーバーに保存されていない行は、確認後すぐに削除
                console.log('[COST] deleteRow: サーバー未保存行のため即時削除');
                currentRow.remove();
                updateProfitDisplay(); // 合計金額と利益を更新
            } else {
                console.warn('[COST] deleteRow: itemIdまたはorderIdが不足しているため、クライアント側でのみ削除');
                currentRow.remove();
                updateProfitDisplay(); // 合計金額と利益を更新
            }
        }
    }

    // 行のインデックスを更新 (Sortable用)
    function updateRowIndexes(table) {
        const tbody = table.find('tbody');
        tbody.find('tr').each(function (index) {
            const row = $(this);
            row.find('input, textarea').each(function () {
                const input = $(this);
                const name = input.attr('name');
                if (name && name.match(/^cost_items\[\d+\]/)) {
                    // 先頭の [数字] 部分だけを置換
                    const newName = name.replace(/^cost_items\[\d+\]/, `cost_items[${index}]`);
                    input.attr('name', newName);
                }
            });
        });
    }

    // 自動追加機能を無効化（[+]ボタンのみで行追加）
    function checkAutoAddRow(currentRow) {
        // 自動追加機能を無効化
        // [+]ボタンクリック時のみ行を追加する仕様に変更
        return;
    }

    // 自動保存機能
    function autoSaveItem(itemType, itemId, fieldName, fieldValue, orderId) {
        // Ajax URLの確認と代替設定
        let ajaxUrl = ajaxurl;
        if (!ajaxUrl) {
            ajaxUrl = '/wp-admin/admin-ajax.php';
            console.warn('ajaxurl not defined, using fallback');
        }

        const ajaxData = {
            action: 'ktp_auto_save_item',
            item_type: itemType, // 'cost' であることを期待
            item_id: itemId,
            field_name: fieldName,
            field_value: fieldValue,
            order_id: orderId,
            nonce: ktp_ajax_nonce || ''
        };

        console.log('Cost items - Sending Ajax request:', ajaxData);
        console.log('Ajax URL:', ajaxUrl);

        $.ajax({
            url: ajaxUrl,
            type: 'POST',
            data: ajaxData,
            success: function (response) {
                console.log('Cost items - Ajax response received:', response);
                try {
                    const result = typeof response === 'string' ? JSON.parse(response) : response;
                    if (result.success) {
                        console.log('Cost auto-saved successfully');
                        // 成功時の視覚的フィードバック（オプション）
                        // showSaveIndicator('saved');
                    } else {
                        console.error('Cost auto-save failed:', result.message);
                    }
                } catch (e) {
                    console.error('Cost auto-save response parse error:', e, 'Raw response:', response);
                }
            },
            error: function (xhr, status, error) {
                console.error('Cost auto-save Ajax error:', {
                    status: status,
                    error: error,
                    responseText: xhr.responseText,
                    statusCode: xhr.status
                });
            }
        });
    }

    // 新規レコード作成機能 (コールバック対応)
    function createNewItem(itemType, fieldName, fieldValue, orderId, $row, callback) {
        // Ajax URLの確認と代替設定
        let ajaxUrl = ajaxurl;
        if (!ajaxUrl) {
            ajaxUrl = '/wp-admin/admin-ajax.php';
            if (window.ktpDebugMode) {
                console.warn('ajaxurl not defined, using fallback');
            }
        }

        const ajaxData = {
            action: 'ktp_create_new_item',
            item_type: itemType, // 'cost' であることを期待
            field_name: fieldName,
            field_value: fieldValue,
            order_id: orderId,
            nonce: ktp_ajax_nonce || ''
        };

        if (window.ktpDebugMode) {
            console.log('Creating new cost item:', ajaxData);
        }

        $.ajax({
            url: ajaxUrl,
            type: 'POST',
            data: ajaxData,
            success: function (response) {
                if (window.ktpDebugMode) {
                    console.log('New cost item creation response:', response);
                }
                try {
                    const result = typeof response === 'string' ? JSON.parse(response) : response;
                    // wp_send_json_success はレスポンスを { success: true, data: { ... } } の形でラップする
                    if (result.success && result.data && result.data.item_id) {
                        const newItemId = result.data.item_id;
                        // 新しいIDをhidden inputに設定
                        $row.find('input[name*="[id]"]').val(newItemId);
                        $row.attr('data-row-id', newItemId); // data-row-idも更新

                        // data-newly-added属性を削除し、他のフィールドを有効化
                        if ($row.data('newly-added')) {
                            $row.removeAttr('data-newly-added');
                            $row.find('.cost-item-input').not('.product-name').not('.amount').prop('disabled', false);
                            console.log('[COST] createNewItem: 他のフィールドを有効化', $row);

                            // product_name からの最初の保存後、price フィールドにフォーカスを移す
                            const $priceField = $row.find('.cost-item-input.price');
                            if ($priceField.length > 0 && !$priceField.prop('disabled')) {
                                $priceField.focus();
                            }
                        }
                        if (window.ktpDebugMode) {
                            console.log('New cost item created with ID:', newItemId);
                        }
                        if (callback) callback(true, newItemId);
                    } else {
                        if (window.ktpDebugMode) {
                            console.error('New cost item creation failed:', result.message || (result.data ? result.data.message : 'Unknown error'));
                        }
                        if (callback) callback(false, null);
                    }
                } catch (e) {
                    if (window.ktpDebugMode) {
                        console.error('New cost item creation response parse error:', e, 'Raw response:', response);
                    }
                    if (callback) callback(false, null);
                }
            },
            error: function (xhr, status, error) {
                if (window.ktpDebugMode) {
                    console.error('New cost item creation Ajax error:', {
                        status: status,
                        error: error,
                        responseText: xhr.responseText,
                        statusCode: xhr.status
                    });
                }
                if (callback) callback(false, null);
            }
        });
    }

    // ページ読み込み完了時の初期化
    $(document).ready(function () {
        console.log('[COST] 📋 ページ初期化開始');
        // 並び替え（sortable）有効化
        $('.cost-items-table tbody').sortable({
            handle: '.drag-handle',
            items: '> tr',
            axis: 'y',
            helper: 'clone',
            update: function (event, ui) {
                const table = $(this).closest('table');
                updateRowIndexes(table); // これはname属性のインデックスを更新する

                // サーバーに並び順を保存
                const items = [];
                const orderId = $('input[name="order_id"]').val() || $('#order_id').val();
                $(this).find('tr').each(function (index) {
                    const itemId = $(this).find('input[name*="[id]"]').val();
                    if (itemId && itemId !== '0') {
                        items.push({ id: itemId, sort_order: index + 1 });
                    }
                });

                if (items.length > 0 && orderId) {
                    let ajaxUrl = ajaxurl;
                    if (!ajaxUrl && typeof ktp_ajax_object !== 'undefined') {
                        ajaxUrl = ktp_ajax_object.ajax_url;
                    } else if (!ajaxUrl) {
                        ajaxUrl = '/wp-admin/admin-ajax.php'; // Fallback
                    }
                    const nonce = (typeof ktp_ajax_nonce !== 'undefined') ? ktp_ajax_nonce : ((typeof ktp_ajax_object !== 'undefined' && ktp_ajax_object.nonce) ? ktp_ajax_object.nonce : '');

                    console.log('[COST] updateItemOrder送信', { order_id: orderId, items: items });
                    $.ajax({
                        url: ajaxUrl,
                        type: 'POST',
                        data: {
                            action: 'ktp_update_item_order',
                            order_id: orderId,
                            items: items,
                            item_type: 'cost',
                            nonce: nonce
                        },
                        success: function (response) {
                            console.log('[COST] updateItemOrderレスポンス', response);
                            try {
                                const result = typeof response === 'string' ? JSON.parse(response) : response;
                                if (result.success) {
                                    console.log('[COST] 並び順の保存に成功しました。');
                                } else {
                                    console.warn('[COST] 並び順の保存に失敗しました。', result);
                                    alert('並び順の保存に失敗しました。: ' + (result.data && result.data.message ? result.data.message : 'サーバーエラー'));
                                }
                            } catch (e) {
                                console.error('[COST] updateItemOrderレスポンスパースエラー', e, response);
                                alert('並び順保存の応答処理中にエラーが発生しました。');
                            }
                        },
                        error: function (xhr, status, error) {
                            console.error('[COST] updateItemOrderエラー', { status, error, responseText: xhr.responseText });
                            alert('並び順の保存中にサーバーエラーが発生しました。');
                        }
                    });
                } else {
                    console.log('[COST] 保存するアイテムがないか、orderIdがありません。');
                }
            },
            start: function (event, ui) {
                ui.item.addClass('dragging');
            },
            stop: function (event, ui) {
                ui.item.removeClass('dragging');
            }
        }).disableSelection();

        // 単価・数量変更時の金額自動計算
        $(document).on('input', '.cost-items-table .price, .cost-items-table .quantity', function () {
            const row = $(this).closest('tr');
            calculateAmount(row);

            // 金額の自動保存は calculateAmount 内で行われる
        });

        // 自動追加機能を無効化（コメントアウト）
        // $(document).on('input change', '.cost-items-table .service-name, .cost-items-table .price, .cost-items-table .quantity', function() {
        //     const row = $(this).closest('tr');
        //     const tbody = row.closest('tbody');
        //     const isFirstRow = tbody.find('tr').first().is(row);
        //
        //     // 手動で行を追加した直後は自動追加をスキップ
        //     if (row.hasClass('manual-add')) {
        //         return;
        //     }
        //
        //     // 1行目で実際に値が変更された場合のみ自動追加をチェック
        //     if (isFirstRow) {
        //         // 少し遅延を入れて、連続入力による重複を防ぐ
        //         clearTimeout(row.data('autoAddTimeout'));
        //         const timeoutId = setTimeout(function() {
        //             checkAutoAddRow(row);
        //         }, 300); // 300ms後にチェック
        //         row.data('autoAddTimeout', timeoutId);
        //     }
        // });

        // [+]ボタンで行追加（手動追加のみ）- イベント重複を防ぐ
        $(document).off('click.ktpCostAdd', '.cost-items-table .btn-add-row').on('click.ktpCostAdd', '.cost-items-table .btn-add-row', function (e) {
            e.preventDefault();
            e.stopPropagation();
            e.stopImmediatePropagation(); // 他のハンドラも止める

            const $button = $(this);
            // ボタン自体の状態で重複クリックをある程度防ぐ
            if ($button.prop('disabled') || $button.hasClass('processing')) {
                console.log('[COST] ボタンが無効または処理中のためスキップ（クリックハンドラ冒頭）');
                return false;
            }

            // グローバルフラグもチェック (addNewRowの冒頭でもチェックするが、ここでも念のため)
            // addNewRow内で最初にチェックされるので、ここでのチェックは必須ではないが、早期リターンとして有効
            if (window.ktpAddingCostRow === true) {
                console.log('[COST] グローバルフラグがtrueのためスキップ（クリックハンドラ冒頭）');
                // この場合、ボタンは無効化せず、ユーザーが再度試せるようにする
                // ただし、何らかの理由でフラグが解除されない場合に備え、UI/UXの観点からは
                // ボタンを一時的に無効化し、少し後に有効化する方が親切かもしれない。
                // 現状は、addNewRowがフラグを管理し、このハンドラのfinallyでボタンが有効化される。
                return false;
            }

            // 処理中フラグとボタン状態を設定
            $button.prop('disabled', true).addClass('processing');
            console.log('[COST] +ボタンクリック処理開始、ボタンを無効化');

            const currentRow = $(this).closest('tr');
            let rowWasAdded;

            try {
                rowWasAdded = addNewRow(currentRow); // addNewRow は true/false を返す
                console.log('[COST] addNewRow からの戻り値:', rowWasAdded);
            } catch (error) {
                // 通常、addNewRow内のtry/catchで捕捉されるはずだが、念のため
                console.error('[COST] +ボタンクリックで addNewRow 呼び出し中に予期せぬエラー:', error);
                rowWasAdded = false; // エラー時も失敗として扱う
            } finally {
                // addNewRow内のsetTimeout(100ms)でktpAddingCostRowがfalseになるのを待ってからボタンを有効化
                // rowWasAdded が false の場合 (addNewRowが処理をスキップした場合も含む) でも、
                // ボタンは一度 disabled にしたので、元に戻す必要がある。
                setTimeout(() => {
                    $button.prop('disabled', false).removeClass('processing');
                    console.log('[COST] ボタン再有効化完了 (クリックハンドラ finally setTimeout)');
                }, 200); // addNewRow内のsetTimeout(100ms) + バッファ
            }
            return false;
        });

        // 行削除ボタン - イベント重複を防ぐ
        $(document).off('click', '.cost-items-table .btn-delete-row').on('click', '.cost-items-table .btn-delete-row', function (e) {
            e.preventDefault();
            e.stopPropagation();
            const currentRow = $(this).closest('tr');
            deleteRow(currentRow);
        });

        // 行移動ボタン（将来の拡張用）
        $(document).on('click', '.btn-move-row', function (e) {
            e.preventDefault();
            // TODO: ドラッグ&ドロップ機能を実装
            alert('行移動機能は今後実装予定です。');
        });

        // フォーカス時の入力欄スタイル調整
        $(document).on('focus', '.cost-item-input', function () {
            $(this).addClass('focused');
        });

        $(document).on('blur', '.cost-item-input', function () {
            $(this).removeClass('focused');
        });

        // 数値フィールドフォーカス時に全選択
        $(document).on('focus', '.cost-items-table input[type="number"]', function () {
            $(this).select();
        });

        // 数値フィールドで半角数字のみを許可
        $(document).on('input', '.cost-items-table input[type="number"]', function () {
            let value = $(this).val();
            // 半角数字と小数点以外の文字を削除
            value = value.replace(/[^0-9.]/g, '');
            // 複数の小数点を最初のもの以外削除
            const parts = value.split('.');
            if (parts.length > 2) {
                value = parts[0] + '.' + parts.slice(1).join('');
            }
            $(this).val(value);
        });

        // 商品名フィールドのblurイベントで自動保存
        $(document).on('blur', '.cost-item-input.product-name', function () {
            const $field = $(this);
            const productName = $field.val();
            const $row = $field.closest('tr');
            let itemId = $row.find('input[name*="[id]"]').val();
            const orderId = $('input[name="order_id"]').val() || $('#order_id').val();

            if (window.ktpDebugMode) {
                console.log('Cost product name auto-save debug:', {
                    productName: productName,
                    itemId: itemId,
                    orderId: orderId,
                    hasNonce: typeof ktp_ajax_nonce !== 'undefined',
                    hasAjaxurl: typeof ajaxurl !== 'undefined'
                });
            }

            // 新規行（ID=0 または data-newly-added=true）と既存行の両方を処理
            if (orderId) {
                if (itemId === '0' || $row.data('newly-added')) {
                    // 新規行の場合：まず新しいレコードを作成
                    // 商品名が空でも、行が追加された以上は一度作成を試みる（IDを得るため）
                    // ただし、本当に空のレコードを作りたくない場合はここで productName のチェックを入れる
                    if (productName.trim() !== '' || $row.data('newly-added')) { // 新規追加行なら空でも一度作成
                        createNewItem('cost', 'product_name', productName, orderId, $row, function (success, newItemId) {
                            if (success && newItemId) {
                                itemId = newItemId; // itemIdを更新
                                // 他のフィールドが有効化されるので、必要ならここで何かする
                                // 例えば、単価や数量にデフォルト値があれば、それらをautoSaveItemで保存するなど
                                // 現状はcreateNewItemのコールバック内でフィールド有効化まで
                            } else {
                                // 作成失敗時の処理
                                console.warn('[COST] 商品名blur時、新規アイテム作成失敗');
                            }
                        });
                    }
                } else if (itemId) {
                    // 既存行の場合：通常の更新処理
                    autoSaveItem('cost', itemId, 'product_name', productName, orderId);
                }
            } else {
                if (window.ktpDebugMode) {
                    console.log('Cost product name auto-save skipped - missing required data');
                }
            }
        });

        // 単価フィールドのblurイベントで自動保存
        $(document).on('blur', '.cost-item-input.price', function () {
            const $field = $(this);
            // フィールドが無効なら何もしない (新規行で商品名入力前の状態)
            if ($field.prop('disabled')) return;

            const price = $field.val();
            const $row = $field.closest('tr');
            const itemId = $row.find('input[name*="[id]"]').val();
            const orderId = $('input[name="order_id"]').val() || $('#order_id').val();

            // 金額を再計算 (calculateAmountはinputイベントで呼ばれるが、blurでも念のため)
            // calculateAmount($row); // これがamountの保存もトリガーする可能性

            if (window.ktpDebugMode) {
                console.log('Cost price auto-save debug:', {
                    price: price,
                    itemId: itemId,
                    orderId: orderId,
                    hasNonce: typeof ktp_ajax_nonce !== 'undefined',
                    hasAjaxurl: typeof ajaxurl !== 'undefined'
                });
            }

            // 新規行（ID=0）は商品名入力時に作成されるので、ここでは既存行のみ対象
            if (orderId && itemId && itemId !== '0') {
                autoSaveItem('cost', itemId, 'price', price, orderId);
            } else {
                if (window.ktpDebugMode) {
                    console.log('Cost price auto-save skipped - item not yet created or missing data');
                }
            }
        });

        // 数量フィールドのblurイベントで自動保存
        $(document).on('blur', '.cost-item-input.quantity', function () {
            const $field = $(this);
            if ($field.prop('disabled')) return;

            const quantity = $field.val();
            const $row = $field.closest('tr');
            const itemId = $row.find('input[name*="[id]"]').val();
            const orderId = $('input[name="order_id"]').val() || $('#order_id').val();

            // 金額を再計算 (calculateAmountはinputイベントで呼ばれるが、blurでも念のため)
            // calculateAmount($row);

            if (window.ktpDebugMode) {
                console.log('Cost quantity auto-save debug:', {
                    quantity: quantity,
                    itemId: itemId,
                    orderId: orderId,
                    hasNonce: typeof ktp_ajax_nonce !== 'undefined',
                    hasAjaxurl: typeof ajaxurl !== 'undefined'
                });
            }

            // 新規行（ID=0）は商品名入力時に作成されるので、ここでは既存行のみ対象
            if (orderId && itemId && itemId !== '0') {
                autoSaveItem('cost', itemId, 'quantity', quantity, orderId);
            } else {
                if (window.ktpDebugMode) {
                    console.log('Cost quantity auto-save skipped - item not yet created or missing data');
                }
            }
        });

        // 単位フィールドのblurイベントで自動保存
        $(document).on('blur', '.cost-item-input.unit', function () {
            const $field = $(this);
            if ($field.prop('disabled')) return;

            const unit = $field.val();
            const $row = $field.closest('tr');
            const itemId = $row.find('input[name*="[id]"]').val();
            const orderId = $('input[name="order_id"]').val() || $('#order_id').val();

            if (window.ktpDebugMode) {
                console.log('Cost unit auto-save debug:', {
                    unit: unit,
                    itemId: itemId,
                    orderId: orderId,
                    hasNonce: typeof ktp_ajax_nonce !== 'undefined',
                    hasAjaxurl: typeof ajaxurl !== 'undefined'
                });
            }

            if (orderId && itemId && itemId !== '0') {
                autoSaveItem('cost', itemId, 'unit', unit, orderId);
            } else {
                if (window.ktpDebugMode) {
                    console.log('Cost unit auto-save skipped - item not yet created or missing data');
                }
            }
        });

        // 備考フィールドのblurイベントで自動保存
        $(document).on('blur', '.cost-item-input.remarks', function () {
            const $field = $(this);
            if ($field.prop('disabled')) return;

            const remarks = $field.val();
            const $row = $field.closest('tr');
            const itemId = $row.find('input[name*="[id]"]').val();
            const orderId = $('input[name="order_id"]').val() || $('#order_id').val();

            if (window.ktpDebugMode) {
                console.log('Cost remarks auto-save debug:', {
                    remarks: remarks,
                    itemId: itemId,
                    orderId: orderId,
                    hasNonce: typeof ktp_ajax_nonce !== 'undefined',
                    hasAjaxurl: typeof ajaxurl !== 'undefined'
                });
            }

            if (orderId && itemId && itemId !== '0') {
                autoSaveItem('cost', itemId, 'remarks', remarks, orderId);
            } else {
                if (window.ktpDebugMode) {
                    console.log('Cost remarks auto-save skipped - item not yet created or missing data');
                }
            }
        });

        // 初期ロード時に合計金額と利益を計算・表示
        updateProfitDisplay();

        console.log('[COST] 📋 ページ初期化完了');
    });
})(jQuery);
