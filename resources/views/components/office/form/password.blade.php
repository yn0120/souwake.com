{{--
    パスワード入力。右端の目のアイコンで表示/非表示を切り替える（resources/js/office.js）。

      <x-office.form.password name="password" id="password" placeholder="············" />
--}}
@php
    $fieldName = $attributes->get('name');
    $invalid = $fieldName && $errors->has($fieldName);
    $inputId = $attributes->get('id') ?? $fieldName;
@endphp

<div class="relative">
    <input {{ $attributes->merge([
        'type' => 'password',
        'class' => 'w-full rounded-lg bg-white px-3 py-2 pr-11 text-sm text-heading placeholder:text-body focus:ring-2 '
            .($invalid
                ? 'border-danger focus:border-danger focus:ring-danger-medium'
                : 'border-default focus:border-brand focus:ring-brand-medium'),
    ]) }}>

    <button type="button"
            data-password-toggle="{{ $inputId }}"
            aria-pressed="false"
            aria-label="パスワードを表示する"
            class="group absolute inset-y-0 right-0 flex cursor-pointer items-center px-3 text-body hover:text-heading">
        <x-office.icon name="eye" class="size-5 group-aria-pressed:hidden" />
        <x-office.icon name="eye-slash" class="hidden size-5 group-aria-pressed:block" />
    </button>
</div>
