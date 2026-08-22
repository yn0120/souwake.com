<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Wedding Invitation')</title>
    <meta name="description" content="@yield('description', '結婚式のご招待とご出欠フォームのご案内です。')">

    {{-- LINE等で招待状のURLを共有した時に表示されるプレビュー（OGP）。
         これが無いとサムネイルもタイトルも出ず、URLの文字列だけが表示される。

         画像は public/assets/img/wedding/ogp.jpg に配置する（推奨1200×630の横長。
         プレビューは横長に切り抜かれるため、ヒーロー画像の縦長写真をそのまま使うと
         顔が切れることがある。専用に用意するのが望ましい）。
         parts/photo.blade.php と同じく、未配置の間は画像のタグ自体を出さない
         （存在しないURLをog:imageに指定すると、クローラーによってはプレビュー全体を
         表示しなくなるため）。

         og:imageは絶対URLでなければクローラーが取得できないためasset()を使う。
         nginx側は静的アセットにBot判定を掛けていないので、プレビュー用クローラーからも
         取得できる（_docker/web/prod.conf 参照）。 --}}
    @php
        $weddingOgpPath = 'assets/img/wedding/ogp.jpg';
    @endphp
    <meta property="og:type" content="website">
    <meta property="og:site_name" content="仲道 祐貴 &amp; 加藤 奈緒 Wedding">
    <meta property="og:title" content="@yield('title', 'Wedding Invitation')">
    <meta property="og:description" content="@yield('description', '結婚式のご招待とご出欠フォームのご案内です。')">
    <meta property="og:url" content="{{ url()->current() }}">
    <meta property="og:locale" content="ja_JP">
    @if (file_exists(public_path($weddingOgpPath)))
        <meta property="og:image" content="{{ asset($weddingOgpPath) }}">
        <meta property="og:image:width" content="1200">
        <meta property="og:image:height" content="630">
        <meta property="og:image:alt" content="新郎新婦の写真">
        <meta name="twitter:card" content="summary_large_image">
    @else
        <meta name="twitter:card" content="summary">
    @endif

    @vite(['resources/css/wedding.css', 'resources/js/wedding.js'])

    {{-- 到着日・出発日のカレンダー（bootstrap-datepicker）。jQueryが前提のため先に読み込む。
         Tailwindのリセットに負けないよう、CSSは@viteの後ろに置く。 --}}
    <link rel="stylesheet" href="/assets/vendor/libs/bootstrap-datepicker/bootstrap-datepicker.css">
    <script src="/assets/vendor/libs/jquery/jquery.js"></script>
    <script src="/assets/vendor/libs/bootstrap-datepicker/bootstrap-datepicker.min.js"></script>
    <script src="/assets/vendor/libs/bootstrap-datepicker/bootstrap-datepicker.ja.min.js"></script>
</head>
<body class="wedding-body font-sans-jp antialiased">
    @yield('content')
</body>
</html>
