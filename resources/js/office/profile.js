import { createToast } from './toast';

(function () {
    'use strict';

    var config = window.profileConfig || {};
    var showAlert = createToast('prf-alert', 'prf-alert-message');

    var form = document.getElementById('prf-form');
    var newPasswordInput = document.getElementById('prf-new-password');
    var serviceAccountJsonInput = document.getElementById('prf-service-account-json');
    var serviceAccountStatusEl = document.getElementById('prf-service-account-status');

    form.addEventListener('submit', function (e) {
        e.preventDefault();

        var formData = new FormData(form);

        fetch(config.updateUrl, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': config.csrfToken,
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json',
            },
            body: formData,
        }).then(async function (res) {
            let data;
            try {
                data = await res.json();
            } catch {
                data = {};
            }
            return { ok: res.ok, data: data };
        }).then(function (result) {
            if (!result.ok) {
                showAlert('danger', (result.data && result.data.message) || '保存に失敗しました。');

                return;
            }

            newPasswordInput.value = '';
            serviceAccountJsonInput.value = '';
            if (result.data.serviceAccountEmail) {
                serviceAccountStatusEl.textContent = result.data.serviceAccountEmail;
            }

            showAlert('success', '保存しました。');
        });
    });
})();
