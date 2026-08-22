/*
 * 権限付与ページ。表のセルをクリックすると、その場で権限を付与/剥奪する。
 *
 * セルはBlade側で <label> がチェックボックスを包む構造にしてあるため、
 * 「セルのどこを押してもトグルされる」のはブラウザ標準の挙動で成立する。
 * ここは change を拾って非同期送信するだけ（クリック位置の判定は不要）。
 */
(function () {
    'use strict';

    var config = window.roleRoutesConfig || {};

    document.addEventListener('change', function (e) {
        var checkbox = e.target.closest('input[name="is_allowed"]');
        if (! checkbox) {
            return;
        }

        var isAllowed = checkbox.checked;

        fetch(config.updateUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': config.csrfToken,
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json',
            },
            body: JSON.stringify({
                role_id: checkbox.dataset.roleId,
                route_id: checkbox.dataset.routeId,
                is_allowed: isAllowed ? 1 : 0,
            }),
        }).then(function (res) {
            if (! res.ok) {
                throw new Error(String(res.status));
            }
        }).catch(function () {
            // 失敗した時はチェック状態を元に戻す（画面と実データが食い違わないようにする）
            checkbox.checked = ! isAllowed;
            window.alert('予期せぬエラーが発生しました。時間をおいて再度お試しください。');
        });
    });
})();
