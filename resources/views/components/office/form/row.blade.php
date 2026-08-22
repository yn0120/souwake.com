{{--
    入力フォームの1行（ラベル + 入力欄 + エラー + 補足）。

    スマホでは縦積み、md以上で「ラベル3 : 入力9」の2カラムになる（モバイルファースト）。

      <x-office.form.row label="権限名" for="name" required name="name" help="...">
          <x-office.form.input name="name" id="name" :value="old('name')" />
      </x-office.form.row>

    プロパティ
      for      : ラベルの for。渡すとラベルが cursor-pointer になる
      required : 先頭に「※」を出す
      name     : エラー表示に使うキー（配列も可）
      help     : 入力欄の下に出す補足文（HTMLをそのまま出すので、変数を混ぜる時はエスケープ済みの値を渡す）
--}}
@props([
    'label' => null,
    'for' => null,
    'required' => false,
    'name' => null,
    'help' => null,
])

<div {{ $attributes->class(['grid gap-1 py-2 md:grid-cols-12 md:gap-4']) }}>
    <label @if ($for) for="{{ $for }}" @endif
           @class([
               'flex items-center text-sm font-bold text-heading md:col-span-3 md:py-2',
               'cursor-pointer' => (bool) $for,
           ])>
        @if ($required)
            <span class="text-danger">※&nbsp;</span>
        @endif
        {{ $label }}
    </label>

    <div class="md:col-span-9 md:py-1.5">
        {{ $slot }}

        @if ($name)
            <x-office.form.error :name="$name" />
        @endif

        @if ($help)
            <p class="mt-1 text-xs break-words text-body">{!! $help !!}</p>
        @endif
    </div>
</div>
