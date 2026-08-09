/**
 * 秘密ファイル閲覧ページの起動処理。
 *
 * 1. Service Worker（secrets-sw.js）を登録する
 * 2. vaultをアンロックしてP-256秘密鍵を取り出す（WebAuthn PRF、またはリカバリコード）
 * 3. その鍵をService Workerへ postMessage で渡す
 * 4. 準備できたらギャラリー本体（secrets-gallery.js）を起動する
 *
 * サーバー側復号へのフォールバックは意図的に一切用意していない。
 * Service WorkerやWebAuthn PRFが使えない環境では、明示的にエラーを出して停止する
 * （黙ってサーバー復号に戻すと、Cloudflareのエッジに平文が流れてE2Eの意味がなくなるため）。
 *
 * 秘密鍵は extractable=false のCryptoKeyとしてこのモジュール変数にだけ保持する。
 * Service Workerは無操作で停止されることがあるため、再起動後に鍵を要求されたら送り直す。
 */
import {
    setupVault,
    unlockWithAuthenticator,
    unlockWithRecoveryCode,
    addAuthenticator,
} from './secrets-vault.js';

const els = {
    gate: document.getElementById('secrets-gate'),
    status: document.getElementById('secrets-gate-status'),
    unlockBtn: document.getElementById('secrets-unlock-btn'),
    setupBtn: document.getElementById('secrets-setup-btn'),
    addKeyBtn: document.getElementById('secrets-add-key-btn'),
    recoveryBtn: document.getElementById('secrets-recovery-btn'),
    recoveryInput: document.getElementById('secrets-recovery-input'),
    gallery: document.getElementById('secrets-gallery-wrap'),
    recoveryOutput: document.getElementById('secrets-recovery-output'),
};

let vaultPrivateKey = null;
let swRegistration = null;

function setStatus(message, isError) {
    if (!els.status) return;
    els.status.textContent = message;
    els.status.className = isError ? 'text-danger' : 'text-muted';
}

/**
 * Service Workerへ鍵を渡し、受理されるまで待つ。
 * SW側は event.source.postMessage で応答するため、navigator.serviceWorker の
 * message イベントで受け取る。
 */
async function sendKeyToServiceWorker() {
    const worker = swRegistration?.active ?? navigator.serviceWorker.controller;
    if (!worker || !vaultPrivateKey) return false;

    return new Promise((resolve) => {
        const onMessage = (event) => {
            if (event.data?.type === 'VAULT_KEY_ACCEPTED') {
                cleanup();
                resolve(true);
            }
        };
        const timer = setTimeout(() => {
            cleanup();
            resolve(false);
        }, 3000);
        function cleanup() {
            clearTimeout(timer);
            navigator.serviceWorker.removeEventListener('message', onMessage);
        }

        navigator.serviceWorker.addEventListener('message', onMessage);

        // CryptoKeyは構造化クローンで渡せる。extractable=falseの属性も保たれるため、
        // Service Worker側でも鍵のバイト列を取り出すことはできない（復号操作は呼べる）。
        worker.postMessage({ type: 'SET_VAULT_KEY', privateKey: vaultPrivateKey });
    });
}

function startGallery() {
    els.gate?.setAttribute('style', 'display:none;');
    els.gallery?.removeAttribute('style');

    if (typeof window.startSecretsGallery === 'function') {
        window.startSecretsGallery();
    }
}

async function afterUnlock() {
    setStatus('Service Workerへ鍵を渡しています...');
    if (!(await sendKeyToServiceWorker())) {
        setStatus('Service Workerが鍵を受け取れませんでした。ページを再読み込みしてください。', true);
        return;
    }
    startGallery();
}

async function handleUnlock() {
    try {
        setStatus('認証器での確認を待っています...');
        vaultPrivateKey = await unlockWithAuthenticator();
        await afterUnlock();
    } catch (e) {
        setStatus(e.message, true);
    }
}

async function handleRecovery() {
    try {
        const code = els.recoveryInput?.value ?? '';
        if (!code.trim()) {
            setStatus('リカバリコードを入力してください。', true);
            return;
        }
        setStatus('リカバリコードを検証しています...');
        vaultPrivateKey = await unlockWithRecoveryCode(code);
        if (els.recoveryInput) els.recoveryInput.value = '';
        await afterUnlock();
    } catch (e) {
        setStatus(e.message, true);
    }
}

async function handleSetup() {
    if (!window.confirm('新しいvault鍵を作成します。認証器（Touch ID / YubiKey等）の登録が必要です。続行しますか？')) {
        return;
    }

    try {
        setStatus('認証器を登録しています...');
        const label = window.prompt('この認証器の名前（例: MacBook Touch ID）', 'MacBook Touch ID');
        if (!label) return;

        const { recoveryCode } = await setupVault(label);

        // リカバリコードはこの一度しか表示できない（サーバーにも平文は残らない）
        if (els.recoveryOutput) {
            els.recoveryOutput.textContent = recoveryCode;
            els.recoveryOutput.parentElement?.removeAttribute('style');
        }
        setStatus('登録が完了しました。表示されたリカバリコードを必ず紙に控えてください（二度と表示されません）。');
    } catch (e) {
        setStatus(e.message, true);
    }
}

async function handleAddKey() {
    try {
        const label = window.prompt('追加する認証器の名前（例: 予備YubiKey）', '予備YubiKey');
        if (!label) return;

        setStatus('既存のvaultをアンロックしてから追加登録します...');
        await addAuthenticator(label);
        setStatus(`「${label}」を追加登録しました。`);
    } catch (e) {
        setStatus(e.message, true);
    }
}

async function boot() {
    if (!('serviceWorker' in navigator) || !window.isSecureContext) {
        setStatus('この環境ではService Workerが使えないため、ファイルを表示できません（HTTPS接続が必要です）。', true);
        return;
    }
    if (!window.PublicKeyCredential) {
        setStatus('この環境ではWebAuthnが使えないため、vaultをアンロックできません。', true);
        return;
    }

    try {
        swRegistration = await navigator.serviceWorker.register('/assets/js/secrets-sw.js', { scope: '/' });
        await navigator.serviceWorker.ready;
    } catch (e) {
        setStatus(`Service Workerの登録に失敗しました: ${e.message}`, true);
        return;
    }

    // SWが再起動して鍵を失った場合、こちらから送り直す
    navigator.serviceWorker.addEventListener('message', (event) => {
        if (event.data?.type === 'VAULT_KEY_REQUIRED' && vaultPrivateKey) {
            sendKeyToServiceWorker();
        }
    });

    const state = window.secretsVaultState ?? {};
    if (!state.registered) {
        setStatus('vaultが未登録です。「vaultを新規作成」から認証器を登録してください。');
        els.setupBtn?.removeAttribute('style');
        return;
    }

    setStatus('「アンロック」を押して認証器で本人確認してください。');
    els.unlockBtn?.removeAttribute('style');
    els.addKeyBtn?.removeAttribute('style');
}

els.unlockBtn?.addEventListener('click', handleUnlock);
els.setupBtn?.addEventListener('click', handleSetup);
els.addKeyBtn?.addEventListener('click', handleAddKey);
els.recoveryBtn?.addEventListener('click', handleRecovery);

boot();
