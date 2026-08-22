{{--
    ヘッダー。

    PC（xl以上）はサイドメニューが常時見えているため、Sneat時代と同じく非表示にしている
    （表示したい場合はこのコンポーネントから xl:hidden を外す）。

    固定 / スクロールで隠す の切り替えは <body data-header-behavior>:
      fixed     … 常に上へ固定（既定。現状の見た目）
      auto-hide … 下スクロールで隠れ、上スクロールで戻る（resources/js/office.js）
--}}
<header data-office-header
        class="sticky top-0 z-30 flex h-14 shrink-0 items-center gap-3 border-b border-default
               bg-white px-4 transition-transform duration-200 xl:hidden">
    {{-- ハンバーガー。ドロワーの開閉はFlowbiteに任せる。 --}}
    <button type="button"
            data-drawer-target="office-sidebar"
            data-drawer-toggle="office-sidebar"
            data-drawer-placement="left"
            aria-controls="office-sidebar"
            aria-label="メニューを開く"
            class="cursor-pointer rounded-lg p-2 text-body transition-colors hover:bg-neutral-tertiary hover:text-heading">
        <x-office.icon name="menu" class="size-6" />
    </button>

    {{-- 左側メニュー --}}
    {{ $left ?? '' }}

    {{-- 右側メニュー --}}
    <div class="ml-auto flex items-center gap-2">
        {{ $right ?? '' }}
    </div>
</header>
