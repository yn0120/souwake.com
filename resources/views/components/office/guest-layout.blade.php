{{--
    ログイン前のレイアウト（サイドメニューなし・カードを中央に置く）。

    フッターを出す場合も、mainがflex-1なのでフッターは画面最下部に付く（中央には来ない）。
--}}
@props([
    'title' => null,
    'robots' => 'noindex, nofollow, noarchive, noimageindex, nocache',
    'footer' => false,
])

<!DOCTYPE html>
<html lang="ja">
<head>
    <x-office.head :title="$title" :robots="$robots" />
    {{ $head ?? '' }}
</head>
<body class="flex min-h-dvh flex-col bg-page font-sans text-body antialiased">
    <main class="flex flex-1 items-center justify-center px-4 py-10">
        <div class="w-full max-w-md">
            <x-office.alert />

            <div class="rounded-xl bg-white p-6 shadow-sm sm:p-8">
                {{-- ブランド --}}
                <div class="mb-6 flex items-center justify-center gap-2 text-heading">
                    <img src="{{ asset('assets/img/kokopelli.jpg') }}" alt="{{ config('app.name') }}"
                         class="size-9 shrink-0 rounded-lg bg-white object-cover">
                    <span class="text-xl font-bold">{{ config('app.name') }}</span>
                </div>

                {{ $slot }}
            </div>
        </div>
    </main>

    @if ($footer)
        <x-office.footer />
    @endif

    {{ $scripts ?? '' }}
</body>
</html>
