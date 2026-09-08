import { createToast } from './toast';

(function () {
    'use strict';

    var config = window.budgetConfig || {};
    var isTransfer = config.layout === 'transfer';
    var showAlert = createToast('bdg-alert', 'bdg-alert-message');

    var entryForm = document.getElementById('bdg-entry-form');
    var occurredOnInput = document.getElementById('bdg-occurred-on');
    var memoInput = document.getElementById('bdg-memo');

    var spreadsheetForm = document.getElementById('bdg-spreadsheet-form');
    var spreadsheetUrlInput = document.getElementById('bdg-spreadsheet-url');

    function apiFetch(url, options) {
        options = options || {};
        var headers = {
            'X-CSRF-TOKEN': config.csrfToken,
            'X-Requested-With': 'XMLHttpRequest',
            'Accept': 'application/json',
        };
        if (options.body) {
            headers['Content-Type'] = 'application/json';
        }
        options.headers = headers;

        return fetch(url, options).then(function (res) {
            return res.json().catch(function () {
                return {};
            }).then(function (data) {
                return {ok: res.ok, status: res.status, data: data};
            });
        });
    }

    function digitsOnly(input) {
        input.addEventListener('input', function () {
            input.value = input.value.replace(/[^0-9]/g, '');
        });
    }

    // --- 数値限定入力 ---

    occurredOnInput.addEventListener('input', function () {
        occurredOnInput.value = occurredOnInput.value.replace(/[^0-9]/g, '').slice(0, 8);
    });

    // --- スプレッドシートURL設定 ---

    spreadsheetForm.addEventListener('submit', function (e) {
        e.preventDefault();

        apiFetch(config.spreadsheetUpdateUrl, {
            method: 'POST',
            body: JSON.stringify({url: spreadsheetUrlInput.value.trim()}),
        }).then(function (result) {
            if (!result.ok) {
                showAlert('danger', (result.data && result.data.message) || '保存に失敗しました。');

                return;
            }
            showAlert('success', '保存しました。');
        });
    });

    // --- 共有の入出金台帳レイアウト（admin id=2） ---

    if (isTransfer) {
        var typeSelect = document.getElementById('bdg-type');
        var contentInput = document.getElementById('bdg-content');
        var depositInput = document.getElementById('bdg-deposit');
        var withdrawalInput = document.getElementById('bdg-withdrawal');
        var memberSelect = document.getElementById('bdg-member');

        digitsOnly(depositInput);
        digitsOnly(withdrawalInput);

        var resetTransferForm = function () {
            occurredOnInput.value = config.today;
            typeSelect.selectedIndex = 0;
            contentInput.value = '';
            depositInput.value = '';
            withdrawalInput.value = '';
            memberSelect.selectedIndex = 0;
            memoInput.value = '';
        };

        resetTransferForm();

        entryForm.addEventListener('submit', function (e) {
            e.preventDefault();

            apiFetch(config.submitUrl, {
                method: 'POST',
                body: JSON.stringify({
                    occurred_on: occurredOnInput.value,
                    type: typeSelect.value,
                    content: contentInput.value.trim(),
                    deposit_amount: depositInput.value,
                    withdrawal_amount: withdrawalInput.value,
                    member: memberSelect.value,
                    memo: memoInput.value.trim(),
                }),
            }).then(function (result) {
                if (!result.ok) {
                    showAlert('danger', (result.data && result.data.message) || '登録に失敗しました。');

                    return;
                }
                showAlert('success', '保存しました。');
                resetTransferForm();
                occurredOnInput.focus();
            });
        });

        return;
    }

    // --- 通常の家計簿レイアウト ---

    var amountInput = document.getElementById('bdg-amount');
    var accountSelect = document.getElementById('bdg-account');
    var categorySelect = document.getElementById('bdg-category');

    digitsOnly(amountInput);

    // --- プルダウンの「＋ 追加」選択肢 ---

    function bindAddOption(selectEl, createUrl) {
        var previousValue = selectEl.value;

        selectEl.addEventListener('focus', function () {
            previousValue = selectEl.value;
        });

        selectEl.addEventListener('change', function () {
            if (selectEl.value !== '__add__') {
                previousValue = selectEl.value;

                return;
            }

            var name = window.prompt('追加する選択肢を入力してください');
            name = name ? name.trim() : '';
            if (!name) {
                selectEl.value = previousValue;

                return;
            }

            apiFetch(createUrl, {
                method: 'POST',
                body: JSON.stringify({name: name}),
            }).then(function (result) {
                if (!result.ok) {
                    showAlert('danger', (result.data && result.data.message) || '追加に失敗しました。');
                    selectEl.value = previousValue;

                    return;
                }

                var option = document.createElement('option');
                option.value = result.data.option.id;
                option.textContent = result.data.option.name;
                selectEl.insertBefore(option, selectEl.querySelector('option[value="__add__"]'));
                selectEl.value = String(result.data.option.id);
                previousValue = selectEl.value;
                showAlert('success', '追加しました。');
            });
        });
    }

    bindAddOption(accountSelect, config.accountCreateUrl);
    bindAddOption(categorySelect, config.categoryCreateUrl);

    // --- 入力値をデフォルトへリセット ---

    function resetForm() {
        occurredOnInput.value = config.today;
        amountInput.value = '';
        accountSelect.value = String(config.defaultAccountId);
        categorySelect.value = String(config.defaultCategoryId);
        memoInput.value = '';
    }

    resetForm();

    // --- 登録 ---

    entryForm.addEventListener('submit', function (e) {
        e.preventDefault();

        apiFetch(config.submitUrl, {
            method: 'POST',
            body: JSON.stringify({
                occurred_on: occurredOnInput.value,
                amount: amountInput.value,
                account_id: accountSelect.value,
                category_id: categorySelect.value,
                memo: memoInput.value.trim(),
            }),
        }).then(function (result) {
            if (!result.ok) {
                showAlert('danger', (result.data && result.data.message) || '登録に失敗しました。');

                return;
            }
            showAlert('success', '保存しました。');
            resetForm();
            occurredOnInput.focus();
        });
    });
})();
