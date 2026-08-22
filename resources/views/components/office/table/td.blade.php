{{--
    データセル。

    clickable を付けると「押せるセル」の見た目になる（カーソルと下地）。
    実際の動作は中に <label> や <a> を置く。セル全体を押せるようにしたい時は padding="" を渡し、
    中の要素側で余白を作る（セルに余白が残っていると、その部分だけ反応しない）。
    ※ 余白をclassで上書きしないのは、Tailwindでは p-0 と px-3 の勝敗が出力順で決まり
      期待どおりにならないため。
--}}
@props([
    'align' => 'left',
    'clickable' => false,
    'nowrap' => true,
    'padding' => 'px-3 py-2',
])

<td {{ $attributes->class([
        'border border-default text-heading',
        $padding => (bool) $padding,
        'whitespace-nowrap' => $nowrap,
        'text-left' => $align === 'left',
        'text-center' => $align === 'center',
        'text-right' => $align === 'right',
        'cursor-pointer transition-colors hover:bg-brand-softer' => $clickable,
    ]) }}>
    {{ $slot }}
</td>
