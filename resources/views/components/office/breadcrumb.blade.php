{{--
    パンくずリスト。現在ページはリンクにしない（aria-current="page" を付ける）。

    使い方（レイアウトの breadcrumb スロットに入れる）:
        <x-slot:breadcrumb>
            <x-office.breadcrumb>
                <x-office.breadcrumb.item :href="route('officeTop', [], false)">ホーム</x-office.breadcrumb.item>
                <x-office.breadcrumb.item :href="route('officeRoleIndex', [], false)">権限管理</x-office.breadcrumb.item>
                <x-office.breadcrumb.item>権限一覧</x-office.breadcrumb.item>
            </x-office.breadcrumb>
        </x-slot:breadcrumb>
--}}
<nav aria-label="パンくず" class="mb-4">
    <ol class="flex flex-wrap items-center gap-1 text-sm">
        {{ $slot }}
    </ol>
</nav>
