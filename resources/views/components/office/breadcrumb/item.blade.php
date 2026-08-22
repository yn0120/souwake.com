{{-- パンくずの1項目。href を渡さない＝現在ページ扱い（非リンク）。 --}}
@props(['href' => null])

<li class="flex items-center gap-1">
    @if ($href)
        <a href="{{ $href }}" class="font-medium text-brand hover:text-brand-strong hover:underline">{{ $slot }}</a>
        <x-office.icon name="chevron-right" class="size-3.5 text-body" />
    @else
        <span aria-current="page" class="font-semibold text-heading">{{ $slot }}</span>
    @endif
</li>
