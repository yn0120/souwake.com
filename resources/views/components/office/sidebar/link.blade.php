{{--
    サイドメニューの1項目（リンク）。

    $level は入れ子の深さ（1〜3）。深さに応じて左のインデントだけを変える。
--}}
@props(['href', 'icon' => null, 'active' => false, 'level' => 1])

@php
    $indent = [1 => 'pl-3', 2 => 'pl-9', 3 => 'pl-14'][$level] ?? 'pl-3';
@endphp

<li>
    <a href="{{ $href }}"
       @class([
           'flex items-center gap-3 rounded-lg py-2 pr-3 text-sm transition-colors',
           $indent,
           'bg-brand text-white' => $active,
           'text-sidebar-fg hover:bg-sidebar-hover hover:text-white' => ! $active,
       ])
       @if ($active) aria-current="page" @endif>
        @if ($icon)
            <x-office.icon :name="$icon" class="size-5 shrink-0" />
        @endif
        <span class="truncate group-data-[sidebar=collapsed]/layout:xl:hidden">{{ $slot }}</span>
    </a>
</li>
