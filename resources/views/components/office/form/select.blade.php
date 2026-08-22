{{--
    プルダウン。<option> はスロットに書く。

    幅は既定で親いっぱい。中身に合わせたい時は :full-width="false" を渡す
    （classで w-auto を足しても、Tailwindの出力順によっては w-full に負けるため）。
--}}
@props(['fullWidth' => true])

@php
    $fieldName = $attributes->get('name');
    $invalid = $fieldName && $errors->has($fieldName);

    $classes = ($fullWidth ? 'w-full' : 'w-auto')
        .' cursor-pointer rounded-lg bg-white px-3 py-2 text-sm text-heading focus:ring-2 '
        .($invalid
            ? 'border-danger focus:border-danger focus:ring-danger-medium'
            : 'border-default focus:border-brand focus:ring-brand-medium');
@endphp

<select {{ $attributes->merge(['class' => $classes]) }}>{{ $slot }}</select>
