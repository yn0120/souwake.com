@extends('office/parts/app')

@section('meta')
    <title>パスワード管理 | {{ config('app.name') }}</title>
@endsection

@push('css')
    <style>
        .pwm-entry-card { border: 1px solid var(--bs-border-color); border-radius: .5rem; }
        .pwm-item-row { border-top: 1px dashed var(--bs-border-color); }
        .pwm-item-row:first-child { border-top: none; }
        #pwm-alert { position: fixed; top: 1rem; right: 1rem; z-index: 1080; max-width: 360px; }
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

                            <div id="pwm-alert" style="display:none;">
                                <p id="pwm-alert-message" class="alert p-2 text-break shadow-sm" role="alert"></p>
                            </div>

                            <div class="card p-5">
                                <div class="row">
                                    <div class="col-6 pt-2">
                                        <h5 class="card-title">パスワード管理</h5>
                                    </div>
                                    <div class="col-6 pt-2 text-end">
                                        <button type="button" id="pwm-create-toggle" class="btn btn-primary">登録</button>
                                    </div>
                                </div>

                                {{-- 新規登録パネル --}}
                                <div id="pwm-create-panel" class="card p-3 mt-3" style="display:none;">
                                    <h6>サイトを登録</h6>
                                    <form id="pwm-create-form">
                                        <div class="row">
                                            <div class="col-12 col-md-6 pt-2">
                                                <label class="form-label" for="pwm-create-name">サイト名</label>
                                                <input type="text" id="pwm-create-name" name="name" class="form-control" required>
                                            </div>
                                        </div>
                                        <div class="row mt-3">
                                            <div class="col-12">
                                                <label class="form-label">項目</label>
                                                <div id="pwm-create-items"></div>
                                                <button type="button" id="pwm-create-item-add" class="btn btn-sm btn-outline-secondary mt-2">＋ 項目を追加</button>
                                            </div>
                                        </div>
                                        <div class="row mt-3">
                                            <div class="col-12 text-end">
                                                <button type="button" id="pwm-create-cancel" class="btn btn-outline-dark me-2">キャンセル</button>
                                                <button type="submit" class="btn btn-success">登録する</button>
                                            </div>
                                        </div>
                                    </form>
                                </div>

                                {{-- 検索条件 --}}
                                <div class="row mt-4">
                                    <div class="col-12">
                                        <form id="pwm-search-form" class="card p-3">
                                            <div class="row">
                                                <div class="col-6 col-md-3 pt-2">
                                                    <label class="form-label" for="pwm-search-name">サイト名</label>
                                                    <input type="text" id="pwm-search-name" name="name" class="form-control">
                                                </div>
                                                <div class="col-6 col-md-3 pt-2">
                                                    <label class="form-label" for="pwm-search-keyword">キーワード（項目名・値）</label>
                                                    <input type="text" id="pwm-search-keyword" name="keyword" class="form-control">
                                                </div>
                                                <div class="col-6 col-md-3 pt-2">
                                                    <label class="form-label" for="pwm-search-sort">並び替え</label>
                                                    <select id="pwm-search-sort" name="sort" class="form-select">
                                                        <option value="display_order">表示順</option>
                                                        <option value="name">サイト名</option>
                                                        <option value="created_at">登録日時</option>
                                                    </select>
                                                </div>
                                                <div class="col-6 col-md-3 pt-2">
                                                    <label class="form-label" for="pwm-search-direction">昇順・降順</label>
                                                    <select id="pwm-search-direction" name="direction" class="form-select">
                                                        <option value="asc">昇順</option>
                                                        <option value="desc">降順</option>
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="row mt-3">
                                                <div class="col-12 text-end">
                                                    <button type="button" id="pwm-search-clear" class="btn btn-outline-dark me-2">クリア</button>
                                                    <button type="submit" class="btn btn-success">検索する</button>
                                                </div>
                                            </div>
                                        </form>
                                        <div class="text-muted small mt-2">※ パスワード型の項目は暗号化して保存しているため、検索・並び替えの対象外です。</div>
                                    </div>
                                </div>

                                <div class="row mt-3">
                                    <div class="col-12">
                                        <div id="pwm-list"></div>
                                        <div id="pwm-loading" class="text-center text-muted py-4">読み込み中...</div>
                                        <div id="pwm-empty" class="text-center text-muted py-4" style="display:none;">登録されているサイトがありません。</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="layout-overlay layout-menu-toggle"></div>
                <div class="drag-target" style="touch-action: pan-y; user-select: none; -webkit-user-drag: none; -webkit-tap-highlight-color: rgba(0, 0, 0, 0);"></div>
            </div>
        </div>
    </div>

@endsection

@push ('js')
    <script src="/assets/vendor/libs/clipboard/clipboard.js"></script>
    <script>
        @php
            $pwmListUrl = route('officePasswordManagerIndexList', [], false);
            $pwmCreateUrl = route('officePasswordManagerCreateExecute', [], false);
            $pwmUpdateUrlBase = route('officePasswordManagerEditExecute', ['id' => '__ID__'], false);
            $pwmDeleteUrlBase = route('officePasswordManagerDeleteExecute', ['id' => '__ID__'], false);
            $pwmItemCreateUrlBase = route('officePasswordManagerItemCreateExecute', ['id' => '__ID__'], false);
            $pwmItemUpdateUrlBase = route('officePasswordManagerItemEditExecute', ['id' => '__ID__', 'itemId' => '__ITEM_ID__'], false);
            $pwmItemDeleteUrlBase = route('officePasswordManagerItemDeleteExecute', ['id' => '__ID__', 'itemId' => '__ITEM_ID__'], false);
        @endphp
        window.passwordManagerConfig = {
            listUrl: @json($pwmListUrl),
            createUrl: @json($pwmCreateUrl),
            updateUrlBase: @json($pwmUpdateUrlBase),
            deleteUrlBase: @json($pwmDeleteUrlBase),
            itemCreateUrlBase: @json($pwmItemCreateUrlBase),
            itemUpdateUrlBase: @json($pwmItemUpdateUrlBase),
            itemDeleteUrlBase: @json($pwmItemDeleteUrlBase),
            itemTypes: @json($assign['itemTypes']),
            csrfToken: @json(csrf_token()),
        };
    </script>
    <script src="/assets/js/password-manager.js"></script>
@endpush
