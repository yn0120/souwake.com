@extends('office/parts/app')

@section('meta')
    <title>管理者詳細 | {{ config('app.name') }}</title>
@endsection

@push('css')

@endpush

@section('content')

    <div class="container-fluid flex-grow-1 container-p-y">
        <!-- Layout wrapper -->
        <div class="layout-wrapper layout-content-navbar">
            <div class="layout-container">
                <!-- Menu -->
                @include ('office/parts/side')
                <!-- / Menu -->
                <!-- Layout container -->
                <div class="layout-page">
                    <!-- Content wrapper -->
                    <div class="content-wrapper">
                        <!-- Content -->
                        <div class="container-fluid flex-grow-1 container-p-y">
                            {{-- エラー/サクセス メッセージ --}}
                            @include ('office/parts/item/alert')
                            <div class="card p-5">
                                <div class="col-12 pb-2 text-end">
                                    @if (in_array('officeAdminIndex', Auth::user()->routes()))
                                        <a href="{{ route('officeAdminIndex', session('officeAdminIndexSearchParams')) }}" class="btn btn-outline-dark">戻る</a>
                                    @endif
                                    @if (in_array('officeAdminEdit*', Auth::user()->routes()) && $assign['record']->activated_at)
                                        <a href="{{ route('officeAdminEditInput', ['id' => $assign['record']->id]) }}" class="btn btn-warning">編集</a>
                                    @endif
                                    @if (in_array('officeMemoIndex', Auth::user()->routes()))
                                        @php
                                            $memoUrl = route('officeMemoIndex', ['segment' => 'admins', 'target_id' => $assign['record']->id]);
                                        @endphp
                                        <button type="button" class="btn btn-info" onclick="window.open('{{ $memoUrl }}', 'memo', 'width=600,height='+ window.innerHeight +',scrollbars=yes,left=' + (window.screen.width) + ',top=0')">
                                            メモ
                                        </button>
                                    @endif
                                </div>
                                <div class="row">
                                    <div class="col-12 pt-2">
                                        <h5 class="card-title">管理者詳細</h5>
                                    </div>
                                </div>

                                <div class="row">
                                    <label class="col-md-3 col-form-label d-flex align-items-center pt-2 pb-0 py-md-2 fs-6 fw-bold">
                                        ID
                                    </label>
                                    <div class="col-md-8 form-text d-flex align-items-center pt-0 pb-2 py-md-2 fs-6">
                                        {{ number_format($assign['record']->id) }}
                                    </div>
                                </div>

                                <div class="row">
                                    <label class="col-md-3 col-form-label d-flex align-items-center pt-2 pb-0 py-md-2 fs-6 fw-bold">
                                        氏名
                                    </label>
                                    <div class="col-md-8 form-text d-flex align-items-center pt-0 pb-2 py-md-2 fs-6">
                                        {{ $assign['record']->name }}
                                    </div>
                                </div>

                                <div class="row">
                                    <label class="col-md-3 col-form-label d-flex align-items-center pt-2 pb-0 py-md-2 fs-6 fw-bold">
                                        権限
                                    </label>
                                    <div class="col-md-8 form-text d-flex align-items-center pt-0 pb-2 py-md-2 fs-6">
                                        {{ $assign['roles'][$assign['record']->role_id] ?? '' }}
                                    </div>
                                </div>

                                <div class="row">
                                    <label class="col-md-3 col-form-label d-flex align-items-center pt-2 pb-0 py-md-2 fs-6 fw-bold">
                                        メールアドレス
                                    </label>
                                    <div class="col-md-8 form-text d-flex align-items-center pt-0 pb-2 py-md-2 fs-6">
                                        {{ $assign['record']->email }}
                                    </div>
                                </div>

                                @if (! $assign['record']->activated_at)
                                    <div class="row">
                                        <label class="col-md-3 col-form-label d-flex align-items-center pt-2 pb-0 py-md-2 fs-6 fw-bold">
                                            登録状況
                                        </label>
                                        <div class="col-md-8 form-text d-flex align-items-center pt-0 pb-2 py-md-2 fs-6">
                                            管理者がアカウント発行を完了していません。<br>
                                            パスワード設定用メールを送っていますので、パスワードを設定するようお伝えください。
                                        </div>
                                    </div>
                                @endif

                                @if ($assign['record']->terminated_at)
                                    <div class="row">
                                        <label class="col-md-3 col-form-label d-flex align-items-center pt-2 pb-0 py-md-2 fs-6 fw-bold">
                                            退職日
                                        </label>
                                        <div class="col-md-8 form-text d-flex align-items-center pt-0 pb-2 py-md-2 fs-6">
                                            {{ date('Y年m月d日', strtotime($assign['record']->terminated_at)) }}
                                        </div>
                                    </div>
                                @endif
                            </div>
                        </div>
                        <!-- / Content -->
                    </div>
                    <!-- Content wrapper -->
                </div>
                <!-- / Layout page -->
            </div>
            <!-- Overlay -->
            <div class="layout-overlay layout-menu-toggle"></div>
            <!-- Drag Target Area To SlideIn Menu On Small Screens -->
            <div class="drag-target" style="touch-action: pan-y; user-select: none; -webkit-user-drag: none; -webkit-tap-highlight-color: rgba(0, 0, 0, 0);" ></div>
        </div>
        <!-- / Layout wrapper -->
        <!-- Page JS -->
        @push ('js')

        @endpush
    </div>

@endsection
