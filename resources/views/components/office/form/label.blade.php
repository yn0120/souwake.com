{{--
    単体のラベル（検索条件など、入力欄の上に置く場合）。

    for を渡した時だけ cursor-pointer にする。クリックで入力欄にフォーカスが移るのに
    それが見た目で分からない、という状態を避けるため。

    inline を渡すと下マージンを付けず横並び用になる（「表示件数 :」のような使い方）。
    ※ 既定クラスと衝突するユーティリティ（mb-0 など）をclassで後から足しても、
      Tailwindは出力順で勝敗が決まるため効かないことがある。そのためプロパティで切り替える。
--}}
@props(['for' => null, 'inline' => false])

@php
    $classes = 'text-sm font-medium text-heading'
        .($inline ? ' inline-block' : ' mb-1 block')
        .($for ? ' cursor-pointer' : '');
@endphp

<label @if ($for) for="{{ $for }}" @endif {{ $attributes->merge(['class' => $classes]) }}>
    {{ $slot }}
</label>
