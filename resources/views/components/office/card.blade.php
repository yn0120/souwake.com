{{-- カード。ページの中身はほぼこの中に入る。 --}}
@props(['title' => null, 'padding' => 'p-5 sm:p-6', 'heading' => 'h2'])

<div {{ $attributes->merge(['class' => "rounded-xl bg-white shadow-sm {$padding}"]) }}>
    @if ($title || isset($actions))
        <div class="mb-4 flex flex-wrap items-center justify-between gap-2">
            @if ($title)
                {{-- 見出しレベルは既定でh2。1ページに複数カードが並ぶため、h1はページ側で決める。 --}}
                <{{ $heading }} class="text-lg font-semibold text-heading">{{ $title }}</{{ $heading }}>
            @endif
            @isset ($actions)
                <div class="flex flex-wrap items-center gap-2">{{ $actions }}</div>
            @endisset
        </div>
    @endif

    {{ $slot }}
</div>
