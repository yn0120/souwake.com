{{--
    日付入力。FlowbiteのdatepickerをJSではなく属性で有効にする（初期化コード不要）。
    日本語ロケールは resources/js/office/datepicker-locale.js で登録している。
    保存形式は Y/m/d のまま（コントローラー側の解釈を変えないため）。
--}}
@php
    $fieldName = $attributes->get('name');
    $invalid = $fieldName && $errors->has($fieldName);
@endphp

<input datepicker
       datepicker-autohide
       datepicker-buttons
       datepicker-format="yyyy/mm/dd"
       datepicker-language="ja"
       datepicker-orientation="bottom"
       autocomplete="off"
       {{ $attributes->merge([
           'type' => 'text',
           'class' => 'w-full rounded-lg bg-white px-3 py-2 text-sm text-heading placeholder:text-body focus:ring-2 '
               .($invalid
                   ? 'border-danger focus:border-danger focus:ring-danger-medium'
                   : 'border-default focus:border-brand focus:ring-brand-medium'),
       ]) }}>
