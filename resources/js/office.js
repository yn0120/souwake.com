/*
 * 管理画面の全ページで読み込むJS。
 *
 * ここに入れるのは「どのページにあっても壊れない」共通の入力補助だけ。
 * ページ固有の処理は resources/js/office/*.js に分け、そのページだけが @vite で読み込む。
 */

// datepickerの日本語ロケールはFlowbite本体の自動初期化より先に登録する必要がある。
import './datepicker-locale';

// Flowbite本体。読み込むだけで data-collapse-toggle / data-drawer-target / data-dropdown-toggle /
// datepicker などのデータ属性を自動で有効にする（初期化コードを書く必要は無い）。
import 'flowbite';

// --- レイアウト: サイドメニューの開閉（PC）---

// PCではアイコンだけの細い状態と通常状態を切り替える。状態は <body data-sidebar> に持たせ、
// 見た目の分岐はTailwindの group-data-* バリアントで書く（JSからクラスを足さない）。
const SIDEBAR_STORAGE_KEY = 'office.sidebar';

function applySidebarState(state) {
    document.body.dataset.sidebar = state;
    try {
        localStorage.setItem(SIDEBAR_STORAGE_KEY, state);
    } catch {
        // プライベートモード等でlocalStorageが使えない場合は、状態を保存しないだけで動作は続ける
    }
}

try {
    const saved = localStorage.getItem(SIDEBAR_STORAGE_KEY);
    if (saved === 'collapsed' || saved === 'expanded') {
        document.body.dataset.sidebar = saved;
    }
} catch {
    // 同上
}

document.addEventListener('click', (e) => {
    const toggle = e.target.closest('[data-sidebar-toggle]');
    if (! toggle) {
        return;
    }
    applySidebarState(document.body.dataset.sidebar === 'collapsed' ? 'expanded' : 'collapsed');
});

// PCではサイドメニューが常時見えているのに、Flowbiteのドロワー初期化が
// aria-hidden="true" を付けてしまい、読み上げから消えてしまう。
// ドロワーとして扱うのはxl未満だけなので、xl以上では外し直す。
function syncSidebarAria() {
    const sidebar = document.getElementById('office-sidebar');
    if (! sidebar) {
        return;
    }
    if (window.matchMedia('(min-width: 80rem)').matches) {
        sidebar.removeAttribute('aria-hidden');
    }
}

// Flowbiteの初期化（load時）より後に走らせる必要があるため、同じloadイベントに後から登録する。
window.addEventListener('load', syncSidebarAria);
window.addEventListener('resize', syncSidebarAria);

// --- レイアウト: ヘッダーをスクロールで隠す ---

// <body data-header-behavior="auto-hide"> の時だけ、下スクロールでヘッダーを画面外へ、
// 上スクロールで戻す。固定のままにしたい場合は data-header-behavior="fixed"（既定）。
if (document.body.dataset.headerBehavior === 'auto-hide') {
    const header = document.querySelector('[data-office-header]');
    let lastY = window.scrollY;

    window.addEventListener('scroll', () => {
        if (! header) {
            return;
        }
        const y = window.scrollY;
        // ごく小さなスクロールで出入りするとちらつくため、しきい値を設ける
        if (Math.abs(y - lastY) < 8) {
            return;
        }
        header.classList.toggle('-translate-y-full', y > lastY && y > header.offsetHeight);
        lastY = y;
    }, { passive: true });
}

// --- フォーム: textareaの高さ自動調整 ---

function fitTextarea(el) {
    el.style.height = 'auto';
    el.style.height = `${Math.max(el.scrollHeight, 50)}px`;
}

document.addEventListener('input', (e) => {
    if (e.target.matches('textarea.autoHeight')) {
        fitTextarea(e.target);
    }
});

document.querySelectorAll('textarea.autoHeight').forEach(fitTextarea);

// --- フォーム: 入力値の自動整形 ---

// クラス名 -> 変換関数。views側は入力欄に class="emailFmt" のように付けるだけでよい。
const FORMATTERS = {
    // 全角英数字を半角へ
    castHalfWidthDigit: (v) => v.replace(/[Ａ-Ｚａ-ｚ０-９＠＿＋]/g,
        (s) => String.fromCharCode(s.charCodeAt(0) - 0xfee0)),
    // 空白を削除
    trimSpace: (v) => v.replace(/\s+/g, ''),
    // 数字のみ
    onlyNumber: (v) => v.replace(/\D/g, ''),
    // 数字とハイフン・コロンのみ
    numberWithHyphen: (v) => v.replace(/[^0-9\-:]/g, ''),
    // 英数字のみ
    onlyNumberAlpha: (v) => v.replace(/[^a-zA-Z0-9]/g, ''),
    // ひらがなのみ
    onlyHiragana: (v) => v.replace(/[^ぁ-んー　]/g, ''),
    // カタカナのみ
    onlyKatakana: (v) => v.replace(/[^ァ-ヶー　]/g, ''),
    // 半角カタカナのみ
    onlyHalfKatakana: (v) => v.replace(/[^ｧ-ﾟ ]/g, ''),
    // メールアドレスとして有効な文字のみ
    emailFmt: (v) => v.replace(/[^a-zA-Z0-9.@_+\-]/g, ''),
};

function formatInput(el) {
    let value = el.value;

    Object.keys(FORMATTERS).forEach((name) => {
        if (el.classList.contains(name)) {
            value = FORMATTERS[name](value);
        }
    });

    // maxlengthはブラウザが弾いてくれるが、上の変換で伸びる場合があるためここでも切る
    const maxLength = Number(el.getAttribute('maxlength'));
    if (maxLength > 0 && value.length > maxLength) {
        value = value.slice(0, maxLength);
    }

    if (value !== el.value) {
        el.value = value;
    }
}

document.addEventListener('change', (e) => {
    if (e.target.matches('input')) {
        formatInput(e.target);
    }
});

document.querySelectorAll('input').forEach(formatInput);

// --- フォーム: パスワードの表示/非表示 ---

// <button data-password-toggle="入力欄のid"> を押すと type を切り替える。
// Sneatのform-password-toggleと同じ役割。アイコンの出し分けはCSS（aria-pressed）で行う。
document.addEventListener('click', (e) => {
    const toggle = e.target.closest('[data-password-toggle]');
    if (! toggle) {
        return;
    }
    const input = document.getElementById(toggle.dataset.passwordToggle);
    if (! input) {
        return;
    }
    const shown = input.type === 'text';
    input.type = shown ? 'password' : 'text';
    toggle.setAttribute('aria-pressed', shown ? 'false' : 'true');
    toggle.setAttribute('aria-label', shown ? 'パスワードを表示する' : 'パスワードを隠す');
});

// --- フォーム: 検索条件アコーディオンの開閉状態をGETパラメーターに残す ---

// 開いたまま検索した時に、検索後も開いた状態で返ってくるようにする。
document.addEventListener('click', (e) => {
    if (! e.target.closest('#headingSearch')) {
        return;
    }
    const holder = document.querySelector('[name="accordion"]');
    if (holder) {
        holder.value = holder.value === '1' ? '0' : '1';
    }
});

// --- フォーム: 送信ボタンの連打防止（POSTのみ）---

document.addEventListener('submit', (e) => {
    const form = e.target;
    if ((form.getAttribute('method') || '').toLowerCase() !== 'post') {
        return;
    }
    // 無効化はイベントを抜けた後（= ブラウザが送信値を確定させた後）に行う。
    // submitハンドラー内でdisabledにすると、押されたボタン自身の name/value
    // （確認画面の「前のページに戻る」= back=1 など）が送信されなくなる。
    setTimeout(() => {
        form.querySelectorAll('button[type="submit"]').forEach((button) => {
            button.disabled = true;
            button.classList.add('cursor-not-allowed', 'opacity-60');
        });
    }, 0);
});

// ブラウザの「戻る」でキャッシュから復元された時は、上で無効化したボタンを戻す
// （そのままだと押せないボタンが残った画面が表示される）。
window.addEventListener('pageshow', (e) => {
    if (! e.persisted) {
        return;
    }
    document.querySelectorAll('button[type="submit"][disabled]').forEach((button) => {
        button.disabled = false;
        button.classList.remove('cursor-not-allowed', 'opacity-60');
    });
});

// --- 一覧: 表示件数の変更 ---

document.addEventListener('change', (e) => {
    if (e.target.id !== 'perPage') {
        return;
    }
    const url = new URL(window.location.href);
    url.searchParams.set('per_page', e.target.value);
    window.location.href = url.toString();
});

// --- メモ: 別ウィンドウで開く ---

// <button data-memo-url="..."> を押すと、画面の右隣に細長い別ウィンドウを開く。
// 以前は各viewにインラインのonclickを書いていたが、同じ文字列が4か所にあったため共通化した。
document.addEventListener('click', (e) => {
    const trigger = e.target.closest('[data-memo-url]');
    if (! trigger) {
        return;
    }
    window.open(
        trigger.dataset.memoUrl,
        'memo',
        `width=600,height=${window.innerHeight},scrollbars=yes,left=${window.screen.width},top=0`,
    );
});

// --- その他 ---

document.addEventListener('click', (e) => {
    if (e.target.closest('.closeTab')) {
        e.preventDefault();
        window.close();
    }
});

// 確認ダイアログ。views側から onsubmit="return confirmDelete()" の形で呼ぶ。
window.confirmDelete = () => window.confirm('本当に削除しますか？');
window.confirmSend = () => window.confirm('本当に送信しますか？');
