@extends('office/parts/app')

@section('meta')
    <title>管理者登録確認 | {{ config('app.name') }}</title>
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
                                <div class="row">
                                    <div class="col-12 pt-2">
                                        <h5 class="card-title">管理者登録確認</h5>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-12">
                                        <form method="POST" action="{{ route('officeAdminCreateExecute', [], false) }}" class="form" enctype="multipart/form-data">
                                            @csrf
                                            <div class="row">
                                                <label class="col-md-3 col-form-label d-flex align-items-center pt-2 pb-0 py-md-2 fs-6 fw-bold">
                                                    氏名
                                                </label>
                                                <div class="col-md-8 form-text d-flex align-items-center pt-0 pb-2 py-md-2 fs-6">
                                                    {{ $assign['confirm']['name'] }}
                                                </div>
                                            </div>

                                            <div class="row">
                                                <label class="col-md-3 col-form-label d-flex align-items-center pt-2 pb-0 py-md-2 fs-6 fw-bold">
                                                    権限
                                                </label>
                                                <div class="col-md-8 form-text d-flex align-items-center pt-0 pb-2 py-md-2 fs-6">
                                                    {{ $assign['confirm']['role_id'] }}
                                                </div>
                                            </div>

                                            <div class="row">
                                                <label class="col-md-3 col-form-label d-flex align-items-center pt-2 pb-0 py-md-2 fs-6 fw-bold">
                                                    メールアドレス
                                                </label>
                                                <div class="col-md-8 form-text d-flex align-items-center pt-0 pb-2 py-md-2 fs-6">
                                                    {{ $assign['confirm']['email'] }}
                                                </div>
                                            </div>

                                            {{-- 進むボタン --}}
                                            <div class="my-3">
                                                <button type="submit" class="btn btn-success d-grid w-100 text-white text-break" id="submit">登録する</button>
                                            </div>

                                            {{-- 戻るボタン --}}
                                            <div class="my-3">
                                                <button type="submit" name="back" value="1" class="text-break btn btn-outline-dark col-12 mb-0">前のページに戻る</button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
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
