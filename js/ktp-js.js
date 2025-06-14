document.addEventListener('DOMContentLoaded', function () {
    console.log('KTPWP: DOM loaded, initializing toggle functionality');

    // HTMLエスケープ関数
    function escapeHtml(str) {
        if (!str) return '';
        return str.replace(/[&<>'"]/g, function (c) {
            return {'&':'&amp;','<':'&lt;','>':'&gt;','\'':'&#39;','"':'&quot;'}[c];
        });
    }

    // グローバルスコープに追加
    window.escapeHtml = escapeHtml;

    // スクロールタイマーを保存する変数（グローバルスコープ）
    window.scrollTimeouts = [];

    // スクロールタイマーをクリアする関数（グローバルスコープ）
    window.clearScrollTimeouts = function () {
        window.scrollTimeouts.forEach(function (timeout) {
            clearTimeout(timeout);
        });
        window.scrollTimeouts = [];
    };

    // 通知バッジを削除（グローバルスコープ）
    window.hideNewMessageNotification = function () {
        var toggleBtn = document.getElementById('staff-chat-toggle-btn');
        if (!toggleBtn) return;

        var badge = toggleBtn.querySelector('.staff-chat-notification-badge');
        if (badge) {
            badge.remove();
        }
    };

    // コスト項目トグル - 詳細デバッグ
    console.log('KTPWP: Searching for cost toggle elements...');

    // 全ての .toggle-cost-items 要素を検索
    var allToggleButtons = document.querySelectorAll('.toggle-cost-items');
    console.log('KTPWP: Found toggle buttons:', allToggleButtons.length, allToggleButtons);

    // 全ての cost-items-content 要素を検索
    var allCostContents = document.querySelectorAll('#cost-items-content');
    console.log('KTPWP: Found cost contents:', allCostContents.length, allCostContents);

    // ページ内の全ての要素を確認（デバッグ用）
    var allButtons = document.querySelectorAll('button');
    console.log('KTPWP: All buttons on page:', allButtons.length);

    var costToggleBtn = document.querySelector('.toggle-cost-items');
    var costContent = document.getElementById('cost-items-content');

    console.log('KTPWP: Cost toggle elements found:', {
        button: !!costToggleBtn,
        content: !!costContent,
        buttonElement: costToggleBtn,
        contentElement: costContent
    }); if (costToggleBtn && costContent) {
        setupCostToggle(costToggleBtn, costContent);
    } else {
        console.log('KTPWP: Cost toggle elements not found - button:', !!costToggleBtn, 'content:', !!costContent);

        // 要素が見つからない場合、少し待ってから再試行
        setTimeout(function () {
            console.log('KTPWP: Retrying to find cost toggle elements...');
            var retryToggleBtn = document.querySelector('.toggle-cost-items');
            var retryCostContent = document.getElementById('cost-items-content');

            console.log('KTPWP: Retry results:', {
                button: !!retryToggleBtn,
                content: !!retryCostContent
            });

            if (retryToggleBtn && retryCostContent) {
                console.log('KTPWP: Elements found on retry, setting up functionality...');
                setupCostToggle(retryToggleBtn, retryCostContent);
            } else {
                // HTML構造をデバッグ
                console.log('KTPWP: Page HTML structure debug:');
                var orderBoxes = document.querySelectorAll('.order_cost_box, .box');
                console.log('KTPWP: Found boxes:', orderBoxes.length, orderBoxes);

                var h4Elements = document.querySelectorAll('h4');
                console.log('KTPWP: Found h4 elements:', h4Elements.length);
                h4Elements.forEach(function (h4, index) {
                    console.log('KTPWP: H4 #' + index + ':', h4.textContent);
                });
            }
        }, 2000);
    }

    // スタッフチャットトグル機能をセットアップする関数
    function setupStaffChatToggle(toggleBtn, content) {
        console.log('KTPWP: Setting up staff chat toggle functionality');

        // URLパラメータでチャットを開く状態を確認
        var urlParams = new URLSearchParams(window.location.search);
        var chatShouldBeOpen = urlParams.get('chat_open') !== '0';
        var messageSent = urlParams.get('message_sent') === '1';
        var shouldOpenChat = chatShouldBeOpen;

        // 自動スクロール関数
        var scrollToBottom = function () {
            if (!content || content.style.display === 'none') {
                return;
            }

            if (toggleBtn && toggleBtn.getAttribute('aria-expanded') !== 'true') {
                return;
            }

            window.clearScrollTimeouts();

            // チャットセクションまでページをスクロール
            var chatSection = document.querySelector('.order_memo_box h4');
            if (chatSection && chatSection.textContent.includes('スタッフチャット')) {
                chatSection.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }

            // メッセージエリアのスクロール処理
            var scrollMessages = function () {
                var currentChatContent = document.getElementById('staff-chat-content');
                if (!currentChatContent || currentChatContent.style.display === 'none') {
                    return false;
                }

                var messagesContainer = document.getElementById('staff-chat-messages');
                if (messagesContainer) {
                    messagesContainer.scrollTop = messagesContainer.scrollHeight;
                    return true;
                } else {
                    if (currentChatContent) {
                        currentChatContent.scrollTop = currentChatContent.scrollHeight;
                        return true;
                    }
                }
                return false;
            };

            // 複数回試行してスクロール
            window.scrollTimeouts.push(setTimeout(function () {
                scrollMessages();
            }, 300));

            window.scrollTimeouts.push(setTimeout(function () {
                scrollMessages();
            }, 800));

            window.scrollTimeouts.push(setTimeout(function () {
                scrollMessages();
            }, 1500));
        };

        // 初期状態を設定
        if (shouldOpenChat) {
            content.style.display = 'block';
            toggleBtn.setAttribute('aria-expanded', 'true');

            if (messageSent) {
                scrollToBottom();

                // スクロール実行後、URLからパラメータを削除
                var newUrl = new URL(window.location);
                newUrl.searchParams.delete('message_sent');
                newUrl.searchParams.delete('chat_open');
                window.history.replaceState({}, '', newUrl);
            }
        } else {
            content.style.display = 'none';
            toggleBtn.setAttribute('aria-expanded', 'false');
        }

        // 項目数を取得してボタンテキストに追加
        var updateStaffChatButtonText = function () {
            var scrollableMessages = content.querySelectorAll('.staff-chat-message.scrollable');
            var messageCount = scrollableMessages.length || 0;

            var emptyMessage = content.querySelector('.staff-chat-empty');
            if (emptyMessage) {
                messageCount = 0;
            }

            var showLabel = toggleBtn.dataset.showLabel || window.ktpwpStaffChatShowLabel || '表示';
            var hideLabel = toggleBtn.dataset.hideLabel || window.ktpwpStaffChatHideLabel || '非表示';
            var isExpanded = toggleBtn.getAttribute('aria-expanded') === 'true';
            var buttonText = (isExpanded ? hideLabel : showLabel) + '（' + messageCount + 'メッセージ）';
            toggleBtn.textContent = buttonText;
            console.log('KTPWP: Staff chat button text updated to:', buttonText);
        };

        // グローバルスコープにも追加
        window.updateStaffChatButtonText = updateStaffChatButtonText;

        toggleBtn.addEventListener('click', function (e) {
            e.preventDefault();
            console.log('KTPWP: Staff chat toggle button clicked');

            var expanded = toggleBtn.getAttribute('aria-expanded') === 'true';
            if (expanded) {
                window.clearScrollTimeouts();
                content.style.display = 'none';
                toggleBtn.setAttribute('aria-expanded', 'false');
                console.log('KTPWP: Staff chat content hidden');
            } else {
                content.style.display = 'block';
                toggleBtn.setAttribute('aria-expanded', 'true');
                window.hideNewMessageNotification();
                console.log('KTPWP: Staff chat content shown');
            }
            updateStaffChatButtonText();
        });

        // 国際化ラベルを設定
        if (typeof window.ktpwpStaffChatShowLabel !== 'undefined') {
            toggleBtn.dataset.showLabel = window.ktpwpStaffChatShowLabel;
        }
        if (typeof window.ktpwpStaffChatHideLabel !== 'undefined') {
            toggleBtn.dataset.hideLabel = window.ktpwpStaffChatHideLabel;
        }

        // 初期状態のボタンテキストを設定
        updateStaffChatButtonText();

        // ページ読み込み完了後の処理
        if (shouldOpenChat && messageSent) {
            window.addEventListener('load', function () {
                setTimeout(function () {
                    scrollToBottom();

                    var newUrl = new URL(window.location);
                    newUrl.searchParams.delete('message_sent');
                    newUrl.searchParams.delete('chat_open');
                    window.history.replaceState({}, '', newUrl);
                }, 1000);
            });
        }

        // スタッフチャットフォームの送信処理を追加
        var chatForm = document.getElementById('staff-chat-form');
        if (chatForm) {
            chatForm.addEventListener('submit', function (e) {
                e.preventDefault(); // デフォルトのフォーム送信を防ぐ

                var messageInput = document.getElementById('staff-chat-input');
                var submitButton = document.getElementById('staff-chat-submit');
                var orderId = document.querySelector('input[name="staff_chat_order_id"]')?.value;

                if (!messageInput || messageInput.value.trim() === '') {
                    messageInput.focus();
                    return false;
                }

                if (!orderId) {
                    return false;
                }

                // 送信ボタンを一時的に無効化
                if (submitButton) {
                    submitButton.disabled = true;
                    submitButton.textContent = '送信中...';
                    submitButton.style.opacity = '0.6';
                }

                // メッセージ入力欄も一時的に無効化
                if (messageInput) {
                    messageInput.disabled = true;
                    messageInput.style.opacity = '0.6';
                }

                // AJAX でメッセージを送信
                var xhr = new XMLHttpRequest();
                var url = (typeof ktpwp_ajax !== 'undefined' && ktpwp_ajax.ajax_url) ? ktpwp_ajax.ajax_url :
                    (typeof ajaxurl !== 'undefined') ? ajaxurl :
                        window.location.origin + '/wp-admin/admin-ajax.php';
                var params = 'action=send_staff_chat_message&order_id=' + orderId + '&message=' + encodeURIComponent(messageInput.value.trim());

                // nonceを追加
                if (typeof ktpwp_ajax !== 'undefined' && ktpwp_ajax.nonces && ktpwp_ajax.nonces.staff_chat) {
                    params += '&_ajax_nonce=' + ktpwp_ajax.nonces.staff_chat;
                }

                xhr.open('POST', url, true);
                xhr.setRequestHeader('Content-type', 'application/x-www-form-urlencoded');

                // 送信パラメータをデバッグ出力
                console.log('[KTPWPスタッフチャット送信] url:', url);
                console.log('[KTPWPスタッフチャット送信] params:', params);
                console.log('[KTPWPスタッフチャット送信] order_id:', orderId, 'message:', messageInput.value.trim());
                if (typeof ktpwp_ajax !== 'undefined') {
                    console.log('[KTPWPスタッフチャット送信] ktpwp_ajax:', ktpwp_ajax);
                }

                xhr.onreadystatechange = function () {
                    if (xhr.readyState === 4) {
                        // 送信ボタンとメッセージ入力欄を復元
                        if (submitButton) {
                            submitButton.disabled = false;
                            submitButton.textContent = '送信';
                            submitButton.style.opacity = '1';
                        }
                        if (messageInput) {
                            messageInput.disabled = false;
                            messageInput.style.opacity = '1';
                        }

                        if (xhr.status === 200) {
                            try {
                                var response = JSON.parse(xhr.responseText);
                                if (response.success) {
                                    // メッセージをクリア
                                    var messageText = messageInput.value.trim();
                                    messageInput.value = '';
                                    updateSubmitButton();
                                    
                                    // 新しいメッセージを動的に追加
                                    var messagesContainer = document.getElementById('staff-chat-messages');
                                    if (messagesContainer) {
                                        var currentUser = (typeof ktpwp_ajax !== 'undefined' && ktpwp_ajax.current_user) ? 
                                            ktpwp_ajax.current_user : 'スタッフ';
                                        var currentTime = new Date().toLocaleString('ja-JP', {
                                            year: 'numeric',
                                            month: 'numeric', 
                                            day: 'numeric',
                                            hour: '2-digit',
                                            minute: '2-digit'
                                        });
                                        
                                        var messageDiv = document.createElement('div');
                                        messageDiv.className = 'staff-chat-message scrollable';
                                        messageDiv.innerHTML = 
                                            '<div class="staff-chat-message-header">' +
                                                '<span class="staff-chat-user-name">' + escapeHtml(currentUser) + '</span>' +
                                                '<span class="staff-chat-timestamp">' + escapeHtml(currentTime) + '</span>' +
                                            '</div>' +
                                            '<div class="staff-chat-message-content">' + escapeHtml(messageText).replace(/\n/g, '<br>') + '</div>';
                                        
                                        messagesContainer.appendChild(messageDiv);
                                        
                                        // 最下部にスクロール
                                        setTimeout(function() {
                                            messagesContainer.scrollTop = messagesContainer.scrollHeight;
                                        }, 100);
                                        
                                        // ボタンテキストを更新
                                        updateStaffChatButtonText();
                                    } else {
                                        // フォールバック：ページリロード
                                        localStorage.setItem('ktp_scroll_to_chat', 'true');
                                        window.location.reload();
                                    }
                                    
                                    return;
                                } else {
                                    alert('メッセージの送信に失敗しました: ' + (response.data || '不明なエラー'));
                                }
                            } catch (e) {
                                alert('JSON解析エラー: ' + e.message);
                            }
                        } else {
                            let msg = 'サーバーエラーが発生しました';
                            if (xhr.responseText) {
                                try {
                                    const resp = JSON.parse(xhr.responseText);
                                    if (resp && resp.data) msg += '\n' + resp.data;
                                } catch(e) {
                                    msg += '\n' + xhr.responseText;
                                }
                            }
                            alert(msg);
                            console.error('スタッフチャット送信エラー詳細:', xhr.responseText);
                        }
                    }
                };
                xhr.send(params);
            });
            
            // テキストエリアでのキーボード操作を追加
            var messageInput = document.getElementById('staff-chat-input');
            var submitButton = document.getElementById('staff-chat-submit');
            
            if (messageInput && submitButton) {
                // 送信ボタンの状態を更新する関数
                function updateSubmitButton() {
                    var hasContent = messageInput.value.trim().length > 0;
                    submitButton.disabled = !hasContent;
                }
                
                // 入力時にボタン状態を更新
                messageInput.addEventListener('input', updateSubmitButton);
                
                // キーボードショートカット
                messageInput.addEventListener('keydown', function (e) {
                    // Ctrl+Enter または Cmd+Enter で送信
                    if ((e.ctrlKey || e.metaKey) && e.key === 'Enter') {
                        if (!submitButton.disabled) {
                            e.preventDefault();
                            chatForm.dispatchEvent(new Event('submit', { bubbles: true, cancelable: true }));
                        }
                    }
                });
                
                // 初期状態を設定
                updateSubmitButton();
            }
        }

        console.log('KTPWP: Staff chat toggle setup complete');
    }

    // コスト項目トグル機能をセットアップする関数
    function setupCostToggle(toggleBtn, content) {
        console.log('KTPWP: Setting up cost toggle functionality');

        // 初期状態を非表示に設定
        content.style.display = 'none';
        toggleBtn.setAttribute('aria-expanded', 'false');

        // 項目数を取得してボタンテキストに追加
        var updateCostButtonText = function () {
            var itemCount = content.querySelectorAll('.cost-items-table tbody tr').length || 0;
            var showLabel = toggleBtn.dataset.showLabel || window.ktpwpCostShowLabel || '表示';
            var hideLabel = toggleBtn.dataset.hideLabel || window.ktpwpCostHideLabel || '非表示';
            var isExpanded = toggleBtn.getAttribute('aria-expanded') === 'true';
            var buttonText = (isExpanded ? hideLabel : showLabel) + '（' + itemCount + '項目）';
            toggleBtn.textContent = buttonText;
            console.log('KTPWP: Button text updated to:', buttonText);
        };

        toggleBtn.addEventListener('click', function (e) {
            e.preventDefault();
            console.log('KTPWP: Cost toggle button clicked');

            var expanded = toggleBtn.getAttribute('aria-expanded') === 'true';
            if (expanded) {
                content.style.display = 'none';
                toggleBtn.setAttribute('aria-expanded', 'false');
                console.log('KTPWP: Cost content hidden');
            } else {
                content.style.display = 'block';
                toggleBtn.setAttribute('aria-expanded', 'true');
                console.log('KTPWP: Cost content shown');
            }
            updateCostButtonText();
        });

        // 国際化ラベルを設定
        if (typeof window.ktpwpCostShowLabel !== 'undefined') {
            toggleBtn.dataset.showLabel = window.ktpwpCostShowLabel;
        }
        if (typeof window.ktpwpCostHideLabel !== 'undefined') {
            toggleBtn.dataset.hideLabel = window.ktpwpCostHideLabel;
        }

        // 初期状態のボタンテキストを設定
        updateCostButtonText();

        console.log('KTPWP: Cost toggle setup complete');
    }

    // スタッフチャットトグル
    var staffChatToggleBtn = document.querySelector('.toggle-staff-chat');
    var staffChatContent = document.getElementById('staff-chat-content');

    console.log('KTPWP: Searching for staff chat toggle elements...');
    console.log('KTPWP: Staff chat toggle button:', !!staffChatToggleBtn, staffChatToggleBtn);
    console.log('KTPWP: Staff chat content:', !!staffChatContent, staffChatContent);

    // ページ読み込み時にローカルストレージをチェックして自動スクロール
    if (localStorage.getItem('ktp_scroll_to_chat') === 'true') {
        localStorage.removeItem('ktp_scroll_to_chat');
        
        // チャットを開く
        if (staffChatContent && staffChatToggleBtn) {
            staffChatContent.style.display = 'block';
            staffChatToggleBtn.setAttribute('aria-expanded', 'true');
            
            // 自動スクロール実行
            setTimeout(function () {
                // チャットセクションまでページをスクロール
                var chatSection = document.querySelector('.staff-chat-title');
                if (!chatSection) {
                    chatSection = document.querySelector('.order_memo_box');
                }
                if (chatSection) {
                    chatSection.scrollIntoView({ behavior: 'smooth', block: 'start' });
                }
                
                // メッセージエリアを最下部にスクロール
                var messagesContainer = document.getElementById('staff-chat-messages');
                if (messagesContainer) {
                    messagesContainer.scrollTop = messagesContainer.scrollHeight;
                } else {
                    // fallback: staff-chat-contentをスクロール
                    if (staffChatContent) {
                        staffChatContent.scrollTop = staffChatContent.scrollHeight;
                    }
                }
            }, 500);
        }
    }

    if (staffChatToggleBtn && staffChatContent) {
        setupStaffChatToggle(staffChatToggleBtn, staffChatContent);
    } else {
        console.log('KTPWP: Staff chat toggle elements not found');

        // 要素が見つからない場合、少し待ってから再試行
        setTimeout(function () {
            console.log('KTPWP: Retrying to find staff chat toggle elements...');
            var retryStaffToggleBtn = document.querySelector('.toggle-staff-chat');
            var retryStaffContent = document.getElementById('staff-chat-content');

            if (retryStaffToggleBtn && retryStaffContent) {
                console.log('KTPWP: Staff chat elements found on retry');
                setupStaffChatToggle(retryStaffToggleBtn, retryStaffContent);
            }
        }, 2000);
    }

    console.log('KTPWP: Initialization complete');
});

// グローバル関数：スタッフチャットトグルをテスト
window.testStaffChatToggle = function () {
    console.log('=== スタッフチャットトグルテスト開始 ===');

    var staffToggleBtn = document.querySelector('.toggle-staff-chat');
    var staffContent = document.getElementById('staff-chat-content');

    console.log('トグルボタン:', staffToggleBtn);
    console.log('コンテンツ:', staffContent);

    if (!staffToggleBtn) {
        console.error('スタッフチャットトグルボタンが見つかりません');
        return false;
    }

    if (!staffContent) {
        console.error('スタッフチャットコンテンツが見つかりません');
        return false;
    }

    // 現在の状態を表示
    console.log('現在の状態:', {
        display: staffContent.style.display,
        ariaExpanded: staffToggleBtn.getAttribute('aria-expanded'),
        buttonText: staffToggleBtn.textContent
    });

    // クリックをシミュレート
    console.log('クリックをシミュレート...');
    staffToggleBtn.click();

    // クリック後の状態を表示
    console.log('クリック後の状態:', {
        display: staffContent.style.display,
        ariaExpanded: staffToggleBtn.getAttribute('aria-expanded'),
        buttonText: staffToggleBtn.textContent
    });

    console.log('=== テスト完了 ===');
    return true;
};

// グローバル関数：両方のトグルをテスト
window.testAllToggles = function () {
    console.log('=== 全トグル機能テスト開始 ===');

    var costResult = window.testCostToggle();
    var staffResult = window.testStaffChatToggle();

    console.log('テスト結果:');
    console.log('- コスト項目トグル:', costResult ? '成功' : '失敗');
    console.log('- スタッフチャットトグル:', staffResult ? '成功' : '失敗');

    if (costResult && staffResult) {
        window.showSuccessNotification('全てのトグル機能が正常に動作しています');
    } else {
        console.error('一部のトグル機能に問題があります');
    }

    console.log('=== 全テスト完了 ===');
    return costResult && staffResult;
};

// グローバル関数：コスト項目トグルをテスト
window.testCostToggle = function () {
    console.log('=== コスト項目トグルテスト開始 ===');

    var costToggleBtn = document.querySelector('.toggle-cost-items');
    var costContent = document.getElementById('cost-items-content');

    console.log('トグルボタン:', costToggleBtn);
    console.log('コンテンツ:', costContent);

    if (!costToggleBtn) {
        console.error('トグルボタンが見つかりません');
        return false;
    }

    if (!costContent) {
        console.error('コストコンテンツが見つかりません');
        return false;
    }

    // 現在の状態を表示
    console.log('現在の状態:', {
        display: costContent.style.display,
        ariaExpanded: costToggleBtn.getAttribute('aria-expanded'),
        buttonText: costToggleBtn.textContent
    });

    // クリックをシミュレート
    console.log('クリックをシミュレート...');
    costToggleBtn.click();

    // クリック後の状態を表示
    console.log('クリック後の状態:', {
        display: costContent.style.display,
        ariaExpanded: costToggleBtn.getAttribute('aria-expanded'),
        buttonText: costToggleBtn.textContent
    });

    console.log('=== テスト完了 ===');
    return true;
};

// 成功通知を表示する関数
window.showSuccessNotification = function (message) {
    var notification = document.createElement('div');
    notification.className = 'success-notification';
    notification.style.cssText = 'position: fixed; top: 20px; right: 20px; background: #28a745; color: white; padding: 10px 20px; border-radius: 5px; z-index: 10000; font-size: 14px;';
    notification.textContent = message;

    document.body.appendChild(notification);

    setTimeout(function () {
        if (notification.parentNode) {
            notification.remove();
        }
    }, 3000);
};
