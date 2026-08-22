{{--
    詳細・確認画面の1行（ラベル + 値）。入力欄が無いのでラベルは cursor-pointer にしない。

      <x-office.detail-row label="権限名">{{ $record->name }}</x-office.detail-row>
--}}
@props(['label'])

<div class="grid gap-1 border-b border-default py-2 last:border-b-0 md:grid-cols-12 md:gap-4">
    <div class="flex items-center text-sm font-bold text-heading md:col-span-3 md:py-1">{{ $label }}</div>
    <div class="text-sm break-words text-heading md:col-span-9 md:py-1">{{ $slot }}</div>
</div>
