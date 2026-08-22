{{--
    ボタン。href を渡すと <a>、渡さなければ <button> になる。

      <x-office.button variant="success" type="submit" class="w-full">確認する</x-office.button>
      <x-office.button variant="outline-info" size="icon" :href="..." title="詳細">
          <x-office.icon name="info" class="size-4" />
      </x-office.button>

    JSでボタンを生成するページ（パスワード管理）の対応クラスは resources/js/office/ui.js。
    色そのものは office.css の @theme を変えれば両方追従する。
--}}
@props([
    'variant' => 'primary',
    'size' => 'md',
    'href' => null,
    'type' => 'button',
])

@php
    $base = 'inline-flex cursor-pointer items-center justify-center gap-1.5 rounded-lg border '
        .'font-medium transition-colors focus:outline-none focus:ring-2 '
        .'disabled:cursor-not-allowed disabled:opacity-60';

    $sizes = [
        'sm' => 'px-3 py-1.5 text-xs',
        'md' => 'px-4 py-2 text-sm',
        'lg' => 'px-5 py-2.5 text-base',
        // アイコンだけの正方形ボタン（一覧の操作列で使う）
        'icon' => 'p-1.5 text-sm',
    ];

    $variants = [
        'primary' => 'border-brand bg-brand text-white hover:bg-brand-strong focus:ring-brand-medium',
        'success' => 'border-success bg-success text-white hover:bg-success-strong focus:ring-success-medium',
        'warning' => 'border-warning bg-warning text-white hover:bg-warning-strong focus:ring-warning-medium',
        'danger' => 'border-danger bg-danger text-white hover:bg-danger-strong focus:ring-danger-medium',
        'info' => 'border-info bg-info text-white hover:bg-info-strong focus:ring-info-medium',
        'dark' => 'border-dark bg-dark text-white hover:bg-dark-strong focus:ring-neutral-quaternary',

        'outline-primary' => 'border-brand text-brand hover:bg-brand hover:text-white focus:ring-brand-medium',
        'outline-success' => 'border-success text-success hover:bg-success hover:text-white focus:ring-success-medium',
        'outline-warning' => 'border-warning text-warning hover:bg-warning hover:text-white focus:ring-warning-medium',
        'outline-danger' => 'border-danger text-danger hover:bg-danger hover:text-white focus:ring-danger-medium',
        'outline-info' => 'border-info text-info hover:bg-info hover:text-white focus:ring-info-medium',
        'outline-dark' => 'border-dark text-dark hover:bg-dark hover:text-white focus:ring-neutral-quaternary',
        'outline-secondary' => 'border-default-strong text-body hover:bg-neutral-tertiary focus:ring-neutral-tertiary',
    ];

    $classes = trim("{$base} ".($sizes[$size] ?? $sizes['md']).' '.($variants[$variant] ?? $variants['primary']));
@endphp

@if ($href)
    <a href="{{ $href }}" {{ $attributes->merge(['class' => $classes]) }}>{{ $slot }}</a>
@else
    <button type="{{ $type }}" {{ $attributes->merge(['class' => $classes]) }}>{{ $slot }}</button>
@endif
