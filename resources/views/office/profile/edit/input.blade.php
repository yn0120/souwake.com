@extends('office/parts/app')

@section('meta')
    <title>プロフィール編集 | {{ config('app.name') }}</title>
@endsection

@push('css')
    <style>
        #prf-alert { position: fixed; top: 1rem; right: 1rem; z-index: 1080; max-width: 360px; }
    </style>
@endpush

@section('content')

    <div class="container-fluid flex-grow-1 container-p-y">
        <div class="layout-wrapper layout-content-navbar">
            <div class="layout-container">
                @include ('office/parts/side')
                <div class="layout-page">
                    <div class="content-wrapper">
                        <div class="container-fluid flex-grow-1 container-p-y">
                            @include ('office/parts/item/alert')

                            <div id="prf-alert" style="display:none;">
                                <p id="prf-alert-message" class="alert p-2 text-break shadow-sm" role="alert"></p>
                            </div>

                            <div class="card p-5">
                                <h5 class="card-title">プロフィール編集</h5>
                                <form id="prf-form" class="mt-3" enctype="multipart/form-data">
                                    <h6 class="mt-2">基本情報</h6>
                                    <div class="row">
                                        <div class="col-12 col-md-6 pt-2">
                                            <label class="form-label" for="prf-name">氏名</label>
                                            <input type="text" id="prf-name" name="name" class="form-control" value="{{ $assign['name'] }}" required>
                                        </div>
                                        <div class="col-12 col-md-6 pt-2">
                                            <label class="form-label" for="prf-email">メールアドレス</label>
                                            <input type="email" id="prf-email" name="email" class="form-control" value="{{ $assign['email'] }}" required>
                                        </div>
                                    </div>

                                    <h6 class="mt-4">パスワード変更（変更する場合のみ入力）</h6>
                                    <div class="row">
                                        <div class="col-12 col-md-6 pt-2">
                                            <label class="form-label" for="prf-new-password">新しいパスワード</label>
                                            <input type="password" id="prf-new-password" name="new_password" class="form-control" autocomplete="new-password">
                                        </div>
                                    </div>

                                    <h6 class="mt-4">Googleサービスアカウント（家計簿のスプレッドシート書き込み用）</h6>
                                    <div class="mb-2">
                                        現在の設定：
                                        <span id="prf-service-account-status" class="fw-semibold">{{ $assign['serviceAccountEmail'] ?: '未設定' }}</span>
                                    </div>
                                    <details class="mb-3">
                                        <summary class="text-primary" style="cursor:pointer;">JSON鍵ファイルの取得方法</summary>
                                        <div class="text-muted small mt-2">
                                            <ol class="ps-3 mb-2">
                                                <li><a href="https://console.cloud.google.com/" target="_blank" rel="noopener">Google Cloud Console</a>でプロジェクトを用意する</li>
                                                <li>「APIとサービス」→「ライブラリ」から「Google Sheets API」を有効化する</li>
                                                <li>「APIとサービス」→「認証情報」→「サービスアカウント」を作成する（ロール付与は不要）</li>
                                                <li>作成したサービスアカウントの「キー」タブから、JSON形式で新しい鍵を作成・ダウンロードする</li>
                                                <li>ダウンロードしたJSONファイルを下のフォームからアップロードする</li>
                                                <li>家計簿で使うスプレッドシートを、JSON内の <code>client_email</code>（アップロード後は上記に表示されます）に<strong>編集者権限</strong>で共有する</li>
                                            </ol>
                                        </div>
                                    </details>
                                    <div class="row">
                                        <div class="col-12 col-md-6 pt-2">
                                            <label class="form-label" for="prf-service-account-json">JSON鍵ファイル</label>
                                            <input type="file" id="prf-service-account-json" name="service_account_json" class="form-control" accept=".json,application/json">
                                        </div>
                                    </div>

                                    <div class="row mt-4">
                                        <div class="col-12 text-end">
                                            <button type="submit" class="btn btn-success">保存する</button>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="layout-overlay layout-menu-toggle"></div>
            </div>
        </div>
    </div>

@endsection

@push ('js')
    <script>
        window.profileConfig = {
            updateUrl: @json(route('officeProfileEditExecute', [], false)),
            csrfToken: @json(csrf_token()),
        };
    </script>
    <script src="/assets/js/profile.js"></script>
@endpush
