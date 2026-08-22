{{--
    サイドメニューを出さない内容ページ用のレイアウト（非公開ファイルの一覧・アップロードなど）。

    ログイン前レイアウト（guest-layout）との違いは、中央寄せの小さなカードではなく
    横幅いっぱいのコンテンツを置くこと。フッターは出す場合も画面最下部に付く。
--}}
@props([
    'title' => null,
    'robots' => 'noindex, nofollow, noarchive, noimageindex, nocache',
    'maxWidth' => 'max-w-6xl',
    'footer' => false,
])

<!DOCTYPE html>
<html lang="ja">
<head>
    <x-office.head :title="$title" :robots="$robots" />
    {{ $head ?? '' }}
</head>
<body class="flex min-h-dvh flex-col bg-page font-sans text-body antialiased">
    <main class="flex-1 px-4 py-6 sm:px-6">
        <div class="mx-auto {{ $maxWidth }}">
            <x-office.alert />

            <x-office.card>
                {{ $slot }}
            </x-office.card>
        </div>
    </main>

    @if ($footer)
        <x-office.footer />
    @endif

    {{ $scripts ?? '' }}
</body>
</html>
