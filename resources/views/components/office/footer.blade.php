{{--
    フッター。

    レイアウト側が「min-h-dvh + flex-col、main に flex-1」になっているため、
    コンテンツが少ないページでも画面の最下部に来る（画面中央には来ない）。
    既定では出さない（現状の画面にフッターが無いため）。出したいページ・全体で出したい場合は
        <x-office.layout :footer="true">
    とする。中身を差し替えたい時はこのファイルを編集する。
--}}
<footer class="mt-auto shrink-0 border-t border-default bg-white px-4 py-4 text-xs text-body sm:px-6">
    <div class="flex flex-col gap-1 sm:flex-row sm:items-center sm:justify-between">
        <span>&copy; {{ date('Y') }} {{ config('app.name') }}</span>
        {{ $slot }}
    </div>
</footer>
