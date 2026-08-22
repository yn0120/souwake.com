{{--
    テキスト入力。name を渡しておくと、その項目にエラーがある時だけ枠が赤くなる。
    name / value / id / placeholder などはそのまま <input> に渡る。
--}}
@php
    $fieldName = $attributes->get('name');
    $invalid = $fieldName && $errors->has($fieldName);
@endphp

<input {{ $attributes->merge([
    'type' => 'text',
    'class' => 'w-full rounded-lg bg-white px-3 py-2 text-sm text-heading placeholder:text-body focus:ring-2 '
        .($invalid
            ? 'border-danger focus:border-danger focus:ring-danger-medium'
            : 'border-default focus:border-brand focus:ring-brand-medium'),
]) }}>
