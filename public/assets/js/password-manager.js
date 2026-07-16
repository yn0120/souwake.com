(function () {
    'use strict';

    var config = window.passwordManagerConfig || {};
    var itemTypes = config.itemTypes || {};
    var currentParams = {};
    var clipboard = null;
    var alertHideTimer = null;

    var listEl = document.getElementById('pwm-list');
    var loadingEl = document.getElementById('pwm-loading');
    var emptyEl = document.getElementById('pwm-empty');
    var alertEl = document.getElementById('pwm-alert');
    var alertMessageEl = document.getElementById('pwm-alert-message');

    var searchForm = document.getElementById('pwm-search-form');
    var searchClearBtn = document.getElementById('pwm-search-clear');

    var createToggleBtn = document.getElementById('pwm-create-toggle');
    var createPanel = document.getElementById('pwm-create-panel');
    var createForm = document.getElementById('pwm-create-form');
    var createCancelBtn = document.getElementById('pwm-create-cancel');
    var createItemsEl = document.getElementById('pwm-create-items');
    var createItemAddBtn = document.getElementById('pwm-create-item-add');

    function esc(value) {
        var div = document.createElement('div');
        div.textContent = value === null || value === undefined ? '' : String(value);

        return div.innerHTML;
    }

    function htmlInputType(type) {
        return (type === 'email' || type === 'password' || type === 'tel') ? type : 'text';
    }

    function buildValueFieldHtml(type, value, inputClass, placeholder) {
        var placeholderAttr = placeholder ? ' placeholder="' + esc(placeholder) + '"' : '';
        if (type === 'textarea') {
            return '<textarea class="form-control form-control-sm ' + inputClass + '" rows="3"' + placeholderAttr + '>' + esc(value || '') + '</textarea>';
        }

        return '<input type="' + htmlInputType(type) + '" class="form-control form-control-sm ' + inputClass + '" value="' + esc(value || '') + '"' + placeholderAttr + '>';
    }

    function showAlert(type, message) {
        alertEl.style.display = '';
        alertMessageEl.className = 'alert p-2 text-break shadow-sm alert-' + type;
        alertMessageEl.textContent = message;

        if (alertHideTimer) {
            clearTimeout(alertHideTimer);
        }
        alertHideTimer = setTimeout(function () {
            alertEl.style.display = 'none';
        }, 3000);
    }

    function urlFor(base, id, itemId) {
        var url = base.replace('__ID__', encodeURIComponent(id));
        if (itemId !== undefined) {
            url = url.replace('__ITEM_ID__', encodeURIComponent(itemId));
        }

        return url;
    }

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

    function itemTypeOptions(selectedType) {
        var html = '';
        Object.keys(itemTypes).forEach(function (key) {
            html += '<option value="' + esc(key) + '"' + (key === selectedType ? ' selected' : '') + '>' + esc(itemTypes[key]) + '</option>';
        });

        return html;
    }

    // --- 一覧取得・描画 ---

    function loadList(params) {
        currentParams = params || currentParams || {};
        loadingEl.style.display = '';
        emptyEl.style.display = 'none';
        listEl.innerHTML = '';

        var query = Object.keys(currentParams)
            .filter(function (key) { return currentParams[key]; })
            .map(function (key) { return encodeURIComponent(key) + '=' + encodeURIComponent(currentParams[key]); })
            .join('&');

        return apiFetch(config.listUrl + (query ? '?' + query : ''))
            .then(function (result) {
                loadingEl.style.display = 'none';
                if (!result.ok) {
                    showAlert('danger', '一覧の取得に失敗しました。');

                    return;
                }
                renderList(result.data.records || []);
            })
            .catch(function () {
                loadingEl.style.display = 'none';
                showAlert('danger', '一覧の取得に失敗しました。通信環境をご確認ください。');
            });
    }

    function renderList(records) {
        if (clipboard) {
            clipboard.destroy();
            clipboard = null;
        }

        if (!records.length) {
            emptyEl.style.display = '';
            listEl.innerHTML = '';

            return;
        }
        emptyEl.style.display = 'none';

        listEl.innerHTML = records.map(renderEntryCard).join('');
        bindEntryEvents();
        clipboard = new ClipboardJS('.pwm-copy-btn');
        clipboard.on('success', function () {
            showAlert('success', 'コピーしました。');
        });
    }

    function renderEntryCard(entry) {
        var itemsHtml = entry.items.map(function (item) { return renderItemRow(entry.id, item); }).join('');

        return ''
            + '<div class="pwm-entry-card p-3 mb-3" data-id="' + entry.id + '">'
            + '  <div class="d-flex justify-content-between align-items-start flex-wrap">'
            + '    <div class="pwm-entry-view">'
            + '      <div class="fw-bold fs-6 pwm-entry-name">' + esc(entry.name) + '</div>'
            + '      <div class="text-muted small">表示順: <span class="pwm-entry-order">' + esc(entry.display_order) + '</span></div>'
            + '    </div>'
            + '    <div class="pwm-entry-edit flex-grow-1" style="display:none; max-width: 260px;">'
            + '      <input type="text" class="form-control form-control-sm mb-2 pwm-entry-name-input" value="' + esc(entry.name) + '">'
            + '      <input type="number" class="form-control form-control-sm pwm-entry-order-input" value="' + esc(entry.display_order) + '">'
            + '    </div>'
            + '    <div class="ms-3 text-nowrap mt-2 mt-md-0">'
            + '      <button type="button" class="btn btn-sm btn-outline-warning pwm-entry-edit-btn">編集</button>'
            + '      <button type="button" class="btn btn-sm btn-outline-success pwm-entry-update-btn" style="display:none;">更新</button>'
            + '      <button type="button" class="btn btn-sm btn-outline-dark pwm-entry-cancel-btn" style="display:none;">キャンセル</button>'
            + '      <button type="button" class="btn btn-sm btn-outline-danger pwm-entry-delete-btn">削除</button>'
            + '    </div>'
            + '  </div>'
            + '  <div class="pwm-items mt-3">' + itemsHtml + '</div>'
            + '  <div class="pwm-item-add-form mt-2 border-top pt-2">'
            + '    <div class="row g-2 align-items-center">'
            + '      <div class="col-6 col-md-3"><input type="text" class="form-control form-control-sm pwm-new-item-label" placeholder="項目名"></div>'
            + '      <div class="col-6 col-md-2"><select class="form-select form-select-sm pwm-new-item-type">' + itemTypeOptions('text') + '</select></div>'
            + '      <div class="col-8 col-md-5 pwm-new-item-value-field">' + buildValueFieldHtml('text', '', 'pwm-new-item-value', '値') + '</div>'
            + '      <div class="col-4 col-md-2"><button type="button" class="btn btn-sm btn-outline-secondary w-100 pwm-item-add-btn">＋ 項目を追加</button></div>'
            + '    </div>'
            + '  </div>'
            + '</div>';
    }

    function renderItemValueView(item) {
        if (item.type === 'textarea') {
            return ''
                + '<div class="pwm-item-value-display" style="white-space: pre-wrap;">' + esc(item.value || '') + '</div>'
                + '<button type="button" class="btn btn-sm btn-link p-0 pwm-copy-btn" data-clipboard-text="' + esc(item.value || '') + '">コピー</button>';
        }

        return ''
            + '<span class="pwm-item-value-display">' + esc(item.value || '') + '</span> '
            + '<button type="button" class="btn btn-sm btn-link p-0 ms-1 pwm-copy-btn" data-clipboard-text="' + esc(item.value || '') + '">コピー</button>';
    }

    function renderItemRow(entryId, item) {
        var valueInput = buildValueFieldHtml(item.type, item.value, 'pwm-item-value-input');

        return ''
            + '<div class="pwm-item-row py-2" data-item-id="' + item.id + '" data-entry-id="' + entryId + '">'
            + '  <div class="row g-2 align-items-center pwm-item-view">'
            + '    <div class="col-12 col-md-3"><span class="fw-semibold">' + esc(item.label) + '</span></div>'
            + '    <div class="col-12 col-md-6">' + renderItemValueView(item) + '</div>'
            + '    <div class="col-12 col-md-3 text-md-end">'
            + '      <button type="button" class="btn btn-sm btn-outline-warning pwm-item-edit-btn">編集</button>'
            + '      <button type="button" class="btn btn-sm btn-outline-danger pwm-item-delete-btn">削除</button>'
            + '    </div>'
            + '  </div>'
            + '  <div class="row g-2 align-items-center pwm-item-edit" style="display:none;">'
            + '    <div class="col-12 col-md-3"><input type="text" class="form-control form-control-sm pwm-item-label-input" value="' + esc(item.label) + '"></div>'
            + '    <div class="col-12 col-md-2"><select class="form-select form-select-sm pwm-item-type-select">' + itemTypeOptions(item.type) + '</select></div>'
            + '    <div class="col-12 col-md-4 pwm-item-value-field">' + valueInput + '</div>'
            + '    <div class="col-12 col-md-3 text-md-end">'
            + '      <button type="button" class="btn btn-sm btn-outline-success pwm-item-update-btn">更新</button>'
            + '      <button type="button" class="btn btn-sm btn-outline-dark pwm-item-cancel-btn">キャンセル</button>'
            + '    </div>'
            + '  </div>'
            + '</div>';
    }

    // --- イベント紐付け ---

    function bindEntryEvents() {
        Array.prototype.forEach.call(listEl.querySelectorAll('.pwm-entry-card'), function (card) {
            var entryId = card.getAttribute('data-id');
            var viewEl = card.querySelector('.pwm-entry-view');
            var editEl = card.querySelector('.pwm-entry-edit');
            var editBtn = card.querySelector('.pwm-entry-edit-btn');
            var updateBtn = card.querySelector('.pwm-entry-update-btn');
            var cancelBtn = card.querySelector('.pwm-entry-cancel-btn');
            var deleteBtn = card.querySelector('.pwm-entry-delete-btn');

            editBtn.addEventListener('click', function () {
                viewEl.style.display = 'none';
                editEl.style.display = '';
                editBtn.style.display = 'none';
                updateBtn.style.display = '';
                cancelBtn.style.display = '';
            });

            cancelBtn.addEventListener('click', function () {
                viewEl.style.display = '';
                editEl.style.display = 'none';
                editBtn.style.display = '';
                updateBtn.style.display = 'none';
                cancelBtn.style.display = 'none';
            });

            updateBtn.addEventListener('click', function () {
                var name = card.querySelector('.pwm-entry-name-input').value.trim();
                var displayOrder = card.querySelector('.pwm-entry-order-input').value;
                if (!name) {
                    showAlert('danger', 'サイト名を入力してください。');

                    return;
                }
                apiFetch(urlFor(config.updateUrlBase, entryId), {
                    method: 'POST',
                    body: JSON.stringify({name: name, display_order: displayOrder}),
                }).then(function (result) {
                    if (!result.ok) {
                        showAlert('danger', (result.data && result.data.message) || '更新に失敗しました。');

                        return;
                    }
                    showAlert('success', '更新しました。');
                    loadList();
                });
            });

            deleteBtn.addEventListener('click', function () {
                if (!confirm('このサイトを削除しますか？（登録されている項目も全て削除されます）')) {
                    return;
                }
                apiFetch(urlFor(config.deleteUrlBase, entryId), {method: 'POST'}).then(function (result) {
                    if (!result.ok) {
                        showAlert('danger', (result.data && result.data.message) || '削除に失敗しました。');

                        return;
                    }
                    showAlert('success', '削除しました。');
                    loadList();
                });
            });

            var addLabelInput = card.querySelector('.pwm-new-item-label');
            var addTypeSelect = card.querySelector('.pwm-new-item-type');
            var addValueFieldWrap = card.querySelector('.pwm-new-item-value-field');
            var addBtn = card.querySelector('.pwm-item-add-btn');
            addTypeSelect.addEventListener('change', function () {
                var currentValueEl = card.querySelector('.pwm-new-item-value');
                var currentValue = currentValueEl ? currentValueEl.value : '';
                addValueFieldWrap.innerHTML = buildValueFieldHtml(addTypeSelect.value, currentValue, 'pwm-new-item-value', '値');
            });
            addBtn.addEventListener('click', function () {
                var label = addLabelInput.value.trim();
                if (!label) {
                    showAlert('danger', '項目名を入力してください。');

                    return;
                }
                apiFetch(urlFor(config.itemCreateUrlBase, entryId), {
                    method: 'POST',
                    body: JSON.stringify({
                        label: label,
                        type: addTypeSelect.value,
                        value: card.querySelector('.pwm-new-item-value').value,
                    }),
                }).then(function (result) {
                    if (!result.ok) {
                        showAlert('danger', (result.data && result.data.message) || '追加に失敗しました。');

                        return;
                    }
                    showAlert('success', '追加しました。');
                    loadList();
                });
            });

            Array.prototype.forEach.call(card.querySelectorAll('.pwm-item-row'), function (row) {
                bindItemRowEvents(entryId, row);
            });
        });
    }

    function bindItemRowEvents(entryId, row) {
        var itemId = row.getAttribute('data-item-id');
        var viewEl = row.querySelector('.pwm-item-view');
        var editEl = row.querySelector('.pwm-item-edit');
        var editBtn = row.querySelector('.pwm-item-edit-btn');
        var updateBtn = row.querySelector('.pwm-item-update-btn');
        var cancelBtn = row.querySelector('.pwm-item-cancel-btn');
        var deleteBtn = row.querySelector('.pwm-item-delete-btn');

        editBtn.addEventListener('click', function () {
            viewEl.style.display = 'none';
            editEl.style.display = '';
        });

        cancelBtn.addEventListener('click', function () {
            viewEl.style.display = '';
            editEl.style.display = 'none';
        });

        var itemTypeSelect = row.querySelector('.pwm-item-type-select');
        var itemValueFieldWrap = row.querySelector('.pwm-item-value-field');
        itemTypeSelect.addEventListener('change', function () {
            var currentValueEl = row.querySelector('.pwm-item-value-input');
            var currentValue = currentValueEl ? currentValueEl.value : '';
            itemValueFieldWrap.innerHTML = buildValueFieldHtml(itemTypeSelect.value, currentValue, 'pwm-item-value-input');
        });

        updateBtn.addEventListener('click', function () {
            var label = row.querySelector('.pwm-item-label-input').value.trim();
            var type = row.querySelector('.pwm-item-type-select').value;
            var value = row.querySelector('.pwm-item-value-input').value;
            if (!label) {
                showAlert('danger', '項目名を入力してください。');

                return;
            }
            apiFetch(urlFor(config.itemUpdateUrlBase, entryId, itemId), {
                method: 'POST',
                body: JSON.stringify({label: label, type: type, value: value}),
            }).then(function (result) {
                if (!result.ok) {
                    showAlert('danger', (result.data && result.data.message) || '更新に失敗しました。');

                    return;
                }
                showAlert('success', '更新しました。');
                loadList();
            });
        });

        deleteBtn.addEventListener('click', function () {
            if (!confirm('この項目を削除しますか？')) {
                return;
            }
            apiFetch(urlFor(config.itemDeleteUrlBase, entryId, itemId), {method: 'POST'}).then(function (result) {
                if (!result.ok) {
                    showAlert('danger', (result.data && result.data.message) || '削除に失敗しました。');

                    return;
                }
                showAlert('success', '削除しました。');
                loadList();
            });
        });
    }

    // --- 検索 ---

    searchForm.addEventListener('submit', function (e) {
        e.preventDefault();
        loadList({
            name: document.getElementById('pwm-search-name').value.trim(),
            keyword: document.getElementById('pwm-search-keyword').value.trim(),
            sort: document.getElementById('pwm-search-sort').value,
            direction: document.getElementById('pwm-search-direction').value,
        });
    });

    searchClearBtn.addEventListener('click', function () {
        searchForm.reset();
        loadList({});
    });

    // --- 新規登録パネル ---

    function newCreateItemRow() {
        var row = document.createElement('div');
        row.className = 'row g-2 align-items-center pwm-create-item-row mb-2';
        row.innerHTML = ''
            + '<div class="col-6 col-md-3"><input type="text" class="form-control form-control-sm pwm-create-item-label" placeholder="項目名（例: ログインID）"></div>'
            + '<div class="col-6 col-md-2"><select class="form-select form-select-sm pwm-create-item-type">' + itemTypeOptions('text') + '</select></div>'
            + '<div class="col-8 col-md-5 pwm-create-item-value-field">' + buildValueFieldHtml('text', '', 'pwm-create-item-value', '値') + '</div>'
            + '<div class="col-4 col-md-2"><button type="button" class="btn btn-sm btn-outline-danger w-100 pwm-create-item-remove">削除</button></div>';
        row.querySelector('.pwm-create-item-remove').addEventListener('click', function () {
            row.remove();
        });
        var createTypeSelect = row.querySelector('.pwm-create-item-type');
        var createValueFieldWrap = row.querySelector('.pwm-create-item-value-field');
        createTypeSelect.addEventListener('change', function () {
            var currentValueEl = row.querySelector('.pwm-create-item-value');
            var currentValue = currentValueEl ? currentValueEl.value : '';
            createValueFieldWrap.innerHTML = buildValueFieldHtml(createTypeSelect.value, currentValue, 'pwm-create-item-value', '値');
        });

        return row;
    }

    createToggleBtn.addEventListener('click', function () {
        var opening = createPanel.style.display === 'none';
        createPanel.style.display = opening ? '' : 'none';
        if (opening && !createItemsEl.children.length) {
            createItemsEl.appendChild(newCreateItemRow());
        }
    });

    createCancelBtn.addEventListener('click', function () {
        createPanel.style.display = 'none';
        createForm.reset();
        createItemsEl.innerHTML = '';
    });

    createItemAddBtn.addEventListener('click', function () {
        createItemsEl.appendChild(newCreateItemRow());
    });

    createForm.addEventListener('submit', function (e) {
        e.preventDefault();

        var name = document.getElementById('pwm-create-name').value.trim();
        if (!name) {
            showAlert('danger', 'サイト名を入力してください。');

            return;
        }

        var items = Array.prototype.map.call(createItemsEl.querySelectorAll('.pwm-create-item-row'), function (row) {
            return {
                label: row.querySelector('.pwm-create-item-label').value.trim(),
                type: row.querySelector('.pwm-create-item-type').value,
                value: row.querySelector('.pwm-create-item-value').value,
            };
        });

        apiFetch(config.createUrl, {
            method: 'POST',
            body: JSON.stringify({name: name, items: items}),
        }).then(function (result) {
            if (!result.ok) {
                showAlert('danger', (result.data && result.data.message) || '登録に失敗しました。');

                return;
            }
            showAlert('success', '登録しました。');
            createPanel.style.display = 'none';
            createForm.reset();
            createItemsEl.innerHTML = '';
            loadList();
        });
    });

    loadList({});
})();
