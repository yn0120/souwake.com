/**
 * ファイル機能のE2E暗号化「vault鍵」を扱うモジュール。
 *
 * 鍵の階層:
 *
 *   WebAuthn PRF (Touch ID / YubiKey)  または  リカバリコード(32byteランダム)
 *     └─ HKDF-SHA256 → vault key (AES-256-GCM)
 *          └─ unwrap → P-256 ECDH 秘密鍵 (PKCS8)   ← ブラウザ内のみ。サーバーは暗号文しか持たない
 *               └─ ECDH-ES + HKDF → file key (AES-256-GCM)
 *                    └─ /var/encrypted の 1MiB チャンクを復号
 *
 * 設計上の要点:
 * - 鍵ペアの生成もラップもすべてここ（ブラウザ）で行う。サーバーへ送るのは
 *   「公開鍵」と「ラップ済みの秘密鍵」だけで、秘密鍵の平文は一度も送らない。
 * - WebAuthnは認証手段ではなく**鍵導出手段**としてのみ使う。認証はLaravelのセッションと
 *   Cloudflare Accessが担っているため、アテステーション検証もチャレンジ管理も行わない。
 * - 秘密鍵のCryptoKeyは extractable=false で import し、一度復号したら二度と取り出せないようにする
 *   （XSSを踏んでも鍵バイト列そのものは持ち出せない。ただし復号操作は呼べてしまうため、
 *   CSPによるXSS対策とセットで初めて意味を持つ）。
 */

const HKDF_INFO_VAULT_KEY = 'souwake-secrets-vault-key-v1';
const HKDF_INFO_RECOVERY = 'souwake-secrets-vault-recovery-v1';

// --- 変換ユーティリティ -----------------------------------------------------

const b64 = {
    encode(buf) {
        const bytes = new Uint8Array(buf);
        let s = '';
        for (let i = 0; i < bytes.length; i++) s += String.fromCharCode(bytes[i]);
        return btoa(s);
    },
    decode(str) {
        const bin = atob(str);
        const bytes = new Uint8Array(bin.length);
        for (let i = 0; i < bin.length; i++) bytes[i] = bin.charCodeAt(i);
        return bytes;
    },
};

// WebAuthnのcredential IDはbase64urlで扱う（`+/` が使えないため）
const b64url = {
    encode(buf) {
        return b64.encode(buf).replace(/\+/g, '-').replace(/\//g, '_').replace(/=+$/, '');
    },
    decode(str) {
        const pad = str.length % 4 === 0 ? '' : '='.repeat(4 - (str.length % 4));
        return b64.decode(str.replace(/-/g, '+').replace(/_/g, '/') + pad);
    },
};

/** base32（Crockford風の大文字英数字）。リカバリコードを人が書き写せる形にするため */
const BASE32_ALPHABET = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';

function toBase32(bytes) {
    let bits = 0;
    let value = 0;
    let out = '';
    for (const byte of bytes) {
        value = (value << 8) | byte;
        bits += 8;
        while (bits >= 5) {
            out += BASE32_ALPHABET[(value >>> (bits - 5)) & 31];
            bits -= 5;
        }
    }
    if (bits > 0) out += BASE32_ALPHABET[(value << (5 - bits)) & 31];
    // 4文字ごとにハイフンを入れて書き写しやすくする
    return out.match(/.{1,4}/g).join('-');
}

function fromBase32(str) {
    const clean = str.toUpperCase().replace(/[^A-Z2-7]/g, '');
    let bits = 0;
    let value = 0;
    const out = [];
    for (const ch of clean) {
        const idx = BASE32_ALPHABET.indexOf(ch);
        if (idx < 0) throw new Error('リカバリコードに使用できない文字が含まれています。');
        value = (value << 5) | idx;
        bits += 5;
        if (bits >= 8) {
            out.push((value >>> (bits - 8)) & 255);
            bits -= 8;
        }
    }
    return new Uint8Array(out);
}

// --- 鍵導出 -----------------------------------------------------------------

/**
 * 生の32byte（PRF出力 or リカバリコード）からAES-256-GCMのvault鍵を導出する。
 * PRF出力もリカバリコードも既に十分なエントロピーを持つ完全ランダム値なので、
 * Argon2等のストレッチは不要で、HKDFで用途を分離するだけでよい。
 */
async function deriveVaultKey(rawSecret, salt, info) {
    const base = await crypto.subtle.importKey('raw', rawSecret, 'HKDF', false, ['deriveKey']);

    return crypto.subtle.deriveKey(
        {
            name: 'HKDF',
            hash: 'SHA-256',
            salt: salt,
            info: new TextEncoder().encode(info),
        },
        base,
        { name: 'AES-GCM', length: 256 },
        false,
        ['encrypt', 'decrypt'],
    );
}

// --- WebAuthn PRF -----------------------------------------------------------

/**
 * PRF拡張つきで認証器を新規登録する。
 * 戻り値の prfSupported が false の場合、その認証器はPRFに対応していないため使えない。
 */
export async function registerAuthenticator(label) {
    const userId = crypto.getRandomValues(new Uint8Array(16));

    const credential = await navigator.credentials.create({
        publicKey: {
            // 認証には使わないため、チャレンジはランダム値でよい（サーバー検証を行わない）
            challenge: crypto.getRandomValues(new Uint8Array(32)),
            rp: { name: 'souwake secrets', id: location.hostname },
            user: { id: userId, name: label, displayName: label },
            pubKeyCredParams: [
                { type: 'public-key', alg: -7 },   // ES256
                { type: 'public-key', alg: -257 }, // RS256
            ],
            authenticatorSelection: {
                userVerification: 'required',
                residentKey: 'preferred',
            },
            extensions: { prf: {} },
            timeout: 120000,
        },
    });

    const ext = credential.getClientExtensionResults();
    if (!ext.prf || ext.prf.enabled !== true) {
        throw new Error(
            'この認証器はWebAuthn PRF拡張に対応していないため、vault鍵の導出に使えません。'
            + '別の認証器（YubiKey 5シリーズ、Touch ID搭載のMac、対応するAndroid端末など）をお試しください。',
        );
    }

    return {
        credentialId: b64url.encode(credential.rawId),
        prfSalt: crypto.getRandomValues(new Uint8Array(32)),
    };
}

/**
 * 登録済みの認証器でPRFを評価し、vault鍵を得る。
 * どの認証器が使われたかは戻り値の usedCredentialId で分かる（複数登録時の判別用）。
 */
export async function evaluatePrf(keys) {
    const webauthnKeys = keys.filter((k) => k.kind === 'webauthn');
    if (webauthnKeys.length === 0) {
        throw new Error('登録済みの認証器がありません。');
    }

    // 複数の認証器を登録している場合、どれが挿さっているか分からないので全部をallowCredentialsに載せる。
    // ただしPRFのsaltは認証器ごとに違うため、eval ではなく evalByCredential で個別に渡す。
    const evalByCredential = {};
    for (const k of webauthnKeys) {
        evalByCredential[k.credential_id] = { first: b64.decode(k.prf_salt) };
    }

    const assertion = await navigator.credentials.get({
        publicKey: {
            challenge: crypto.getRandomValues(new Uint8Array(32)),
            rpId: location.hostname,
            allowCredentials: webauthnKeys.map((k) => ({
                type: 'public-key',
                id: b64url.decode(k.credential_id),
            })),
            userVerification: 'required',
            extensions: { prf: { evalByCredential } },
            timeout: 120000,
        },
    });

    const ext = assertion.getClientExtensionResults();
    if (!ext.prf || !ext.prf.results || !ext.prf.results.first) {
        throw new Error('認証器からPRF出力を取得できませんでした。');
    }

    const usedCredentialId = b64url.encode(assertion.rawId);
    const key = webauthnKeys.find((k) => k.credential_id === usedCredentialId);
    if (!key) {
        throw new Error('使用された認証器が登録済みの一覧に見つかりません。');
    }

    return {
        key,
        vaultKey: await deriveVaultKey(ext.prf.results.first, b64.decode(key.prf_salt), HKDF_INFO_VAULT_KEY),
    };
}

// --- vault鍵ペアの生成・ラップ・アンラップ -----------------------------------

/** P-256のECDH鍵ペアを新規生成する（初回登録時のみ） */
async function generateVaultKeyPair() {
    return crypto.subtle.generateKey(
        { name: 'ECDH', namedCurve: 'P-256' },
        true, // 秘密鍵をラップするために一度だけexportする必要があるのでtrue
        ['deriveBits'],
    );
}

/** 秘密鍵（PKCS8）をvault鍵でAES-256-GCM暗号化する */
async function wrapPrivateKey(vaultKey, privateKeyPkcs8) {
    const nonce = crypto.getRandomValues(new Uint8Array(12));
    const sealed = new Uint8Array(
        await crypto.subtle.encrypt({ name: 'AES-GCM', iv: nonce, tagLength: 128 }, vaultKey, privateKeyPkcs8),
    );

    // WebCryptoは [暗号文][16byteタグ] を連結して返す。PHP側の保存形式に合わせて分離する
    return {
        wrapped_private_key: b64.encode(sealed.slice(0, sealed.length - 16)),
        wrap_nonce: b64.encode(nonce),
        wrap_tag: b64.encode(sealed.slice(sealed.length - 16)),
    };
}

/**
 * ラップ済み秘密鍵をvault鍵で復号し、extractable=false のCryptoKeyとしてimportする。
 * 以降このキーからバイト列を取り出すことはできない。
 */
async function unwrapPrivateKey(vaultKey, row) {
    const ciphertext = b64.decode(row.wrapped_private_key);
    const tag = b64.decode(row.wrap_tag);
    const sealed = new Uint8Array(ciphertext.length + tag.length);
    sealed.set(ciphertext, 0);
    sealed.set(tag, ciphertext.length);

    let pkcs8;
    try {
        pkcs8 = await crypto.subtle.decrypt(
            { name: 'AES-GCM', iv: b64.decode(row.wrap_nonce), tagLength: 128 },
            vaultKey,
            sealed,
        );
    } catch (e) {
        throw new Error('vault秘密鍵の復号に失敗しました（認証器またはリカバリコードが一致していません）。');
    }

    return crypto.subtle.importKey('pkcs8', pkcs8, { name: 'ECDH', namedCurve: 'P-256' }, false, ['deriveBits']);
}

// --- サーバーとのやり取り ---------------------------------------------------

function csrfToken() {
    return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '';
}

async function fetchVault() {
    const res = await fetch('/secrets/vault', { credentials: 'same-origin' });
    if (!res.ok) throw new Error('vault情報の取得に失敗しました。');
    return res.json();
}

async function postVault(payload) {
    const res = await fetch('/secrets/vault', {
        method: 'POST',
        credentials: 'same-origin',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken() },
        body: JSON.stringify(payload),
    });
    if (!res.ok) {
        const body = await res.json().catch(() => ({}));
        throw new Error(body.error ?? 'vaultの登録に失敗しました。');
    }
    return res.json();
}

// --- 公開API ---------------------------------------------------------------

/**
 * 初回セットアップ。新しい鍵ペアを生成し、認証器とリカバリコードの2通りでラップして登録する。
 * 戻り値のリカバリコードは**この一度しか表示されない**ので、必ず利用者に控えさせること。
 */
export async function setupVault(label) {
    const state = await fetchVault();
    if (state.registered) {
        throw new Error('vaultは既に登録済みです。追加登録には addAuthenticator を使ってください。');
    }

    const { credentialId, prfSalt } = await registerAuthenticator(label);
    const keyPair = await generateVaultKeyPair();
    const publicKeySpki = await crypto.subtle.exportKey('spki', keyPair.publicKey);
    const privateKeyPkcs8 = await crypto.subtle.exportKey('pkcs8', keyPair.privateKey);

    // 1件目: 認証器のPRF出力でラップ
    const prfOutput = await evaluatePrfRaw(credentialId, prfSalt);
    const vaultKey = await deriveVaultKey(prfOutput, prfSalt, HKDF_INFO_VAULT_KEY);
    await postVault({
        kind: 'webauthn',
        label,
        credential_id: credentialId,
        prf_salt: b64.encode(prfSalt),
        public_key: b64.encode(publicKeySpki),
        ...(await wrapPrivateKey(vaultKey, privateKeyPkcs8)),
    });

    // 2件目: リカバリコードでラップ（認証器を全部失った場合の最後の砦）
    const recoveryCode = crypto.getRandomValues(new Uint8Array(32));
    const recoverySalt = crypto.getRandomValues(new Uint8Array(32));
    const recoveryKey = await deriveVaultKey(recoveryCode, recoverySalt, HKDF_INFO_RECOVERY);
    await postVault({
        kind: 'recovery',
        label: 'リカバリコード',
        recovery_salt: b64.encode(recoverySalt),
        public_key: b64.encode(publicKeySpki),
        ...(await wrapPrivateKey(recoveryKey, privateKeyPkcs8)),
    });

    return { recoveryCode: toBase32(recoveryCode) };
}

/** 指定のcredentialでPRFを1回だけ評価する（setupVault内部用） */
async function evaluatePrfRaw(credentialId, prfSalt) {
    const assertion = await navigator.credentials.get({
        publicKey: {
            challenge: crypto.getRandomValues(new Uint8Array(32)),
            rpId: location.hostname,
            allowCredentials: [{ type: 'public-key', id: b64url.decode(credentialId) }],
            userVerification: 'required',
            extensions: { prf: { eval: { first: prfSalt } } },
            timeout: 120000,
        },
    });

    const ext = assertion.getClientExtensionResults();
    if (!ext.prf?.results?.first) {
        throw new Error('認証器からPRF出力を取得できませんでした。');
    }

    return ext.prf.results.first;
}

/**
 * 認証器でvaultをアンロックし、復号用の秘密鍵（extractable=false）を返す。
 * これをService Workerへ postMessage で渡すと、以降のメディア復号が可能になる。
 */
export async function unlockWithAuthenticator() {
    const state = await fetchVault();
    if (!state.registered) throw new Error('vaultが未登録です。先にセットアップしてください。');

    const { key, vaultKey } = await evaluatePrf(state.keys);
    const privateKey = await unwrapPrivateKey(vaultKey, key);

    fetch(`/secrets/vault/${key.id}/touch`, {
        method: 'POST',
        credentials: 'same-origin',
        headers: { 'X-CSRF-TOKEN': csrfToken() },
    }).catch(() => {});

    return privateKey;
}

/** リカバリコードでvaultをアンロックする（認証器を全部失った場合） */
export async function unlockWithRecoveryCode(codeText) {
    const state = await fetchVault();
    if (!state.registered) throw new Error('vaultが未登録です。');

    const row = state.keys.find((k) => k.kind === 'recovery');
    if (!row) throw new Error('リカバリコードが登録されていません。');

    const code = fromBase32(codeText);
    if (code.length !== 32) throw new Error('リカバリコードの長さが不正です。');

    const recoveryKey = await deriveVaultKey(code, b64.decode(row.recovery_salt), HKDF_INFO_RECOVERY);

    return unwrapPrivateKey(recoveryKey, row);
}

/**
 * バックアップ認証器を追加登録する。
 * 既存のvaultを一度アンロックして秘密鍵を取り出す必要があるため、
 * ここでは extractable=true で import し直す点に注意（追加登録時だけの一時的な例外）。
 */
export async function addAuthenticator(label, existingVaultKeyAndRow) {
    const state = await fetchVault();
    if (!state.registered) throw new Error('vaultが未登録です。');

    const { vaultKey, key } = existingVaultKeyAndRow ?? (await evaluatePrf(state.keys));

    // 追加登録では新しい認証器で同じ秘密鍵をラップし直す必要があるため、平文のPKCS8を経由する
    const ciphertext = b64.decode(key.wrapped_private_key);
    const tag = b64.decode(key.wrap_tag);
    const sealed = new Uint8Array(ciphertext.length + tag.length);
    sealed.set(ciphertext, 0);
    sealed.set(tag, ciphertext.length);
    const privateKeyPkcs8 = await crypto.subtle.decrypt(
        { name: 'AES-GCM', iv: b64.decode(key.wrap_nonce), tagLength: 128 },
        vaultKey,
        sealed,
    );

    const { credentialId, prfSalt } = await registerAuthenticator(label);
    const prfOutput = await evaluatePrfRaw(credentialId, prfSalt);
    const newVaultKey = await deriveVaultKey(prfOutput, prfSalt, HKDF_INFO_VAULT_KEY);

    await postVault({
        kind: 'webauthn',
        label,
        credential_id: credentialId,
        prf_salt: b64.encode(prfSalt),
        public_key: state.public_key,
        ...(await wrapPrivateKey(newVaultKey, privateKeyPkcs8)),
    });
}

export const __testing = { toBase32, fromBase32, b64, b64url };
