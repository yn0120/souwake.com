@extends('office/parts/app')

@push('css')
    <link rel="stylesheet" href="/assets/vendor/libs/plyr/plyr.css">
    <link rel="stylesheet" href="/assets/css/secrets-gallery.css">
@endpush

@section('content')

    <div class="container-xxl container-p-y">
        @include ('office/parts/item/alert')

        {{--
            アンロックゲート。
            ファイルはE2E暗号化されており、サーバーもCloudflareも復号鍵を持たない。
            認証器（WebAuthn PRF）かリカバリコードでvault秘密鍵を取り出すまで、
            ブラウザは暗号文を持っていても中身を一切表示できない。
        --}}
        <div class="card p-5" id="secrets-gate">
            <h5 class="mb-3">ファイルのロック解除</h5>
            <p class="text-muted small mb-4">
                ファイルは端末側でのみ復号されます。サーバーもCloudflareも復号鍵を持たないため、
                認証器での本人確認が必要です。
            </p>

            <p id="secrets-gate-status" class="text-muted">準備しています...</p>

            <div class="d-flex flex-wrap gap-2 mb-4">
                <button type="button" id="secrets-unlock-btn" class="btn btn-primary" style="display:none;">アンロック</button>
                <button type="button" id="secrets-setup-btn" class="btn btn-primary" style="display:none;">vaultを新規作成</button>
                <button type="button" id="secrets-add-key-btn" class="btn btn-outline-secondary" style="display:none;">認証器を追加登録</button>
            </div>

            <div style="display:none;" class="alert alert-warning">
                <strong>リカバリコード（この一度しか表示されません）</strong>
                <p class="small mb-2">認証器をすべて紛失した場合の唯一の復旧手段です。必ず紙に控えて安全な場所に保管してください。</p>
                <code id="secrets-recovery-output" class="d-block p-2" style="word-break:break-all;"></code>
            </div>

            <details class="mt-3">
                <summary class="text-muted small">認証器を紛失した場合（リカバリコードでアンロック）</summary>
                <div class="mt-3">
                    <input type="text" id="secrets-recovery-input" class="form-control mb-2" autocomplete="off"
                           placeholder="XXXX-XXXX-XXXX-...">
                    <button type="button" id="secrets-recovery-btn" class="btn btn-outline-danger btn-sm">
                        リカバリコードでアンロック
                    </button>
                </div>
            </details>
        </div>

        <div id="secrets-gallery-wrap" style="display:none;">
            <div class="card p-5">
                <ul id="secrets-gallery-list" class="secrets-gallery-list"></ul>
                <div id="secrets-gallery-sentinel" class="text-center text-muted py-4" style="display:none;">読み込み中...</div>
                <div id="secrets-gallery-empty" class="text-center text-muted py-4" style="display:none;">ファイルはありません。</div>
            </div>
        </div>
    </div>

    <div id="secrets-modal" class="secrets-modal" style="display:none;">
        <div class="secrets-modal-stage" id="secrets-modal-stage"></div>
        <button type="button" id="secrets-modal-close" class="secrets-modal-close" aria-label="閉じる">&times;</button>
    </div>

@endsection

@push ('js')
    <script src="/assets/vendor/libs/plyr/plyr.js"></script>
    <script nonce="{{ $cspNonce }}">
        window.secretsGalleryConfig = {
            initialRecords: @json($assign['records']),
            hasMore: @json($assign['hasMore']),
            listUrl: @json(route('officeSecretsList', [], false)),
            // このパスはサーバーには存在しない。Service Worker（secrets-sw.js）が横取りして
            // /secrets/meta/{id} と /secrets/raw/{id} から暗号文を取得し、ブラウザ内で復号する。
            viewUrlBase: '/secrets/media/__ID__',
        };
        window.secretsVaultState = {
            registered: @json($assign['vaultRegistered']),
        };
    </script>
    <script src="/assets/js/secrets-gallery.js"></script>
    {{-- secrets-vault.js を import するため type="module" が必須 --}}
    <script type="module" src="/assets/js/secrets-boot.js"></script>
@endpush
