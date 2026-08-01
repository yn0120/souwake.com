<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title>@yield('title', 'Wedding Invitation')</title>
    <meta name="description" content="@yield('description', '結婚式のご招待とご出欠フォームのご案内です。')">
    @vite(['resources/css/wedding.css', 'resources/js/wedding.js'])
</head>
<body class="wedding-body font-sans-jp antialiased">
    @yield('content')
</body>
</html>
