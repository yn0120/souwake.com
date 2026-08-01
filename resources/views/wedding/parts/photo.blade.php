{{--
    写真の差し込み用パーツ。
    public/assets/img/wedding/ 配下に $path で指定したファイル名の画像を置くと自動で表示されます。
    未配置の間は、差し込み位置とファイル名がひと目でわかるプレースホルダーを表示します。

    使い方:
    @include('wedding/parts/photo', [
        'path' => 'assets/img/wedding/hero.jpg',
        'alt' => '新郎新婦のメイン写真',
        'label' => 'メイン写真',
        'class' => 'aspect-[4/5]',
    ])
--}}
@php
    $weddingPhotoExists = file_exists(public_path($path));
@endphp
<div class="{{ $class ?? 'aspect-[4/3]' }} {{ $wrapperClass ?? '' }} relative overflow-hidden rounded-[1.75rem] bg-sand-200">
    @if ($weddingPhotoExists)
        <img src="{{ asset($path) }}" alt="{{ $alt ?? '' }}" class="h-full w-full object-cover" loading="lazy">
    @else
        <div class="flex h-full w-full flex-col items-center justify-center gap-2 border-2 border-dashed border-clay-400/50 bg-sand-100/60 p-4 text-center">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.4" class="h-7 w-7 text-moss-500">
                <path d="M4 16.5 8.5 12a2 2 0 0 1 2.8 0l4.7 4.7" stroke-linecap="round" stroke-linejoin="round"/>
                <path d="M13.5 13.5 15 12a2 2 0 0 1 2.8 0L20 14.2" stroke-linecap="round" stroke-linejoin="round"/>
                <rect x="3" y="4.5" width="18" height="15" rx="2.5"/>
                <circle cx="8" cy="9" r="1.4"/>
            </svg>
            <p class="font-serif-jp text-sm text-ink-700">{{ $label ?? '写真' }}</p>
            <p class="text-[11px] leading-relaxed text-ink-700/50">public/{{ $path }}</p>
        </div>
    @endif
</div>
