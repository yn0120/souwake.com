{{-- 見出しセル。align に 'center' / 'right' を渡すと揃えが変わる。 --}}
@props(['align' => 'left'])

<th scope="col" {{ $attributes->class([
        'border border-default px-3 py-2 font-bold whitespace-nowrap text-white',
        'text-left' => $align === 'left',
        'text-center' => $align === 'center',
        'text-right' => $align === 'right',
    ]) }}>
    {{ $slot }}
</th>
