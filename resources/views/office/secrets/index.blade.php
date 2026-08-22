{{--
    非公開ファイルの一覧とビューア。

    ビューア（モーダル）内のPlyr（動画プレイヤー）だけはライブラリが自前でDOMを作るため、
    専用CSSを置く代わりに任意バリアント（[&_.plyr]: など）で当てている。
--}}
<x-office.plain-layout title="ファイル">
    <x-slot:head>
        <link rel="stylesheet" href="/assets/vendor/libs/plyr/plyr.css">
    </x-slot:head>

    <ul id="secrets-gallery-list"></ul>
    <div id="secrets-gallery-sentinel" class="py-4 text-center text-sm text-body" style="display:none;">読み込み中...</div>
    <div id="secrets-gallery-empty" class="py-4 text-center text-sm text-body" style="display:none;">ファイルはありません。</div>

    <x-slot:scripts>
        {{-- ビューア本体。カードの外（body直下）に置きたいため scripts スロットに入れている。 --}}
        <div id="secrets-modal" class="fixed inset-0 z-[20000] select-none bg-black" style="display:none;">
            <div id="secrets-modal-stage"
                 class="absolute inset-0 flex touch-none items-center justify-center overflow-hidden
                        [&_.plyr]:h-full [&_.plyr]:max-h-full [&_.plyr]:max-w-full
                        [&_.plyr_video]:object-contain [&_.plyr\_\_controls]:bottom-6"></div>
            <button type="button" id="secrets-modal-close" aria-label="閉じる"
                    class="absolute right-4 bottom-4 z-[20020] flex size-12 cursor-pointer items-center
                           justify-center rounded-full bg-white/15 text-3xl leading-none text-white hover:bg-white/30">
                &times;
            </button>
        </div>

        <script src="/assets/vendor/libs/plyr/plyr.js"></script>
        <script>
            window.secretsGalleryConfig = {
                initialRecords: @json($assign['records']),
                hasMore: @json($assign['hasMore']),
                listUrl: @json(route('officeSecretsList', [], false)),
                viewUrlBase: @json(route('officeSecretsView', ['id' => '__ID__'], false)),
            };
        </script>
        @vite('resources/js/office/secrets-gallery.js')
    </x-slot:scripts>
</x-office.plain-layout>
