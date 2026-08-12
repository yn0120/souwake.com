/**
 * 結婚式招待ページのフロント処理。
 *
 * - スクロール演出、出欠・同伴者による項目の出し分け
 * - お祝い画像の非同期アップロード（選択した時点で1枚ずつPOST。変換はサーバー側のキューが担当）
 * - 入力内容・アップロード済み画像のlocalStorageへの退避と、再訪時の復元
 */

/** localStorageのキー。保存形式を変えるときはバージョンを上げる */
const STORAGE_KEY = 'wedding:rsvp:input:v1';

/** 変換完了待ちのポーリング間隔（ms）と最大回数 */
const PHOTO_POLL_INTERVAL = 3000;
const PHOTO_POLL_MAX_COUNT = 60;

/** 復元・保存の対象外にする項目（CSRFトークン、ボット対策のハニーポット、JSが管理する項目） */
const EXCLUDED_FIELDS = ['_token', 'contact_note', 'photo_session_token', 'photo_tokens[]'];

const ALLOWED_IMAGE_TYPES = ['image/jpeg', 'image/png', 'image/webp', 'image/gif', 'image/heic', 'image/heif'];

/** ご住所の国（App\Models\WeddingRsvpModelの定数と対応） */
const COUNTRY_JP = 'JP';
const COUNTRY_US = 'US';

const readStore = () => {
    try {
        return JSON.parse(localStorage.getItem(STORAGE_KEY)) || {};
    } catch {
        return {};
    }
};

const writeStore = (data) => {
    try {
        localStorage.setItem(STORAGE_KEY, JSON.stringify(data));
    } catch {
        // プライベートブラウジングや容量超過で保存できない場合は、保持機能なしで動作させる
    }
};

const clearStore = () => {
    try {
        localStorage.removeItem(STORAGE_KEY);
    } catch {
        // 失敗しても致命的ではないため無視する
    }
};

const patchStore = (patch) => writeStore({ ...readStore(), ...patch });

const createUuid = () => {
    if (window.crypto?.randomUUID) {
        return window.crypto.randomUUID();
    }

    // crypto.randomUUIDが使えない環境（古いSafari・非HTTPS）向けのフォールバック
    return 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, (char) => {
        const random = (Math.random() * 16) | 0;

        return (char === 'x' ? random : (random & 0x3) | 0x8).toString(16);
    });
};

const csrfToken = () => document.querySelector('meta[name="csrf-token"]')?.content ?? '';

document.addEventListener('DOMContentLoaded', () => {
    // 送信完了ページでは退避していた入力内容を破棄する
    if (document.querySelector('[data-clear-rsvp-storage]')) {
        clearStore();
    }

    // スクロールで要素をふわっと表示
    const revealTargets = document.querySelectorAll('.wedding-reveal');
    if ('IntersectionObserver' in window && revealTargets.length) {
        const observer = new IntersectionObserver(
            (entries) => {
                entries.forEach((entry) => {
                    if (entry.isIntersecting) {
                        entry.target.classList.add('is-visible');
                        observer.unobserve(entry.target);
                    }
                });
            },
            { threshold: 0.15 }
        );
        revealTargets.forEach((el) => observer.observe(el));
    } else {
        revealTargets.forEach((el) => el.classList.add('is-visible'));
    }

    // 出欠：欠席選択時は沖縄リゾート婚特有の項目を隠す
    const attendanceRadios = document.querySelectorAll('input[name="attendance"]');
    const attendingOnlySection = document.getElementById('attending-only-fields');
    const toggleAttendingSection = () => {
        const selected = document.querySelector('input[name="attendance"]:checked');
        if (!attendingOnlySection) {
            return;
        }
        attendingOnlySection.classList.toggle('hidden', !selected || selected.value !== 'attending');
    };
    attendanceRadios.forEach((radio) => radio.addEventListener('change', toggleAttendingSection));
    toggleAttendingSection();

    // 同伴者：ありを選択したときのみ詳細項目を表示
    const companionRadios = document.querySelectorAll('input[name="companion_flag"]');
    const companionFields = document.getElementById('companion-fields');
    const toggleCompanionFields = () => {
        const selected = document.querySelector('input[name="companion_flag"]:checked');
        if (!companionFields) {
            return;
        }
        companionFields.classList.toggle('hidden', !selected || selected.value !== '1');
    };
    companionRadios.forEach((radio) => radio.addEventListener('change', toggleCompanionFields));
    toggleCompanionFields();

    // ナビの目次からのスムーズスクロール
    document.querySelectorAll('a[data-scroll]').forEach((link) => {
        link.addEventListener('click', (event) => {
            const target = document.querySelector(link.getAttribute('href'));
            if (!target) {
                return;
            }
            event.preventDefault();
            target.scrollIntoView({ behavior: 'smooth', block: 'start' });
        });
    });

    initPhotoUploader();
    const companionRows = initCompanionRows();
    initPostalCodeLookup();
    // 国の切替時にカレンダーの言語も作り直す
    initCountrySwitch((country) => initDatepickers(country));
    initFormPersistence(companionRows);
});

/**
 * 入力内容をlocalStorageへ退避し、再訪時に復元する。
 *
 * @param {{ensureRowCount: (count: number) => void}|null} companionRows 同伴者行の操作
 */
function initFormPersistence(companionRows) {
    const form = document.querySelector('[data-rsvp-form]');
    if (!form) {
        return;
    }

    const targetFields = () =>
        Array.from(form.querySelectorAll('input[name], select[name], textarea[name]')).filter(
            (field) => field.type !== 'file' && !EXCLUDED_FIELDS.includes(field.name)
        );

    const saveFields = () => {
        const fields = {};
        targetFields().forEach((field) => {
            if (field.type === 'radio') {
                if (field.checked) {
                    fields[field.name] = field.value;
                }

                return;
            }
            if (field.type === 'checkbox') {
                fields[field.name] = field.checked;

                return;
            }
            fields[field.name] = field.value;
        });
        patchStore({ fields, savedAt: new Date().toISOString() });
    };

    // バリデーションエラー等でサーバー側の値（old）が入っている場合は、そちらを優先する
    if (form.dataset.restoreInput === '1') {
        const { fields } = readStore();
        if (fields) {
            // 同伴者は行そのものが動的に増えるため、値を戻す前に保存時の行数まで復元しておく
            const companionCount = Object.keys(fields).reduce((max, name) => {
                const matched = name.match(/^companions\[(\d+)\]/);

                return matched ? Math.max(max, Number(matched[1]) + 1) : max;
            }, 0);
            if (companionCount > 0) {
                companionRows?.ensureRowCount(companionCount);
            }

            targetFields().forEach((field) => {
                const stored = fields[field.name];
                if (stored === undefined || stored === null) {
                    return;
                }
                if (field.type === 'radio') {
                    if (field.value === stored && !field.checked) {
                        field.checked = true;
                        field.dispatchEvent(new Event('change', { bubbles: true }));
                    }

                    return;
                }
                if (field.type === 'checkbox') {
                    field.checked = Boolean(stored);

                    return;
                }
                if (stored !== '') {
                    field.value = stored;
                }
            });
        }
    }

    // 直後に入力が続くことが多いため、都度書き込まず少しまとめてから保存する
    let saveTimer = null;
    const scheduleSave = () => {
        window.clearTimeout(saveTimer);
        saveTimer = window.setTimeout(saveFields, 300);
    };

    form.addEventListener('input', scheduleSave);
    form.addEventListener('change', scheduleSave);
    saveFields();
}

/**
 * 同伴者（連名）の行の追加・削除。
 *
 * 行のHTMLはBladeのテンプレート（wedding/parts/companion_row）を使い回し、
 * __INDEX__・__NUMBER__を実際の番号へ置換して複製する。削除後はname属性を振り直すため、
 * サーバー側には常に companions[0], companions[1] ... と隙間のない添字で届く。
 *
 * @return {{ensureRowCount: (count: number) => void}|null}
 */
function initCompanionRows() {
    const root = document.querySelector('[data-companion-fields]');
    const rowsContainer = root?.querySelector('[data-companion-rows]');
    const template = document.querySelector('[data-companion-row-template]');
    const addButton = root?.querySelector('[data-companion-add]');
    const message = root?.querySelector('[data-companion-message]');
    if (!root || !rowsContainer || !template || !addButton) {
        return null;
    }

    const maxCount = Number(root.dataset.maxCount || 20);
    const rows = () => Array.from(rowsContainer.querySelectorAll('[data-companion-row]'));

    const showMessage = (text) => {
        message.textContent = text;
        message.classList.toggle('hidden', text === '');
    };

    /** 削除で歯抜けになった添字と「○人目」の表示を振り直す */
    const renumber = () => {
        const current = rows();
        current.forEach((row, index) => {
            row.querySelectorAll('[name^="companions["]').forEach((field) => {
                field.name = field.name.replace(/^companions\[\d+\]/, `companions[${index}]`);
            });
            row.querySelectorAll('[id^="companions_"]').forEach((field) => {
                const label = row.querySelector(`label[for="${field.id}"]`);
                field.id = field.id.replace(/^companions_\d+/, `companions_${index}`);
                if (label) {
                    label.htmlFor = field.id;
                }
            });
            const number = row.querySelector('[data-companion-number]');
            if (number) {
                number.textContent = String(index + 1);
            }
            // 1行だけのときは削除ボタンを出さない（「同伴者あり」で0名になるのを防ぐ）
            row.querySelector('[data-companion-remove]')?.classList.toggle('hidden', current.length <= 1);
        });

        addButton.disabled = current.length >= maxCount;
        addButton.classList.toggle('opacity-40', current.length >= maxCount);
    };

    const addRow = () => {
        const index = rows().length;
        if (index >= maxCount) {
            showMessage(`同伴者は${maxCount}名までご入力いただけます。`);

            return false;
        }

        rowsContainer.insertAdjacentHTML(
            'beforeend',
            template.innerHTML.replaceAll('__INDEX__', String(index)).replaceAll('__NUMBER__', String(index + 1))
        );
        showMessage('');
        renumber();

        return true;
    };

    addButton.addEventListener('click', () => {
        if (addRow()) {
            // 追加直後の行が見えるように、増えた行へスクロールする
            rows().at(-1)?.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
        }
    });

    rowsContainer.addEventListener('click', (event) => {
        const removeButton = event.target.closest('[data-companion-remove]');
        if (!removeButton || rows().length <= 1) {
            return;
        }
        removeButton.closest('[data-companion-row]')?.remove();
        showMessage('');
        renumber();

        // 行が消えた分をlocalStorageへ反映させる
        rowsContainer.dispatchEvent(new Event('change', { bubbles: true }));
    });

    renumber();

    return {
        ensureRowCount: (count) => {
            while (rows().length < Math.min(count, maxCount)) {
                if (!addRow()) {
                    break;
                }
            }
        },
    };
}

/** 現在選択されている住所の国 */
function selectedCountry() {
    return document.querySelector('input[name="country"]:checked')?.value ?? COUNTRY_JP;
}

/**
 * 日本／アメリカの切替。住所欄の表示・プレースホルダー・カレンダーの言語を切り替える。
 *
 * @param {(country: string) => void} onChanged 切替後に呼ぶ処理
 */
function initCountrySwitch(onChanged) {
    const options = document.querySelectorAll('[data-country-option]');
    if (options.length === 0) {
        return;
    }

    const apply = () => {
        const country = selectedCountry();
        const isUs = country === COUNTRY_US;

        // 都道府県（日本）と州（アメリカ）のように、国ごとにしか使わない欄を出し入れする
        document.querySelectorAll('[data-country-only]').forEach((element) => {
            element.classList.toggle('hidden', element.dataset.countryOnly !== country);
        });
        document.querySelectorAll('[data-country-note]').forEach((element) => {
            element.classList.toggle('hidden', element.dataset.countryNote !== country);
        });
        document.querySelectorAll('[data-placeholder-jp]').forEach((element) => {
            element.placeholder = isUs ? element.dataset.placeholderUs : element.dataset.placeholderJp;
        });

        const postalNote = document.querySelector('[data-postal-note]');
        if (postalNote) {
            postalNote.textContent = isUs ? postalNote.dataset.noteUs : postalNote.dataset.noteJp;
        }

        onChanged?.(country);
    };

    options.forEach((option) => option.addEventListener('change', apply));
    apply();
}

/**
 * 到着日・出発日のカレンダー（public/assets/vendor/libs/bootstrap-datepicker）。
 *
 * 表示言語は選択中の国に合わせ、値はサーバー側の日付検証に合わせてyyyy-mm-ddで書き込む。
 */
function initDatepickers(country) {
    const jquery = window.jQuery;
    if (!jquery?.fn?.datepicker) {
        return;
    }

    const targets = jquery('.datepicker');
    if (targets.length === 0) {
        return;
    }

    // 国の切替で作り直すため、既に初期化済みなら一度破棄する
    targets.each((_, element) => {
        if (jquery(element).data('datepicker')) {
            jquery(element).datepicker('destroy');
        }
    });

    targets.datepicker({
        format: 'yyyy-mm-dd',
        language: country === COUNTRY_US ? 'en' : 'ja',
        autoclose: true,
        clearBtn: true,
        orientation: 'bottom auto',
        todayHighlight: true,
        // カレンダーのz-indexはこのライブラリが算出して直接styleに書き込む。
        // 追従ヘッダー（z-40）に隠れないよう、既定の10より大きい値を指定する。
        zIndexOffset: 50,
    });

    // カレンダーから選んだ値もlocalStorageへ退避させる（jQueryのvalは変更イベントを飛ばさないため）
    targets.off('changeDate.wedding').on('changeDate.wedding', (event) => {
        event.target.dispatchEvent(new Event('change', { bubbles: true }));
    });
}

/**
 * 郵便番号／ZIP Codeの整形と、住所の自動入力。
 *
 * フォーカスを外した時点（change）で全角数字を半角へ寄せ、数字以外を取り除いてから、
 * 日本は7桁でZipcloud、アメリカは5桁でZippopotam.usに問い合わせ、都道府県／州と市区町村へ反映する。
 * 番地（Street Address）は手入力のため触らない。
 */
function initPostalCodeLookup() {
    const postalInput = document.querySelector('[data-postal-code]');
    if (!postalInput) {
        return;
    }

    const prefectureSelect = document.querySelector('[data-address-prefecture]');
    const stateSelect = document.querySelector('[data-address-state]');
    const cityInput = document.querySelector('[data-address-city]');
    const message = document.querySelector('[data-postal-message]');

    let lastLookedUp = '';

    /**
     * 入力欄の下のメッセージ表示。住所検索APIは数秒かかることがあるため、
     * 検索中は同じ場所に控えめな色で案内を出す。
     */
    const showMessage = (text, isError = true) => {
        if (!message) {
            return;
        }
        message.textContent = text;
        message.classList.toggle('hidden', text === '');
        message.classList.toggle('text-clay-600', isError);
        message.classList.toggle('text-ink-700/60', !isError);
    };

    /** 全角数字・ハイフンを含む入力から数字だけを取り出す */
    const toDigits = (value) =>
        value
            .replace(/[０-９]/g, (char) => String.fromCharCode(char.charCodeAt(0) - 0xfee0))
            .replace(/\D/g, '');

    /** selectに存在する選択肢だけを反映する */
    const applyToSelect = (select, value) => {
        if (!select || !value) {
            return;
        }
        if (Array.from(select.options).some((option) => option.value === value)) {
            select.value = value;
            select.dispatchEvent(new Event('change', { bubbles: true }));
        }
    };

    const applyToCity = (value) => {
        if (!cityInput) {
            return;
        }
        cityInput.value = value;
        cityInput.dispatchEvent(new Event('change', { bubbles: true }));
    };

    /** 日本：Zipcloud。市区町村には市区町村＋町域（例：那覇市港町）を入れる */
    const lookupJapan = async (digits) => {
        const response = await fetch(`https://zipcloud.ibsnet.co.jp/api/search?zipcode=${digits}`);
        if (!response.ok) {
            throw new Error('lookup failed');
        }
        const result = (await response.json())?.results?.[0];
        if (!result) {
            return false;
        }

        applyToSelect(prefectureSelect, result.address1);
        applyToCity(`${result.address2 ?? ''}${result.address3 ?? ''}`);

        return true;
    };

    /** アメリカ：Zippopotam.us。ZIP+4で入力されていても頭5桁で引く */
    const lookupUnitedStates = async (digits) => {
        const response = await fetch(`https://api.zippopotam.us/us/${digits.slice(0, 5)}`);
        if (!response.ok) {
            // 存在しないZIPは404で返る
            return false;
        }
        const place = (await response.json())?.places?.[0];
        if (!place) {
            return false;
        }

        applyToSelect(stateSelect, place.state);
        applyToCity(place['place name'] ?? '');

        return true;
    };

    postalInput.addEventListener('change', async () => {
        const country = selectedCountry();
        const isUs = country === COUNTRY_US;
        const digits = toDigits(postalInput.value).slice(0, isUs ? 9 : 7);
        if (postalInput.value !== digits) {
            postalInput.value = digits;
        }
        showMessage('');

        const isLookupReady = isUs ? digits.length === 5 || digits.length === 9 : digits.length === 7;
        if (!isLookupReady) {
            lastLookedUp = '';

            return;
        }

        // 国を切り替えた直後は同じ番号でも引き直す
        const lookupKey = `${country}:${digits}`;
        if (lookupKey === lastLookedUp) {
            return;
        }
        lastLookedUp = lookupKey;
        showMessage(isUs ? 'Looking up your address…' : '住所を検索しています…', false);

        try {
            const found = isUs ? await lookupUnitedStates(digits) : await lookupJapan(digits);
            showMessage('');
            if (!found) {
                showMessage(
                    isUs
                        ? 'No address found for that ZIP code. Please fill in the fields below.'
                        : '該当する住所が見つかりませんでした。お手数ですが、続く欄をご入力ください。'
                );
            }
        } catch {
            lastLookedUp = '';
            showMessage(
                isUs
                    ? 'Address lookup failed. Please fill in the fields below.'
                    : '住所の自動入力に失敗しました。お手数ですが、続く欄をご入力ください。'
            );
        }
    });
}

/**
 * お祝い画像のアップローダー。
 *
 * 選択された画像はフォーム送信を待たず1枚ずつPOSTし、サーバーは一時保存してジョブを投げるだけ。
 * 縮小・圧縮が終わるまでの状態はポーリングで反映し、uuidをhidden inputとしてフォームに載せる。
 */
function initPhotoUploader() {
    const root = document.querySelector('[data-photo-uploader]');
    if (!root) {
        return;
    }

    const uploadUrl = root.dataset.uploadUrl;
    const statusUrl = root.dataset.statusUrl;
    const destroyUrlTemplate = root.dataset.destroyUrl;
    const maxFiles = Number(root.dataset.maxFiles || 20);
    const maxSize = Number(root.dataset.maxSize || 20 * 1024 * 1024);

    const fileInput = root.querySelector('[data-photo-input]');
    const addButton = root.querySelector('[data-photo-add]');
    const clearButton = root.querySelector('[data-photo-clear]');
    const errorBox = root.querySelector('[data-photo-error]');
    const emptyText = root.querySelector('[data-photo-empty]');
    const list = root.querySelector('[data-photo-list]');
    const tokenBox = root.querySelector('[data-photo-tokens]');
    const sessionTokenInput = root.querySelector('[data-photo-session-token]');
    const itemTemplate = document.querySelector('[data-photo-item-template]');

    /** アップロード元ブラウザの識別トークン。localStorageに持ち続けることで再訪時も自分の画像を扱える */
    const stored = readStore();
    const sessionToken = stored.photoSessionToken || createUuid();
    sessionTokenInput.value = sessionToken;

    /** @type {Array<{uuid: string, name: string, status: string, url: string|null, localUrl: string|null}>} */
    let photos = [];
    let pollTimer = null;
    let pollCount = 0;

    const statusLabel = (status) => {
        switch (status) {
            case 'uploading':
                return 'アップロード中';
            case 'pending':
            case 'processing':
                return '処理中';
            case 'failed':
                return '処理に失敗';
            default:
                return '';
        }
    };

    const showError = (message) => {
        if (!message) {
            errorBox.classList.add('hidden');
            errorBox.textContent = '';

            return;
        }
        errorBox.textContent = message;
        errorBox.classList.remove('hidden');
    };

    const persist = () => {
        patchStore({
            photoSessionToken: sessionToken,
            // ローカルのプレビューURL（blob）はページを閉じると無効になるため保存しない
            photos: photos
                .filter((photo) => photo.status !== 'uploading')
                .map(({ uuid, name, status, url }) => ({ uuid, name, status, url })),
        });
    };

    const thumbUrl = (photo) => {
        if (photo.localUrl) {
            return photo.localUrl;
        }
        if (!photo.url) {
            return '';
        }

        // 変換前後で同じURLのため、完了後はキャッシュを避けて縮小後の画像を取りに行く
        return photo.status === 'ready' ? `${photo.url}?v=ready` : photo.url;
    };

    const render = () => {
        list.textContent = '';
        tokenBox.textContent = '';

        photos.forEach((photo) => {
            const item = itemTemplate.content.firstElementChild.cloneNode(true);
            const thumb = item.querySelector('[data-photo-thumb]');
            const src = thumbUrl(photo);
            if (src) {
                thumb.src = src;
                thumb.alt = photo.name;
            }
            item.querySelector('[data-photo-name]').textContent = photo.name;

            const badge = item.querySelector('[data-photo-status]');
            const label = statusLabel(photo.status);
            badge.textContent = label;
            badge.classList.toggle('hidden', label === '');
            if (photo.status === 'failed') {
                badge.classList.add('bg-clay-600/90');
            }

            const removeButton = item.querySelector('[data-photo-remove]');
            if (photo.status === 'uploading') {
                removeButton.disabled = true;
                removeButton.classList.add('opacity-40');
            } else {
                removeButton.addEventListener('click', () => removePhoto(photo.uuid));
            }

            list.append(item);

            // 変換に失敗した画像は表示できないため、フォームには載せない
            if (photo.status !== 'uploading' && photo.status !== 'failed') {
                const hidden = document.createElement('input');
                hidden.type = 'hidden';
                hidden.name = 'photo_tokens[]';
                hidden.value = photo.uuid;
                tokenBox.append(hidden);
            }
        });

        const hasPhotos = photos.length > 0;
        list.classList.toggle('hidden', !hasPhotos);
        list.classList.toggle('grid', hasPhotos);
        emptyText.classList.toggle('hidden', hasPhotos);
        clearButton.classList.toggle('hidden', !hasPhotos);
        clearButton.classList.toggle('inline-flex', hasPhotos);
    };

    const schedulePoll = () => {
        const pendingUuids = photos
            .filter((photo) => photo.status === 'pending' || photo.status === 'processing')
            .map((photo) => photo.uuid);

        if (pendingUuids.length === 0 || pollTimer || pollCount >= PHOTO_POLL_MAX_COUNT) {
            return;
        }

        pollTimer = window.setTimeout(async () => {
            pollTimer = null;
            pollCount++;
            const fresh = await fetchStatuses(pendingUuids);
            if (fresh) {
                fresh.forEach((updated) => {
                    const target = photos.find((photo) => photo.uuid === updated.uuid);
                    if (target) {
                        target.status = updated.status;
                        target.url = updated.url;
                        if (updated.status === 'ready' && target.localUrl) {
                            // 縮小後の画像に切り替え、blobは解放する
                            URL.revokeObjectURL(target.localUrl);
                            target.localUrl = null;
                        }
                    }
                });
                render();
                persist();
            }
            schedulePoll();
        }, PHOTO_POLL_INTERVAL);
    };

    const fetchStatuses = async (uuids) => {
        try {
            const response = await fetch(statusUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    Accept: 'application/json',
                    'X-CSRF-TOKEN': csrfToken(),
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: JSON.stringify({ session_token: sessionToken, uuids }),
            });
            if (!response.ok) {
                return null;
            }
            const data = await response.json();

            return Array.isArray(data.photos) ? data.photos : null;
        } catch {
            return null;
        }
    };

    const uploadFile = async (file) => {
        const placeholder = {
            uuid: `local-${createUuid()}`,
            name: file.name,
            status: 'uploading',
            url: null,
            localUrl: URL.createObjectURL(file),
        };
        photos.push(placeholder);
        render();

        const body = new FormData();
        body.append('photo', file);
        body.append('session_token', sessionToken);

        try {
            const response = await fetch(uploadUrl, {
                method: 'POST',
                headers: {
                    Accept: 'application/json',
                    'X-CSRF-TOKEN': csrfToken(),
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body,
            });
            const data = await response.json().catch(() => ({}));

            if (!response.ok) {
                throw new Error(data.error || 'アップロードに失敗しました。時間をおいて再度お試しください。');
            }

            placeholder.uuid = data.photo.uuid;
            placeholder.name = data.photo.original_name;
            placeholder.status = data.photo.status;
            placeholder.url = data.photo.url;
        } catch (error) {
            photos = photos.filter((photo) => photo !== placeholder);
            URL.revokeObjectURL(placeholder.localUrl);
            showError(`「${file.name}」${error.message || 'のアップロードに失敗しました。'}`);
        }

        render();
        persist();
    };

    const removePhoto = async (uuid) => {
        const target = photos.find((photo) => photo.uuid === uuid);
        if (!target) {
            return;
        }

        // 通信結果を待たずに画面からは消し、失敗した場合のみ戻す
        const index = photos.indexOf(target);
        photos = photos.filter((photo) => photo !== target);
        render();
        persist();

        const body = new FormData();
        body.append('_method', 'DELETE');
        body.append('session_token', sessionToken);

        try {
            const response = await fetch(destroyUrlTemplate.replace('__UUID__', uuid), {
                method: 'POST',
                headers: {
                    Accept: 'application/json',
                    'X-CSRF-TOKEN': csrfToken(),
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body,
            });
            if (!response.ok) {
                throw new Error('削除に失敗しました。');
            }
            if (target.localUrl) {
                URL.revokeObjectURL(target.localUrl);
            }
        } catch {
            photos.splice(index, 0, target);
            render();
            persist();
            showError('画像の削除に失敗しました。時間をおいて再度お試しください。');
        }
    };

    addButton.addEventListener('click', () => fileInput.click());

    clearButton.addEventListener('click', async () => {
        showError('');
        const targets = photos.filter((photo) => photo.status !== 'uploading').map((photo) => photo.uuid);
        for (const uuid of targets) {
            await removePhoto(uuid);
        }
    });

    fileInput.addEventListener('change', async () => {
        showError('');
        const files = Array.from(fileInput.files || []);
        // 同じファイルを選び直せるよう、読み取り後すぐに値をリセットする
        fileInput.value = '';

        for (const file of files) {
            if (photos.length >= maxFiles) {
                showError(`お祝い画像は${maxFiles}枚までアップロードいただけます。`);
                break;
            }
            if (file.size > maxSize) {
                showError(`「${file.name}」は${Math.round(maxSize / 1024 / 1024)}MBを超えているためアップロードできません。`);
                continue;
            }
            if (file.type && !ALLOWED_IMAGE_TYPES.includes(file.type)) {
                showError(`「${file.name}」は対応していない形式です。JPEG・PNG・WebP・GIF・HEIC形式の画像をお選びください。`);
                continue;
            }

            // 同時に送るとPHPのアップロード上限や回線を圧迫するため、1枚ずつ順番に送る
            await uploadFile(file);
        }

        schedulePoll();
    });

    // 再訪時：localStorageに残っている画像がサーバー側にもあるか確認してから復元する
    const restore = async () => {
        const savedPhotos = Array.isArray(stored.photos) ? stored.photos : [];
        if (savedPhotos.length === 0) {
            render();

            return;
        }

        const fresh = await fetchStatuses(savedPhotos.map((photo) => photo.uuid));
        if (!fresh) {
            return;
        }

        photos = fresh.map((photo) => ({
            uuid: photo.uuid,
            name: photo.original_name,
            status: photo.status,
            url: photo.url,
            localUrl: null,
        }));
        render();
        persist();
        schedulePoll();
    };

    render();
    restore();
}
