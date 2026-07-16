(function () {
    'use strict';

    var config = window.profileConfig || {};
    var alertHideTimer = null;

    var alertEl = document.getElementById('prf-alert');
    var alertMessageEl = document.getElementById('prf-alert-message');

    var form = document.getElementById('prf-form');
    var newPasswordInput = document.getElementById('prf-new-password');
    var serviceAccountJsonInput = document.getElementById('prf-service-account-json');
    var serviceAccountStatusEl = document.getElementById('prf-service-account-status');

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
        }).then(function (res) {
            return res.json().catch(function () {
                return {};
            }).then(function (data) {
                return {ok: res.ok, data: data};
            });
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
