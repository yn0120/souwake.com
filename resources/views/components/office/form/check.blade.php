{{--
    チェックボックス / ラジオボタン。

    要件にしていた2点をここで担保する。
      1. ラベル文字の高さがチェック（丸/四角）の中央に揃う
         → label を inline-flex + items-center にしているため、行の高さに関係なく中央で揃う。
            「チェックの上端とラベル文字の上端が揃って、下に余白が余る」状態にはならない。
      2. ラベルがクリックできることを見た目で示す
         → label 全体に cursor-pointer を付け、input を label で包む。
            包んでいるので for は付けない（for + 入れ子は環境によって2回トグルされるため）。

      <x-office.form.check type="radio" name="companion_flag" value="1" id="companion_flag_1" :checked="..." >あり</x-office.form.check>
--}}
@props(['type' => 'checkbox'])

<label {{ $attributes->only('class')->merge(['class' => 'inline-flex cursor-pointer items-center gap-2']) }}>
    <input type="{{ $type }}"
           {{ $attributes->except('class')->merge([
               'class' => 'size-4 shrink-0 cursor-pointer border-default text-brand focus:ring-2 focus:ring-brand-medium '
                   .($type === 'radio' ? 'rounded-full' : 'rounded'),
           ]) }}>
    <span class="text-sm text-heading">{{ $slot }}</span>
</label>
