/**
 * 秘密ファイルのE2E復号を担うService Worker。
 *
 * `<img src="/secrets/media/{id}">` や `<video src="/secrets/media/{id}">` を横取りし、
 *   1. /secrets/meta/{id} でチャンク情報とラップ済みファイル鍵を取得
 *   2. vault秘密鍵とのECDHでファイル鍵をアンラップ
 *   3. ブラウザが要求した「平文座標のRange」を「暗号文座標のRange」に変換して
 *      /secrets/raw/{id} から必要なチャンクだけ取得
 *   4. WebCryptoでチャンクを復号し、平文座標の206レスポンスとして組み立てて返す
 * という流れで、サーバーにもCloudflareにも平文を触らせずに表示・再生を成立させる。
 *
 * これによりPlyrの再生・シーク・±10秒ジャンプは、通常のRange対応サーバーを相手にしているのと
 * 同じように動作する（secrets-gallery.js側の変更はsrcのパスだけで済む）。
 *
 * 暗号文のディスク上のレイアウト（PHP側の SecretFileCryptoService と一致させること）:
 *
 *   chunk i の位置 = i * (chunkSize + tagLen)
 *   chunk i の中身 = [ciphertext][16byte GCMタグ]
 *   chunk i のnonce = 基準ノンスの下位4byteに i を加算したもの
 *   chunk i のAAD  = "{uuid}|{i}|{isLast ? 1 : 0}"
 *
 * 秘密鍵はモジュール変数にしか保持せず、IndexedDB等へ永続化しない。
 * Service Workerは無操作で停止されるため、再起動後は開いているページへ鍵を要求し直す。
 */

const HKDF_INFO_FILE_KEY = 'souwake-secrets-file-key-v1';
const MEDIA_PREFIX = '/secrets/media/';

/** vault秘密鍵（ECDH, extractable=false）。ページから postMessage で渡される */
let vaultPrivateKey = null;

/** id -> メタデータ + ファイル鍵 のキャッシュ。SWが生きている間だけ保持する */
const fileCache = new Map();

// --- ユーティリティ ---------------------------------------------------------

function b64decode(str) {
    const bin = atob(str);
    const bytes = new Uint8Array(bin.length);
    for (let i = 0; i < bin.length; i++) bytes[i] = bin.charCodeAt(i);
    return bytes;
}

/**
 * チャンクindexからそのチャンク専用のnonceを導出する。
 * PHP側 deriveChunkNonce と同じく「基準ノンスの下位4byteをbig-endian uint32として加算」する。
 */
function deriveChunkNonce(nonceBase, chunkIndex) {
    const nonce = new Uint8Array(nonceBase);
    const view = new DataView(nonce.buffer, nonce.byteOffset, nonce.byteLength);
    const counter = view.getUint32(8, false);
    // >>> 0 で符号なし32bitに畳む（PHP側の & 0xFFFFFFFF と等価）
    view.setUint32(8, (counter + chunkIndex) >>> 0, false);
    return nonce;
}

/** PHP側 buildAad と同じ文字列を生成する */
function buildAad(uuid, chunkIndex, isLast) {
    return new TextEncoder().encode(`${uuid}|${chunkIndex}|${isLast ? '1' : '0'}`);
}

/**
 * ブラウザが送ってきたRangeヘッダー（平文座標）を解釈する。
 * PHP側にあった resolveRange と同じ仕様（bytes=a-b / bytes=a- / bytes=-N）。
 */
function resolveRange(rangeHeader, plainSize) {
    if (!rangeHeader) {
        return { start: 0, end: Math.max(0, plainSize - 1), isPartial: false };
    }

    const m = /bytes=(\d*)-(\d*)/.exec(rangeHeader);
    if (!m) {
        return { start: 0, end: Math.max(0, plainSize - 1), isPartial: false };
    }

    let start;
    let end;
    if (m[1] === '' && m[2] !== '') {
        start = Math.max(0, plainSize - Number(m[2]));
        end = plainSize - 1;
    } else {
        start = m[1] === '' ? 0 : Number(m[1]);
        end = m[2] === '' ? plainSize - 1 : Number(m[2]);
    }

    if (start > end || end >= plainSize || start < 0) {
        return { invalid: true };
    }

    return { start, end, isPartial: true };
}

// --- 鍵のアンラップ ---------------------------------------------------------

/**
 * ファイル鍵をアンラップする。
 * 一時公開鍵（サーバーが生成）とvault秘密鍵（ブラウザのみ）のECDHで共有秘密を作り、
 * HKDF-SHA256でラップ鍵を導出してAES-256-GCMを解く。PHP側 wrapFileKeyForVault の逆操作。
 */
async function unwrapFileKey(meta) {
    const ephPublicKey = await crypto.subtle.importKey(
        'spki',
        b64decode(meta.eph_public_key),
        { name: 'ECDH', namedCurve: 'P-256' },
        false,
        [],
    );

    const sharedSecret = await crypto.subtle.deriveBits(
        { name: 'ECDH', public: ephPublicKey },
        vaultPrivateKey,
        256,
    );

    const hkdfBase = await crypto.subtle.importKey('raw', sharedSecret, 'HKDF', false, ['deriveKey']);
    const wrapKey = await crypto.subtle.deriveKey(
        {
            name: 'HKDF',
            hash: 'SHA-256',
            // PHP側は salt に一時公開鍵(SPKI DER)を渡している
            salt: b64decode(meta.eph_public_key),
            info: new TextEncoder().encode(HKDF_INFO_FILE_KEY),
        },
        hkdfBase,
        { name: 'AES-GCM', length: 256 },
        false,
        ['decrypt'],
    );

    // PHPは暗号文とタグを別カラムに分けて保存しているが、WebCryptoは連結された形を要求する
    const ct = b64decode(meta.wrapped_key);
    const tag = b64decode(meta.wrap_tag);
    const sealed = new Uint8Array(ct.length + tag.length);
    sealed.set(ct, 0);
    sealed.set(tag, ct.length);

    const raw = await crypto.subtle.decrypt(
        { name: 'AES-GCM', iv: b64decode(meta.wrap_nonce), tagLength: 128 },
        wrapKey,
        sealed,
    );

    return crypto.subtle.importKey('raw', raw, { name: 'AES-GCM' }, false, ['decrypt']);
}

async function loadFile(id) {
    if (fileCache.has(id)) return fileCache.get(id);

    const res = await fetch(`/secrets/meta/${id}`, { credentials: 'same-origin' });
    if (!res.ok) throw new Error(`メタデータの取得に失敗しました (${res.status})`);

    const meta = await res.json();
    const entry = {
        meta,
        fileKey: await unwrapFileKey(meta),
        nonceBase: b64decode(meta.content_nonce_base),
        totalChunks: Math.max(1, Math.ceil(meta.size_bytes / meta.chunk_size)),
    };

    fileCache.set(id, entry);
    return entry;
}

// --- 復号ストリームの組み立て -----------------------------------------------

/**
 * 平文座標の [start, end] を返すレスポンスを組み立てる。
 * 必要なチャンクだけを1回のRangeリクエストで取得し、順に復号してReadableStreamへ流す。
 */
async function buildDecryptedResponse(id, rangeHeader) {
    const { meta, fileKey, nonceBase, totalChunks } = await loadFile(id);
    const { chunk_size: chunkSize, tag_len: tagLen, uuid, size_bytes: plainSize } = meta;

    const range = resolveRange(rangeHeader, plainSize);
    if (range.invalid) {
        return new Response('', { status: 416, headers: { 'Content-Range': `bytes */${plainSize}` } });
    }

    const { start, end, isPartial } = range;
    const firstChunk = Math.floor(start / chunkSize);
    const lastChunk = Math.floor(end / chunkSize);

    // 暗号文側の取得範囲。最終チャンクは平文が chunkSize 未満のことがあるため、
    // 終端は指定せず「そこから最後まで」を要求してサーバー（nginx）に任せる。
    const cipherStart = firstChunk * (chunkSize + tagLen);
    const cipherEnd = lastChunk === totalChunks - 1 ? '' : (lastChunk + 1) * (chunkSize + tagLen) - 1;

    const rawRes = await fetch(`/secrets/raw/${id}`, {
        credentials: 'same-origin',
        headers: { Range: `bytes=${cipherStart}-${cipherEnd}` },
    });

    if (rawRes.status !== 206 && rawRes.status !== 200) {
        // Cloudflare Accessのセッション切れなどで認証画面へ飛ばされた場合もここに来る
        throw new Error(`暗号文の取得に失敗しました (${rawRes.status})`);
    }

    const cipherBuffer = new Uint8Array(await rawRes.arrayBuffer());

    const stream = new ReadableStream({
        async start(controller) {
            try {
                for (let i = firstChunk; i <= lastChunk; i++) {
                    const isLast = i === totalChunks - 1;
                    const offsetInBuffer = (i - firstChunk) * (chunkSize + tagLen);
                    const blockSize = isLast
                        ? cipherBuffer.length - offsetInBuffer
                        : chunkSize + tagLen;
                    const block = cipherBuffer.subarray(offsetInBuffer, offsetInBuffer + blockSize);

                    // 復号に失敗＝改ざんか破損。旧実装ではサーバーが黙って配信を打ち切っていたため
                    // クライアントには「短いレスポンス」としてしか見えなかったが、E2E化により
                    // 改ざん検知がクライアント側で明示的に起きるようになった。
                    const plaintext = new Uint8Array(
                        await crypto.subtle.decrypt(
                            {
                                name: 'AES-GCM',
                                iv: deriveChunkNonce(nonceBase, i),
                                additionalData: buildAad(uuid, i, isLast),
                                tagLength: 128,
                            },
                            fileKey,
                            block,
                        ),
                    );

                    const chunkPlainStart = i * chunkSize;
                    const sliceStart = Math.max(0, start - chunkPlainStart);
                    const sliceEnd = Math.min(plaintext.length - 1, end - chunkPlainStart);

                    if (sliceStart <= sliceEnd) {
                        controller.enqueue(plaintext.subarray(sliceStart, sliceEnd + 1));
                    }
                }
                controller.close();
            } catch (e) {
                controller.error(e);
            }
        },
    });

    const headers = {
        'Content-Type': meta.mime_type,
        'Content-Length': String(end - start + 1),
        'Accept-Ranges': 'bytes',
        'Cache-Control': 'no-store',
    };
    if (isPartial) {
        headers['Content-Range'] = `bytes ${start}-${end}/${plainSize}`;
    }

    return new Response(stream, { status: isPartial ? 206 : 200, headers });
}

// --- Service Workerのライフサイクル -----------------------------------------

self.addEventListener('install', (event) => {
    // 更新をすぐ反映させる（古いSWが残っていると復号ロジックの不整合が起きうる）
    event.waitUntil(self.skipWaiting());
});

self.addEventListener('activate', (event) => {
    event.waitUntil(self.clients.claim());
});

self.addEventListener('message', (event) => {
    if (event.data?.type === 'SET_VAULT_KEY') {
        vaultPrivateKey = event.data.privateKey;
        fileCache.clear();
        event.source?.postMessage({ type: 'VAULT_KEY_ACCEPTED' });
    } else if (event.data?.type === 'CLEAR_VAULT_KEY') {
        vaultPrivateKey = null;
        fileCache.clear();
    }
});

/** SWが再起動して鍵を失った場合に、開いているページへ鍵の再送を要求する */
async function requestVaultKeyFromClients() {
    const clientList = await self.clients.matchAll({ type: 'window' });
    for (const client of clientList) {
        client.postMessage({ type: 'VAULT_KEY_REQUIRED' });
    }

    // ページが postMessage で鍵を返してくるのを最大3秒待つ
    for (let i = 0; i < 30; i++) {
        if (vaultPrivateKey) return true;
        await new Promise((r) => setTimeout(r, 100));
    }
    return false;
}

self.addEventListener('fetch', (event) => {
    const url = new URL(event.request.url);

    if (url.origin !== self.location.origin || !url.pathname.startsWith(MEDIA_PREFIX)) {
        return;
    }

    const id = url.pathname.slice(MEDIA_PREFIX.length);

    event.respondWith(
        (async () => {
            try {
                if (!vaultPrivateKey && !(await requestVaultKeyFromClients())) {
                    // フォールバックでサーバー側復号に戻すことは絶対にしない（E2Eが無意味になる）。
                    // 明示的にエラーを返し、ページ側でアンロックを促す。
                    return new Response('vaultがアンロックされていません。', {
                        status: 401,
                        headers: { 'Content-Type': 'text/plain; charset=utf-8' },
                    });
                }

                return await buildDecryptedResponse(id, event.request.headers.get('Range'));
            } catch (e) {
                return new Response(`復号に失敗しました: ${e.message}`, {
                    status: 502,
                    headers: { 'Content-Type': 'text/plain; charset=utf-8' },
                });
            }
        })(),
    );
});
