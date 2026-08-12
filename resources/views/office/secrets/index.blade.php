@extends('office/parts/app')

@push('css')
    <link rel="stylesheet" href="/assets/vendor/libs/plyr/plyr.css">
    <link rel="stylesheet" href="/assets/css/secrets-gallery.css">
@endpush

@section('content')

    <div class="container-xxl container-p-y">
        @include ('office/parts/item/alert')

        <div class="card p-5">
            <ul id="secrets-gallery-list" class="secrets-gallery-list"></ul>
            <div id="secrets-gallery-sentinel" class="text-center text-muted py-4" style="display:none;">読み込み中...</div>
            <div id="secrets-gallery-empty" class="text-center text-muted py-4" style="display:none;">ファイルはありません。</div>
        </div>
    </div>

    <div id="secrets-modal" class="secrets-modal" style="display:none;">
        <div class="secrets-modal-stage" id="secrets-modal-stage"></div>
        <button type="button" id="secrets-modal-close" class="secrets-modal-close" aria-label="閉じる">&times;</button>
    </div>

@endsection

@push ('js')
    <script src="/assets/vendor/libs/plyr/plyr.js"></script>
    <script>
        window.secretsGalleryConfig = {
            initialRecords: @json($assign['records']),
            hasMore: @json($assign['hasMore']),
            listUrl: @json(route('officeSecretsList', [], false)),
            viewUrlBase: @json(route('officeSecretsView', ['id' => '__ID__'], false)),
        };
    </script>
    <script src="/assets/js/secrets-gallery.js"></script>
@endpush
