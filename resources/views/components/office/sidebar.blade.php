{{--
    サイドメニュー。

    - スマホ・タブレット（xl未満）: Flowbiteのドロワーとして開閉する（背景の暗幕もFlowbite任せ）
    - PC（xl以上）: 常時表示。ヘッダー左の «  ボタンでアイコンのみの細い表示に切り替わる
      （状態は <body data-sidebar> に入り、localStorageへ保存される）
--}}
<aside id="office-sidebar"
       aria-label="メインメニュー"
       class="fixed inset-y-0 left-0 z-40 w-64 -translate-x-full overflow-y-auto bg-sidebar
              transition-[transform,width] duration-200 xl:translate-x-0
              group-data-[sidebar=collapsed]/layout:xl:w-16">
    {{-- ブランド --}}
    <div class="flex h-14 items-center gap-2 px-3 group-data-[sidebar=collapsed]/layout:xl:justify-center">
        <a href="{{ route('officeTop', [], false) }}"
           class="flex min-w-0 items-center gap-2 text-white group-data-[sidebar=collapsed]/layout:xl:hidden">
            <img src="{{ asset('assets/img/kokopelli.jpg') }}" alt="{{ config('app.name') }}"
                 class="size-8 shrink-0 rounded-lg bg-white object-cover">
            <span class="truncate text-lg font-bold">{{ config('app.name') }}</span>
        </a>

        {{-- PC: アイコンのみ表示との切り替え --}}
        <button type="button"
                data-sidebar-toggle
                aria-label="メニューの幅を切り替える"
                class="ml-auto hidden cursor-pointer rounded-lg p-1.5 text-sidebar-muted
                       transition-colors hover:bg-sidebar-hover hover:text-white xl:block
                       group-data-[sidebar=collapsed]/layout:xl:ml-0">
            <x-office.icon name="chevron-double-left"
                           class="size-4 group-data-[sidebar=collapsed]/layout:hidden" />
            <x-office.icon name="chevron-double-right"
                           class="hidden size-4 group-data-[sidebar=collapsed]/layout:block" />
        </button>

        {{-- スマホ: ドロワーを閉じる --}}
        <button type="button"
                data-drawer-hide="office-sidebar"
                aria-controls="office-sidebar"
                aria-label="メニューを閉じる"
                class="ml-auto cursor-pointer rounded-lg p-1.5 text-sidebar-muted
                       transition-colors hover:bg-sidebar-hover hover:text-white xl:hidden">
            <x-office.icon name="close" class="size-5" />
        </button>
    </div>

    {{-- メニュー本体。権限（Auth::user()->routes()）を持たない項目は出さない。 --}}
    <ul class="space-y-1 px-3 pb-6">
        {{-- 権限管理 --}}
        @if (preg_grep('/^officeRole.*$/', Auth::user()->routes()))
            <x-office.sidebar.group label="権限管理" icon="shield"
                                    :active="request()->route()->named('*officeRole*')">
                @if (in_array('officeRoleIndex*', Auth::user()->routes()))
                    <x-office.sidebar.link :level="2" :href="route('officeRoleIndex', [], false)"
                                           :active="request()->route()->named('officeRoleIndex')">一覧</x-office.sidebar.link>
                @endif
                @if (in_array('officeRoleCreate*', Auth::user()->routes()))
                    <x-office.sidebar.link :level="2" :href="route('officeRoleCreateInput', [], false)"
                                           :active="request()->route()->named('officeRoleCreate*')">登録</x-office.sidebar.link>
                @endif
                @if (in_array('officeRoleRouteEdit*', Auth::user()->routes()))
                    <x-office.sidebar.link :level="2" :href="route('officeRoleRouteEditInput', [], false)"
                                           :active="request()->route()->named('officeRoleRouteEdit*')">付与</x-office.sidebar.link>
                @endif
            </x-office.sidebar.group>
        @endif

        {{-- 管理者管理 --}}
        @if (preg_grep('/^officeAdmin.*$/', Auth::user()->routes()))
            <x-office.sidebar.group label="管理者管理" icon="user"
                                    :active="request()->route()->named('*officeAdmin*')">
                @if (in_array('officeAdminIndex*', Auth::user()->routes()))
                    <x-office.sidebar.link :level="2" :href="route('officeAdminIndex', [], false)"
                                           :active="request()->route()->named('officeAdminIndex')">一覧</x-office.sidebar.link>
                @endif
                @if (in_array('officeAdminCreate*', Auth::user()->routes()))
                    <x-office.sidebar.link :level="2" :href="route('officeAdminCreateInput', [], false)"
                                           :active="request()->route()->named('officeAdminCreate*')">登録</x-office.sidebar.link>
                @endif
            </x-office.sidebar.group>
        @endif

        {{-- 出欠回答管理（結婚式サイトから届いた回答） --}}
        @if (in_array('officeWeddingRsvpIndex*', Auth::user()->routes()))
            <x-office.sidebar.link icon="envelope" :href="route('officeWeddingRsvpIndex', [], false)"
                                   :active="request()->route()->named('*officeWeddingRsvp*')">出欠回答管理</x-office.sidebar.link>
        @endif

        @if (preg_grep('/^officePasswordManager.*$/', Auth::user()->routes()))
            <x-office.sidebar.link icon="key" :href="route('officePasswordManagerIndex', [], false)"
                                   :active="request()->route()->named('*officePasswordManager*')">パスワード管理</x-office.sidebar.link>
        @endif

        @if (preg_grep('/^officeBudget.*$/', Auth::user()->routes()))
            <x-office.sidebar.link icon="wallet" :href="route('officeBudgetCreateInput', [], false)"
                                   :active="request()->route()->named('*officeBudget*')">家計簿</x-office.sidebar.link>
        @endif

        @if (preg_grep('/^officeProfile.*$/', Auth::user()->routes()))
            <x-office.sidebar.link icon="user-circle" :href="route('officeProfileEditInput', [], false)"
                                   :active="request()->route()->named('*officeProfile*')">プロフィール編集</x-office.sidebar.link>
        @endif
    </ul>
</aside>
