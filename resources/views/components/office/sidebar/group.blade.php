{{--
    サイドメニューの折りたたみグループ。

    開閉はFlowbiteの data-collapse-toggle に任せる（対象の hidden クラスを付け外しする）。
    このコンポーネントを $slot の中に入れれば3階層目も作れる。
        <x-office.sidebar.group label="A">
            <x-office.sidebar.group label="B" :level="2">
                <x-office.sidebar.link :level="3" href="...">C</x-office.sidebar.link>
--}}
@props(['label', 'icon' => null, 'active' => false, 'level' => 1])

@php
    $id = 'office-nav-'.substr(md5($label.$level), 0, 8);
    $indent = [1 => 'pl-3', 2 => 'pl-9', 3 => 'pl-14'][$level] ?? 'pl-3';
@endphp

<li>
    <button type="button"
            data-collapse-toggle="{{ $id }}"
            aria-expanded="{{ $active ? 'true' : 'false' }}"
            aria-controls="{{ $id }}"
            @class([
                'group/nav flex w-full cursor-pointer items-center gap-3 rounded-lg py-2 pr-3 text-sm',
                'text-sidebar-fg transition-colors hover:bg-sidebar-hover hover:text-white',
                $indent,
            ])>
        @if ($icon)
            <x-office.icon :name="$icon" class="size-5 shrink-0" />
        @endif
        <span class="grow truncate text-left group-data-[sidebar=collapsed]/layout:xl:hidden">{{ $label }}</span>
        <x-office.icon name="chevron-down"
                       class="size-4 shrink-0 transition-transform group-aria-expanded/nav:rotate-180 group-data-[sidebar=collapsed]/layout:xl:hidden" />
    </button>
    <ul id="{{ $id }}" @class(['space-y-1 py-1', 'hidden' => ! $active])>
        {{ $slot }}
    </ul>
</li>
