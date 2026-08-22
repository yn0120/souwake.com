{{--
    <head> の中身。管理画面のレイアウト（layout / guest-layout）が共通で読み込む。

    SEO/AIO用のメタは埋める口を用意してあるが、管理画面は既定で robots="noindex, nofollow"。
    公開ページを作る時は :robots="'index, follow'" と :description、:canonical を渡す。
--}}
@props([
    'title' => null,
    'description' => null,
    'robots' => 'noindex, nofollow, noarchive, noimageindex, nocache',
    'canonical' => null,
    'ogImage' => null,
])

<meta charset="UTF-8">
{{-- user-scalable=no は付けない（拡大できないとアクセシビリティを損ねるため） --}}
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="robots" content="{{ $robots }}">
<meta name="csrf-token" content="{{ csrf_token() }}">

<title>{{ $title ? "{$title} | ".config('app.name') : config('app.name') }}</title>
@if ($description)
    <meta name="description" content="{{ $description }}">
@endif
@if ($canonical)
    <link rel="canonical" href="{{ $canonical }}">
@endif

{{-- OGP。SNS・AIクローラーがページの要約に使う。公開ページで値を渡した時だけ出す。 --}}
@if ($description || $ogImage)
    <meta property="og:type" content="website">
    <meta property="og:site_name" content="{{ config('app.name') }}">
    <meta property="og:title" content="{{ $title ?? config('app.name') }}">
    <meta property="og:url" content="{{ $canonical ?? url()->current() }}">
    <meta property="og:locale" content="ja_JP">
    @if ($description)
        <meta property="og:description" content="{{ $description }}">
    @endif
    @if ($ogImage)
        <meta property="og:image" content="{{ $ogImage }}">
        <meta name="twitter:card" content="summary_large_image">
    @endif
@endif

<link rel="icon" type="image/x-icon" href="/assets/img/favicon/favicon.ico">

@vite(['resources/css/office.css', 'resources/js/office.js'])
