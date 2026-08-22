{{-- 複数行入力。class に autoHeight を足すと内容に合わせて高さが伸びる（resources/js/office.js）。 --}}
@php
    $fieldName = $attributes->get('name');
    $invalid = $fieldName && $errors->has($fieldName);
@endphp

<textarea {{ $attributes->merge([
    'rows' => 3,
    'class' => 'w-full rounded-lg bg-white px-3 py-2 text-sm text-heading placeholder:text-body focus:ring-2 '
        .($invalid
            ? 'border-danger focus:border-danger focus:ring-danger-medium'
            : 'border-default focus:border-brand focus:ring-brand-medium'),
]) }}>{{ $slot }}</textarea>
