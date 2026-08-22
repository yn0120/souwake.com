{{--
    ログイン後のレイアウト（サイドメニューあり）。

      <x-office.layout title="権限一覧">
          ...ページの中身...
      </x-office.layout>

    プロパティ
      headerBehavior : 'fixed'（既定・常に固定） / 'auto-hide'（下スクロールで隠す）
      footer         : true でフッターを出す（既定はfalse＝現状どおり出さない）
--}}
@props([
    'title' => null,
    'description' => null,
    'robots' => 'noindex, nofollow, noarchive, noimageindex, nocache',
    'headerBehavior' => 'fixed',
    'footer' => false,
])

<!DOCTYPE html>
<html lang="ja">
<head>
    <x-office.head :title="$title" :description="$description" :robots="$robots" />
    {{ $head ?? '' }}
</head>
{{--
    group/layout + data-sidebar で、サイドメニューの開閉状態を子要素へ伝える
    （子は group-data-[sidebar=collapsed]/layout: を付けるだけでよく、JSからクラスを触らない）。
--}}
<body data-sidebar="expanded"
      data-header-behavior="{{ $headerBehavior }}"
      class="group/layout min-h-dvh bg-page font-sans text-body antialiased">
    <x-office.sidebar />

    <div class="flex min-h-dvh flex-col transition-[padding] duration-200
                xl:pl-64 group-data-[sidebar=collapsed]/layout:xl:pl-16">
        <x-office.header />

        <main class="flex-1 px-4 py-6 sm:px-6">
            {{-- パンくず。今は使っていないが、置き場所はここ（例は breadcrumb.blade.php を参照）。 --}}
            {{ $breadcrumb ?? '' }}

            <x-office.alert />

            {{ $slot }}
        </main>

        @if ($footer)
            <x-office.footer />
        @endif
    </div>

    {{ $scripts ?? '' }}
</body>
</html>
