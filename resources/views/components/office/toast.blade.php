{{--
    非同期処理の結果を右上に出すトースト置き場。

    表示は resources/js/office/toast.js が行う（idを渡して使う）。
      <x-office.toast id="bdg-alert" />
--}}
@props(['id'])

<div id="{{ $id }}" class="fixed top-4 right-4 z-50 hidden w-full max-w-sm">
    <p id="{{ $id }}-message" role="alert"></p>
</div>
