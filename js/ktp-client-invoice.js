// KTP Client Invoice Script

jQuery(document).ready(function($) {
    console.log("[請求書発行] スクリプト読み込み確認");
    console.log("[請求書発行] ktpClientInvoice object:", typeof ktpClientInvoice, ktpClientInvoice);
    
    //
    // 請求書発行機能
    //
    (function() {
        if (typeof ktpClientInvoice === 'undefined') {
            console.error("[請求書発行] Localized script object 'ktpClientInvoice' not found.");
            console.error("[請求書発行] Available window objects:", Object.keys(window).filter(key => key.includes('ktp')));
            return;
        }

        console.log("[請求書発行] 初期化開始");

        // デザイン設定をグローバル変数として設定
        window.ktp_design_settings = ktpClientInvoice.design_settings;
        
        var ajaxurl = ktpClientInvoice.ajax_url;
        
        // フォールバック: ktpClientInvoiceが利用できない場合の代替手段
        if (!ajaxurl) {
            console.warn("[請求書発行] ktpClientInvoice.ajax_url が利用できません。代替手段を試行します。");
            if (typeof ktp_ajax_object !== 'undefined' && ktp_ajax_object.ajax_url) {
                ajaxurl = ktp_ajax_object.ajax_url;
                console.log("[請求書発行] ktp_ajax_object から AJAX URL を取得:", ajaxurl);
            } else if (typeof ktpwp_ajax !== 'undefined' && ktpwp_ajax.ajax_url) {
                ajaxurl = ktpwp_ajax.ajax_url;
                console.log("[請求書発行] ktpwp_ajax から AJAX URL を取得:", ajaxurl);
            } else if (typeof window.ajaxurl !== 'undefined') {
                ajaxurl = window.ajaxurl;
                console.log("[請求書発行] window.ajaxurl から AJAX URL を取得:", ajaxurl);
            } else {
                ajaxurl = '/wp-admin/admin-ajax.php';
                console.warn("[請求書発行] デフォルトの AJAX URL を使用:", ajaxurl);
            }
        }
        
        console.log("[請求書発行] AJAX URL:", ajaxurl);
        console.log("[請求書発行] Nonce:", ktpClientInvoice.nonce);

        var invoiceButton = document.getElementById("invoiceButton");
        var popup = document.getElementById("invoicePopup");
        var list = document.getElementById("invoiceList");

        console.log("[請求書発行] 要素確認:", {
            invoiceButton: !!invoiceButton,
            popup: !!popup,
            list: !!list
        });

        if (invoiceButton && popup && list) {
            invoiceButton.addEventListener("click", function() {
                console.log("[請求書発行] ボタンがクリックされました");
                popup.style.display = "block";
                
                var xhr = new XMLHttpRequest();
                xhr.open("POST", ajaxurl, true);
                xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
                xhr.onload = function() {
                    console.log("[請求書発行] Ajaxレスポンス受信:", xhr.status, xhr.responseText);
                    if (xhr.status === 200) {
                        try {
                            var res = JSON.parse(xhr.responseText);
                            console.log("[請求書発行] レスポンス解析結果:", res);
                            if (res.success && res.data && res.data.monthly_groups && res.data.monthly_groups.length > 0) {
                                var html = "<div style=\"margin-bottom:20px;font-size:12px;\">";

                                // 部署選択がある場合の宛先表示を修正
                                var address = res.data.client_address || "";
                                var postalCode = "";
                                var addressWithoutPostal = address;
                                var companyName = res.data.client_name || "未設定";
                                var contactDisplay = res.data.client_contact || "";

                                // 部署選択がある場合
                                if (res.data.selected_department) {
                                    // 会社名を表示
                                    html += "<div style=\"margin-bottom:5px;\">" + companyName + "</div>";
                                    // 部署名を表示
                                    html += "<div style=\"margin-bottom:5px;\">" + res.data.selected_department.department_name + "</div>";
                                    // 担当者名を表示
                                    html += "<div style=\"margin-bottom:5px;\">" + res.data.selected_department.contact_person + " 様</div>";
                                } else {
                                    // 部署選択がない場合：現行のまま
                                    if (address.startsWith("〒")) {
                                        var postalMatch = address.match(/〒(\d{3}-?\d{4})/);
                                        if (postalMatch) {
                                            postalCode = "〒" + postalMatch[1];
                                            addressWithoutPostal = address.replace(/〒\d{3}-?\d{4}\s*/, "");
                                        }
                                    }

                                    // 住所情報が設定されていない場合は「未設定」は表示しない
                                    if (address && address.trim() !== "" && address !== "未設定") {
                                        if (postalCode) {
                                            html += "<div style=\"margin-bottom:5px;\">" + postalCode + "</div>";
                                        }
                                        html += "<div style=\"margin-bottom:5px;\">" + addressWithoutPostal + "</div>";
                                    }
                                    html += "<div style=\"margin-bottom:5px;\">" + companyName + "</div>";

                                    if (contactDisplay && contactDisplay.trim() !== "" && contactDisplay !== "未設定") {
                                        contactDisplay += " 様";
                                        html += "<div style=\"margin-bottom:5px;\">" + contactDisplay + "</div>";
                                    }
                                }
                                html += "</div>";

                                html += "<div style=\"margin:100px 0 20px 0;padding:15px;border:2px solid #333;border-radius:8px;background-color:#f9f9f9;text-align:center;\">";
                                html += "<div style=\"font-size:18px;font-weight:bold;color:#333;\">請求書</div>";
                                html += "</div>";

                                html += "<div style=\"margin:20px 0;padding:10px;font-size:14px;line-height:1.6;color:#333;\">";
                                html += "平素より大変お世話になっております。下記の通りご請求申し上げます。";
                                html += "</div>";

                                var grandTotal = 0;
                                res.data.monthly_groups.forEach(function(group) {
                                    var monthlyTotal = 0;
                                    group.orders.forEach(function(order) {
                                        var orderSubtotal = 0;
                                        if (order.items && order.items.length > 0) {
                                            order.items.forEach(function(item) {
                                                if (item.total_price) {
                                                    orderSubtotal += parseFloat(item.total_price);
                                                }
                                            });
                                        }
                                        monthlyTotal += orderSubtotal;
                                    });
                                    grandTotal += monthlyTotal;
                                });

                                // 合計金額・繰越金額を1行で横並び
                                html += "<div style=\"font-weight:bold;font-size:18px;color:#333;display:flex;align-items:center;margin:20px 0 0 0;\">";
                                html += "<span>合計金額&nbsp;" + grandTotal.toLocaleString() + "円</span>";
                                html += "<span style=\"font-size:16px;margin-left:20px;\">繰越金額：</span>";
                                html += "<input type=\"number\" id=\"carryover-amount\" name=\"carryover_amount\" value=\"0\" min=\"0\" step=\"1\" style=\"width:120px;padding:4px 8px;border:1px solid #ccc;border-radius:4px;font-size:16px;text-align:right;margin-left:5px;\" onchange=\"updateInvoiceTotal()\">";
                                html += "<span style=\"font-size:16px;\">円</span>";
                                html += "</div>";
                                // 請求金額・お支払い期日を1行で横並び
                                var paymentDueDate = (res.data.monthly_groups && res.data.monthly_groups.length > 0 && res.data.monthly_groups[0].payment_due_date) ? res.data.monthly_groups[0].payment_due_date : '';
                                html += "<div style=\"font-weight:bold;font-size:20px;color:#0073aa;display:flex;align-items:center;margin:10px 0 0 0;\">";
                                html += "<span>請求金額：<span id=\"total-amount\">" + grandTotal.toLocaleString() + "</span>円</span>";
                                html += "<span style=\"margin-left:2em;font-size:16px;\">お支払い期日：<input type=\"date\" id=\"payment-due-date-input\" value=\"" + paymentDueDate + "\" style=\"font-size:16px;padding:4px 8px;border:1px solid #ccc;border-radius:4px;width:180px;max-width:100%;\"></span>";
                                html += "</div>";

                                window.invoiceGrandTotal = grandTotal;

                                res.data.monthly_groups.forEach(function(group) {
                                    html += "<div style=\"margin:20px 0 10px 0;padding:8px 12px;background-color:#f0f8ff;border-left:4px solid #0073aa;border-radius:4px;\">";
                                    html += "<div style=\"font-weight:bold;color:#0073aa;font-size:14px;\">";
                                    html += "【" + group.billing_period + "】締日：" + group.closing_date + "　案件数：" + group.orders.length + "件";
                                    html += "</div>";
                                    html += "</div>";

                                    var monthlyTotal = 0;

                                    group.orders.forEach(function(order) {
                                        var orderSubtotal = 0;
                                        html += "<div style=\"padding:10px;border-bottom:1px solid #eee;\">";
                                        html += "<div style=\"font-weight:bold;margin-bottom:8px;color:#333;font-size:12px;\">";
                                        html += "ID: " + order.id + " - " + order.project_name + "（完了日：" + order.completion_date + "）";
                                        html += "</div>";

                                        if (order.items && order.items.length > 0) {
                                            html += "<div style=\"margin-top:10px;width:100%;\">";
                                            html += "<div style=\"display: flex; background: #f0f0f0; padding: 8px; font-weight: bold; border-bottom: 1px solid #ccc; align-items: center; font-size: 12px;\">";
                                            html += "<div style=\"width: 30px; text-align: center;\">No.</div>";
                                            html += "<div style=\"flex: 1; text-align: left; margin-left: 8px;\">サービス</div>";
                                            html += "<div style=\"width: 80px; text-align: right;\">単価</div>";
                                            html += "<div style=\"width: 60px; text-align: right;\">数量/単位</div>";
                                            html += "<div style=\"width: 80px; text-align: right;\">金額</div>";
                                            html += "<div style=\"width: 100px; text-align: left; margin-left: 8px;\">備考</div>";
                                            html += "</div>";

                                            var oddRowColor = window.ktp_design_settings.odd_row_color || "#E7EEFD";
                                            var evenRowColor = window.ktp_design_settings.even_row_color || "#FFFFFF";

                                            order.items.forEach(function(item, index) {
                                                var unitPrice = item.unit_price ? parseFloat(item.unit_price).toLocaleString() + "円" : "-";
                                                var quantity = item.quantity ? item.quantity : "-";
                                                var totalPrice = item.total_price ? parseFloat(item.total_price).toLocaleString() + "円" : "-";

                                                if (item.total_price) {
                                                    orderSubtotal += parseFloat(item.total_price);
                                                }
                                                var bgColor = (index % 2 === 0) ? evenRowColor : oddRowColor;
                                                html += "<div style=\"display: flex; padding: 6px 8px; height: 24px; background: " + bgColor + "; align-items: center; font-size: 12px;\">";
                                                html += "<div style=\"width: 30px; text-align: center;\">" + (index + 1) + "</div>";
                                                html += "<div style=\"flex: 1; text-align: left; margin-left: 8px;\">" + item.item_name + "</div>";
                                                html += "<div style=\"width: 80px; text-align: right;\">" + unitPrice + "</div>";
                                                html += "<div style=\"width: 60px; text-align: right;\">" + quantity + "/式</div>";
                                                html += "<div style=\"width: 80px; text-align: right;\">" + totalPrice + "</div>";
                                                html += "<div style=\"width: 100px; text-align: left; margin-left: 8px;\"></div>";
                                                html += "</div>";
                                            });

                                            html += "</div>";
                                            html += "<div style=\"margin-top:10px;text-align:right;font-weight:bold;font-size:13px;color:#333;\">";
                                            html += "小計：" + orderSubtotal.toLocaleString() + "円";
                                            html += "</div>";
                                        } else {
                                            html += "<div style=\"color:#999;font-size:12px;\">請求項目なし</div>";
                                        }
                                        monthlyTotal += orderSubtotal;
                                        html += "</div>";
                                    });

                                    html += "<div style=\"margin:15px 0;padding:12px;background-color:#f8f9fa;border:2px solid #0073aa;border-radius:6px;text-align:right;\">";
                                    html += "<div style=\"font-weight:bold;font-size:15px;color:#0073aa;\">";
                                    html += group.billing_period + " 合計：" + monthlyTotal.toLocaleString() + "円";
                                    html += "</div>";
                                    html += "</div>";
                                });

                                if (res.data.company_info) {
                                    html += "<div style=\"margin-top:30px;padding:20px;border:1px solid #ddd;background:#fafafa;text-align:right;border-radius:6px;\">";
                                    html += res.data.company_info;
                                    html += "</div>";
                                }

                                // 印刷・PDF保存ボタンの上にチェックボックスを追加
                                html += '<div style="margin-top:20px;text-align:center;">';
                                html += '<label style="display:inline-flex;align-items:center;font-size:15px;font-weight:500;margin-bottom:12px;">';
                                html += '<input type="checkbox" id="set-invoice-completed" style="width:18px;height:18px;margin-right:8px;">';
                                html += '対象受注書の進捗を「請求済」に変更する';
                                html += '</label><br />';
                                html += '<button onclick="printInvoiceContent()" style="background-color:#0073aa;color:white;border:none;padding:10px 20px;border-radius:5px;cursor:pointer;font-size:14px;font-weight:500;">';
                                html += '<span class="material-symbols-outlined" style="font-size:16px;vertical-align:middle;margin-right:5px;">print</span>';
                                html += '印刷 PDF保存';
                                html += '</button>';
                                html += '</div>';

                                list.innerHTML = html;
                            } else {
                                list.innerHTML = "<div style=\"color:#888;\">該当する案件はありません。</div>";
                            }
                        } catch (e) {
                            console.error("[請求書発行] JSON解析エラー:", e);
                            list.innerHTML = "<div style=\"color:#c00;\">データ取得エラー: " + e.message + "</div>";
                        }
                    } else {
                        console.error("[請求書発行] HTTPエラー:", xhr.status, xhr.statusText);
                        list.innerHTML = "<div style=\"color:#c00;\">通信エラー (HTTP " + xhr.status + "): " + xhr.statusText + "</div>";
                    }
                };
                var clientId = "";
                var urlParams = new URLSearchParams(window.location.search);
                clientId = urlParams.get("data_id");
                console.log("[請求書発行] URLパラメータから顧客ID:", clientId);

                if (!clientId) {
                    var clientIdInput = document.getElementById("client-id-input");
                    if (clientIdInput) {
                        clientId = clientIdInput.value;
                        console.log("[請求書発行] フォームから顧客ID:", clientId);
                    }
                }

                if (!clientId) {
                    var hiddenClientId = document.querySelector("input[name=\"data_id\"]");
                    if (hiddenClientId) {
                        clientId = hiddenClientId.value;
                        console.log("[請求書発行] 隠しフィールドから顧客ID:", clientId);
                    }
                }

                if (!clientId) {
                    console.error("[請求書発行] 顧客IDが見つかりません");
                    list.innerHTML = "<div style=\"color:#c00;\">顧客IDが見つかりません。</div>";
                    return;
                }

                console.log("[請求書発行] 最終的な顧客ID:", clientId);
                var nonce = ktpClientInvoice.nonce;
                
                // フォールバック: nonceが利用できない場合の代替手段
                if (!nonce) {
                    console.warn("[請求書発行] ktpClientInvoice.nonce が利用できません。代替手段を試行します。");
                    if (typeof ktp_ajax_object !== 'undefined' && ktp_ajax_object.nonce) {
                        nonce = ktp_ajax_object.nonce;
                        console.log("[請求書発行] ktp_ajax_object から nonce を取得");
                    } else if (typeof ktpwp_ajax !== 'undefined' && ktpwp_ajax.nonces && ktpwp_ajax.nonces.invoice_candidates) {
                        nonce = ktpwp_ajax.nonces.invoice_candidates;
                        console.log("[請求書発行] ktpwp_ajax から nonce を取得");
                    } else if (typeof window.ktpwp_ajax_nonce !== 'undefined') {
                        nonce = window.ktpwp_ajax_nonce;
                        console.log("[請求書発行] window.ktpwp_ajax_nonce から nonce を取得");
                    } else {
                        console.error("[請求書発行] nonce が見つかりません。AJAXリクエストを中止します。");
                        list.innerHTML = "<div style=\"color:#c00;\">セキュリティエラー: nonceが見つかりません。</div>";
                        return;
                    }
                }
                
                var params = "action=ktp_get_invoice_candidates&client_id=" + encodeURIComponent(clientId) + "&_wpnonce=" + encodeURIComponent(nonce);
                console.log("[請求書発行] 送信パラメータ:", params);
                xhr.send(params);
            });
        } else {
            console.error("[請求書発行] 必要な要素が見つかりません:", {
                invoiceButton: !!invoiceButton,
                popup: !!popup,
                list: !!list
            });
        }
    })();
});

function printInvoiceContent() {
    // チェックボックスの状態を確認
    var setInvoiceCompleted = document.getElementById('set-invoice-completed');
    var shouldSetCompleted = false;
    if (setInvoiceCompleted && setInvoiceCompleted.checked) {
        var confirmed = window.confirm('本当に対象受注書の進捗を「請求済」に変更しますか？\nこの操作は取り消せません。\nOKで印刷を続行、キャンセルで中止します。');
        if (!confirmed) {
            return; // キャンセル時は何もしない
        }
        shouldSetCompleted = true;
    }
    try {
        console.log("[請求書印刷] 印刷開始");

        var invoiceList = document.getElementById('invoiceList');
        if (!invoiceList) {
            console.error("[請求書印刷] invoiceList要素が見つかりません");
            alert("印刷エラー：請求書データが見つかりません");
            return;
        }

        var invoiceContent = invoiceList.innerHTML;
        if (!invoiceContent || invoiceContent.trim() === "") {
            console.error("[請求書印刷] 請求書の内容が空です");
            alert("印刷エラー：請求書の内容が空です");
            return;
        }

        console.log("[請求書印刷] 請求書内容取得完了");

        // デザイン設定を取得
        var designSettings = window.ktp_design_settings || {};
        var oddRowColor = designSettings.odd_row_color || "#E7EEFD";
        var evenRowColor = designSettings.even_row_color || "#FFFFFF";
        
        console.log("[請求書印刷] デザイン設定:", {
            oddRowColor: oddRowColor,
            evenRowColor: evenRowColor
        });

        var carryoverAmount = window.carryoverAmount || 0;
        var carryoverInput = document.getElementById('carryover-amount');
        if (carryoverInput) {
            carryoverAmount = parseInt(carryoverInput.value) || 0;
            console.log("[請求書印刷] 繰越金額:", carryoverAmount);
        }

        // 繰越金額入力フィールドを非表示にし、印刷用のspanに置き換える
        var tempDiv = document.createElement('div');
        tempDiv.innerHTML = invoiceContent;
        var carryoverInputInContent = tempDiv.querySelector('#carryover-amount');
        if(carryoverInputInContent) {
            var carryoverSpan = document.createElement('span');
            carryoverSpan.style.fontWeight = 'bold';
            carryoverSpan.textContent = carryoverAmount.toLocaleString();
            carryoverInputInContent.parentNode.replaceChild(carryoverSpan, carryoverInputInContent);
        }

        // チェックボックスとラベルを印刷用HTMLから除去
        var invoiceCompletedInput = tempDiv.querySelector('#set-invoice-completed');
        if (invoiceCompletedInput) {
            // 親labelごと削除
            var label = invoiceCompletedInput.closest('label');
            if (label && label.parentNode) {
                // labelの直後の<br>も削除
                var next = label.nextSibling;
                if (next && next.nodeName === 'BR') {
                    next.parentNode.removeChild(next);
                }
                label.parentNode.removeChild(label);
            } else {
                invoiceCompletedInput.parentNode.removeChild(invoiceCompletedInput);
            }
        }

        // お支払い期日inputをテキストに置き換え
        var paymentDueDateInputInContent = tempDiv.querySelector('#payment-due-date-input');
        if (paymentDueDateInputInContent) {
            // 最新の値を取得（元のDOMから）
            var liveInput = document.getElementById('payment-due-date-input');
            var paymentDueDateValue = liveInput ? liveInput.value : paymentDueDateInputInContent.value;
            // 日付を「YYYY/MM/DD」形式に整形
            var formattedDate = paymentDueDateValue ? paymentDueDateValue.replace(/-/g, "/") : "";
            var paymentDueDateSpan = document.createElement('span');
            paymentDueDateSpan.style.fontWeight = 'bold';
            paymentDueDateSpan.textContent = formattedDate;
            paymentDueDateInputInContent.parentNode.replaceChild(paymentDueDateSpan, paymentDueDateInputInContent);
        }

        // 合計金額を更新
        if (window.invoiceGrandTotal) {
            var totalAmount = window.invoiceGrandTotal + carryoverAmount;
            var totalAmountElement = tempDiv.querySelector('#total-amount');
            if(totalAmountElement) {
                totalAmountElement.textContent = totalAmount.toLocaleString();
            }
            console.log("[請求書印刷] 合計金額更新:", totalAmount);
        }

        // 印刷用にデザイン設定を適用
        var rows = tempDiv.querySelectorAll('[style*="background"]');
        rows.forEach(function(row, index) {
            if (row.style.background && (row.style.background.includes('#E7EEFD') || row.style.background.includes('#FFFFFF'))) {
                var bgColor = (index % 2 === 0) ? evenRowColor : oddRowColor;
                row.style.background = bgColor;
                console.log("[請求書印刷] 行の色を更新:", index, bgColor);
            }
        });

        invoiceContent = tempDiv.innerHTML;

        // 印刷用のスタイルを適用したHTMLを生成
        var printHTML = '<!DOCTYPE html>';
        printHTML += '<html lang="ja">';
        printHTML += '<head>';
        printHTML += '<meta charset="UTF-8">';
        printHTML += '<meta name="viewport" content="width=device-width, initial-scale=1.0">';
        printHTML += '<title>請求書</title>';
        printHTML += '<style>';
        printHTML += '* { margin: 0; padding: 0; box-sizing: border-box; }';
        printHTML += 'body { font-family: "Noto Sans JP", "Hiragino Kaku Gothic ProN", "Yu Gothic", Meiryo, sans-serif; font-size: 12px; line-height: 1.4; color: #333; background: white; padding: 20px; -webkit-print-color-adjust: exact; print-color-adjust: exact; }';
        printHTML += '.page-container { width: 210mm; max-width: 210mm; margin: 0 auto; background: white; padding: 50px; }';
        printHTML += '@page { size: A4; margin: 50px; }';
        printHTML += '@media print { body { margin: 0; padding: 0; background: white; } .page-container { box-shadow: none; margin: 0; padding: 0; width: auto; max-width: none; } }';
        printHTML += '@media print { button, .no-print { display: none !important; } }';
        printHTML += 'h1, h2, h3, h4, h5, h6 { font-weight: bold; }';
        printHTML += '* { -webkit-print-color-adjust: exact !important; color-adjust: exact !important; print-color-adjust: exact !important; }';
        printHTML += '</style>';
        printHTML += '</head>';
        printHTML += '<body>';
        printHTML += '<div class="page-container">';
        printHTML += invoiceContent;
        printHTML += '</div>';
        printHTML += '</body>';
        printHTML += '</html>';

        console.log("[請求書印刷] 印刷HTML生成完了");
        
        // ファイル名生成
        var clientId = '';
        var clientName = '';
        var monthLabels = [];
        // 顧客ID
        var urlParams = new URLSearchParams(window.location.search);
        clientId = urlParams.get('data_id');
        if (!clientId) {
            var clientIdInput = document.getElementById('client-id-input');
            if (clientIdInput) {
                clientId = clientIdInput.value;
            }
        }
        // 顧客名
        var clientNameElem = document.querySelector('#invoiceList div[style*="margin-bottom:5px;"]:nth-child(3)');
        if (clientNameElem) {
            clientName = clientNameElem.textContent.replace(/\s*様?$/, '');
        }
        // 月分（複数対応）
        var monthElems = document.querySelectorAll('#invoiceList div[style*="font-weight:bold;color:#0073aa;font-size:14px;"]');
        monthElems.forEach(function(elem) {
            var match = elem.textContent.match(/\d{4}年\d{1,2}月分/);
            if (match) {
                monthLabels.push(match[0]);
            }
        });
        var monthLabel = '';
        if (monthLabels.length > 0) {
            monthLabel = monthLabels.join('_');
        }
        var filename = '請求書';
        if (clientId) filename += '：' + clientId;
        if (clientName) filename += '：' + clientName + '様';
        if (monthLabel) filename += '（' + monthLabel + '）';
        filename += '.pdf';

        // 印刷用のiframeを作成（非表示）
        var printFrame = document.createElement('iframe');
        printFrame.style.position = 'fixed';
        printFrame.style.top = '-9999px';
        printFrame.style.left = '-9999px';
        printFrame.style.width = '210mm';
        printFrame.style.height = '297mm';
        printFrame.style.border = 'none';
        document.body.appendChild(printFrame);
        
        // iframeにHTMLを書き込み
        printFrame.contentDocument.open();
        printFrame.contentDocument.write(printHTML);
        printFrame.contentDocument.close();
        
        // ファイル名をwindowにセット（PDF保存時に参照される場合用）
        printFrame.contentWindow.document.title = filename;
        
        // iframeの読み込み完了を待ってから印刷
        printFrame.onload = function() {
            setTimeout(function() {
                try {
                    printFrame.contentWindow.print();
                    console.log("[請求書印刷] 印刷ダイアログを開きました");
                    
                    // 印刷完了後にiframeを削除
                    setTimeout(function() {
                        if (printFrame && printFrame.parentNode) {
                            printFrame.parentNode.removeChild(printFrame);
                        }
                    }, 1000);

                    // 印刷完了後に進捗変更Ajax
                    if (shouldSetCompleted) {
                        // ここでAjaxリクエストを送信（ダミー実装）
                        var xhr = new XMLHttpRequest();
                        xhr.open('POST', '/wp-admin/admin-ajax.php');
                        xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
                        // 必要に応じて対象IDやnonceをセット
                        var clientId = '';
                        var urlParams = new URLSearchParams(window.location.search);
                        clientId = urlParams.get('data_id');
                        if (!clientId) {
                            var clientIdInput = document.getElementById('client-id-input');
                            if (clientIdInput) {
                                clientId = clientIdInput.value;
                            }
                        }
                        var params = 'action=ktp_set_invoice_completed&client_id=' + encodeURIComponent(clientId);
                        xhr.onload = function() {
                            if (xhr.status === 200) {
                                console.log('[進捗変更] 請求済みへの変更成功:', xhr.responseText);
                            } else {
                                console.error('[進捗変更] エラー:', xhr.status, xhr.statusText);
                            }
                        };
                        xhr.send(params);
                    }
                } catch (error) {
                    console.error("[請求書印刷] 印刷実行エラー:", error);
                    alert("印刷エラーが発生しました: " + error.message);
                    
                    // エラー時もiframeを削除
                    if (printFrame && printFrame.parentNode) {
                        printFrame.parentNode.removeChild(printFrame);
                    }
                }
            }, 100);
        };

    } catch (error) {
        console.error("[請求書印刷] エラーが発生しました:", error);
        alert("印刷エラーが発生しました: " + error.message);
    }
}


function updateInvoiceTotal() {
    var carryoverAmount = parseInt(document.getElementById("carryover-amount").value) || 0;
    var grandTotal = window.invoiceGrandTotal || 0;
    var totalAmount = grandTotal + carryoverAmount;
    var totalAmountElement = document.getElementById("total-amount");
    if (totalAmountElement) {
        totalAmountElement.textContent = totalAmount.toLocaleString();
    }
    window.carryoverAmount = carryoverAmount;
}

// 入力変更時に値を即時反映（例：印刷時や他の参照用にwindow.paymentDueDateを更新）
setTimeout(function() {
    var paymentDueDateInput = document.getElementById('payment-due-date-input');
    if (paymentDueDateInput) {
        window.paymentDueDate = paymentDueDateInput.value;
        paymentDueDateInput.addEventListener('change', function() {
            window.paymentDueDate = paymentDueDateInput.value;
        });
    }
}, 100); 