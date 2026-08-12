<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Wedding Invitation')</title>
    <meta name="description" content="@yield('description', '結婚式のご招待とご出欠フォームのご案内です。')">
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
