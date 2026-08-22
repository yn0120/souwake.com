/*
 * 非同期処理の結果を右上に一時表示するトースト。
 *
 * 家計簿・パスワード管理・プロフィールで同じ実装を持っていたため共通化した。
 * 呼び出し側は <div id="..."><p id="..."></p></div> を置いて、ここで返る show() を使う。
 */

// アラートの見た目はここだけ。色を変える時は office.css の @theme を触れば追従する。
const BASE_CLASS = 'rounded-lg border p-3 text-sm break-words shadow-sm';

const VARIANT_CLASS = {
    success: 'border-success-subtle bg-success-soft text-success-strong',
    danger: 'border-danger-subtle bg-danger-soft text-danger-strong',
};

export function createToast(rootId, messageId, hideAfterMs = 3000) {
    const rootEl = document.getElementById(rootId);
    const messageEl = document.getElementById(messageId);
    let hideTimer = null;

    return function show(type, message) {
        if (! rootEl || ! messageEl) {
            return;
        }

        rootEl.classList.remove('hidden');
        messageEl.className = `${BASE_CLASS} ${VARIANT_CLASS[type] ?? VARIANT_CLASS.danger}`;
        messageEl.textContent = message;

        clearTimeout(hideTimer);
        hideTimer = setTimeout(() => rootEl.classList.add('hidden'), hideAfterMs);
    };
}
