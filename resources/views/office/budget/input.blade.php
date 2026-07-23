@extends('office/parts/app')

@section('meta')
    <title>家計簿 | {{ config('app.name') }}</title>
@endsection

@push('css')
    <style>
        #bdg-alert { position: fixed; top: 1rem; right: 1rem; z-index: 1080; max-width: 360px; }
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

                            <div id="bdg-alert" style="display:none;">
                                <p id="bdg-alert-message" class="alert p-2 text-break shadow-sm" role="alert"></p>
                            </div>

                            {{-- スプレッドシートURL設定 --}}
                            <div class="card p-4 mb-4 {{ $assign['spreadsheetUrl'] ? 'd-none' : '' }}">
                                <h6 class="card-title">保存先スプレッドシート</h6>
                                <form id="bdg-spreadsheet-form">
                                    <div class="row">
                                        <div class="col-12 col-md-8 pt-2">
                                            <label class="form-label" for="bdg-spreadsheet-url">スプレッドシートURL</label>
                                            <input type="url" id="bdg-spreadsheet-url" class="form-control" placeholder="https://docs.google.com/spreadsheets/d/..." value="{{ $assign['spreadsheetUrl'] }}">
                                        </div>
                                        <div class="col-12 col-md-4 pt-2 d-flex align-items-end">
                                            <button type="submit" class="btn btn-outline-primary">URLを保存</button>
                                        </div>
                                    </div>
                                    <div class="text-muted small mt-2">※ このスプレッドシートを、サービスアカウントのメールアドレスに編集者権限で共有しておく必要があります。</div>
                                </form>
                            </div>

                            {{-- 入力フォーム --}}
                            <div class="card p-5">
                                <h5 class="card-title">家計簿を入力</h5>
                                <form id="bdg-entry-form" class="mt-3">
                                    <div class="row">
                                        <div class="col-12 col-md-3 pt-2">
                                            <label class="form-label" for="bdg-occurred-on">発生日</label>
                                            <input type="tel" name="occurred_on" id="bdg-occurred-on" class="form-control" maxlength="8" inputmode="numeric" required>
                                        </div>
                                        <div class="col-12 col-md-3 pt-2">
                                            <label class="form-label" for="bdg-amount">金額</label>
                                            <input type="tel" id="bdg-amount" name="amount" class="form-control" inputmode="numeric" placeholder="9999" required autocomplete="off">
                                        </div>
                                        <div class="col-12 col-md-3 pt-2">
                                            <label class="form-label" for="bdg-account">口座</label>
                                            <select id="bdg-account" name="account_id" class="form-select" required>
                                                @foreach ($assign['accounts'] as $account)
                                                    <option value="{{ $account['id'] }}" {{ $account['id'] === $assign['defaultAccountId'] ? 'selected' : '' }}>{{ $account['name'] }}</option>
                                                @endforeach
                                                <option value="__add__">＋ 追加</option>
                                            </select>
                                        </div>
                                        <div class="col-12 col-md-3 pt-2">
                                            <label class="form-label" for="bdg-category">科目</label>
                                            <select id="bdg-category" name="category_id" class="form-select" required>
                                                @foreach ($assign['categories'] as $category)
                                                    <option value="{{ $category['id'] }}" {{ $category['id'] === $assign['defaultCategoryId'] ? 'selected' : '' }}>{{ $category['name'] }}</option>
                                                @endforeach
                                                <option value="__add__">＋ 追加</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="row mt-3">
                                        <div class="col-12">
                                            <label class="form-label" for="bdg-memo">備考</label>
                                            <input type="text" id="bdg-memo" name="memo" class="form-control">
                                        </div>
                                    </div>
                                    <div class="row mt-4">
                                        <div class="col-12 text-end">
                                            <button type="submit" class="btn btn-success">登録する</button>
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
        window.budgetConfig = {
            submitUrl: @json(route('officeBudgetCreateExecute', [], false)),
            accountCreateUrl: @json(route('officeBudgetAccountCreateExecute', [], false)),
            categoryCreateUrl: @json(route('officeBudgetCategoryCreateExecute', [], false)),
            spreadsheetUpdateUrl: @json(route('officeBudgetSpreadsheetEditExecute', [], false)),
            defaultAccountId: @json($assign['defaultAccountId']),
            defaultCategoryId: @json($assign['defaultCategoryId']),
            today: @json($assign['today']),
            csrfToken: @json(csrf_token()),
        };
    </script>
    <script src="/assets/js/budget.js"></script>
@endpush
