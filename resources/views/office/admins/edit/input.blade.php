@extends('office/parts/app')

@section('meta')
    <title>管理者編集 | {{ config('app.name') }}</title>
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
                                        <h5 class="card-title">管理者編集</h5>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-12">
                                        <form method="POST" action="{{ route('officeAdminEditConfirm', ['id' => $assign['record']->id], false) }}" class="form" enctype="multipart/form-data">
                                            @csrf
                                            <div class="row">
                                                <label class="col-md-3 col-form-label d-flex align-items-center pt-2 pb-0 py-md-2 fs-6 fw-bold" for="name" role="button">
                                                    <span class="text-danger">※&nbsp;</span> 氏名
                                                </label>
                                                <div class="col-md-8 form-text d-flex align-items-center pt-0 pb-2 py-md-2 fs-6">
                                                    <input type="text" name="name" value="{{ old('name', $assign['record']->name) }}" id="name" class="form-control">
                                                </div>
                                                @error ('name')
                                                    <div class="col-md-3"></div>
                                                    <div class="col-md-8">
                                                        <div class="alert alert-danger mt-0 p-1 form-text" role="alert">{{ $message }}</div>
                                                    </div>
                                                @enderror
                                            </div>

                                            <div class="row">
                                                <label class="col-md-3 col-form-label d-flex align-items-center pt-2 pb-0 py-md-2 fs-6 fw-bold" for="role_id" role="button">
                                                    <span class="text-danger">※&nbsp;</span> 権限
                                                </label>
                                                <div class="col-md-8 form-text d-flex align-items-center pt-0 pb-2 py-md-2 fs-6">
                                                    <select name="role_id" id="role_id" class="form-control">
                                                        <option value="">未選択</option>
                                                        @foreach ($assign['roles'] as $key => $role)
                                                            <option value="{{ $key }}" @selected($key == old('role_id', $assign['record']->role_id))>{{ $role }}</option>
                                                        @endforeach
                                                    </select>
                                                </div>
                                                @error('role_id')
                                                    <div class="col-md-3"></div>
                                                    <div class="col-md-8">
                                                        <div class="alert alert-danger mt-0 p-1 form-text" role="alert">{{ $message }}</div>
                                                    </div>
                                                @enderror
                                            </div>

                                            <div class="row">
                                                <label class="col-md-3 col-form-label d-flex align-items-center pt-2 pb-0 py-md-2 fs-6 fw-bold" for="email" role="button">
                                                    <span class="text-danger">※&nbsp;</span> メールアドレス
                                                </label>
                                                <div class="col-md-8 form-text d-flex align-items-center pt-0 pb-2 py-md-2 fs-6">
                                                    <input type="text" name="email" value="{{ old('email', $assign['record']->email) }}" id="email" class="form-control">
                                                </div>
                                                @error ('email')
                                                    <div class="col-md-3"></div>
                                                    <div class="col-md-8">
                                                        <div class="alert alert-danger mt-0 p-1 form-text" role="alert">{{ $message }}</div>
                                                    </div>
                                                @enderror
                                            </div>

                                            <div class="row">
                                                <label class="col-md-3 col-form-label d-flex align-items-center pt-2 pb-0 py-md-2 fs-6 fw-bold" for="password" role="button">
                                                    パスワード
                                                </label>
                                                <div class="col-md-8 form-text d-flex align-items-center pt-0 pb-2 py-md-2 fs-6">
                                                    <input type="password" name="password" value="" id="password" class="form-control" autocapitalize="off" autocomplete="new-password">
                                                </div>
                                                @error ('password')
                                                    <div class="col-md-3"></div>
                                                    <div class="col-md-8">
                                                        <div class="alert alert-danger mt-0 p-1 form-text" role="alert">{{ $message }}</div>
                                                    </div>
                                                @enderror
                                                <div class="col-md-3"></div>
                                                <div class="col-md-8">
                                                    <p class="fs-small text-break">未入力の場合は更新しません。</p>
                                                </div>
                                            </div>

                                            @if ($assign['record']->login_locked_at)
                                                <div class="row">
                                                    <label class="col-md-3 col-form-label d-flex align-items-center pt-2 pb-0 py-md-2 fs-6 fw-bold" for="login_locked_at">
                                                        ログインロック
                                                    </label>
                                                    <div class="col-md-8 form-text pt-0 pb-2 py-md-2 fs-6 text-break">
                                                        <div class="form-check">
                                                            <input type="hidden" name="login_locked_at" value="0">
                                                            <input class="form-check-input" type="checkbox" name="login_locked_at" id="login_locked_at" value="1">
                                                            <label class="form-check-label" for="login_locked_at">解除する</label>
                                                        </div>
                                                    </div>
                                                    <div class="col-md-3"></div>
                                                        <div class="col-md-8">
                                                            <p class="fs-small text-break">
                                                                ログインロックを解除する場合はチェックしてください。<br>
                                                                {{ $assign['record']->login_locked_at_formatted }} 後に自動的に解除されます。
                                                            </p>
                                                        </div>
                                                </div>
                                                @else
                                                    <input type="hidden" name="login_locked_at" value="0">
                                                @endif

                                            <div class="row">
                                                <label class="col-md-3 col-form-label d-flex align-items-center pt-2 pb-0 py-md-2 fs-6 fw-bold" for="terminated_at" role="button">
                                                    退職日
                                                </label>
                                                <div class="col-md-8 form-text d-flex flex-row align-items-center pt-0 pb-2 py-md-2">
                                                    <input type="text" name="terminated_at" value="{{ old('terminated_at', $assign['record']->terminated_at ? date('Y/m/d', strtotime($assign['record']->terminated_at)) : null) }}" id="terminated_at" class="form-control datepicker" autocomplete="off">
                                                </div>
                                                @error('terminated_at')
                                                    <div class="col-md-3"></div>
                                                    <div class="col-md-8">
                                                        <div class="alert alert-danger mt-0 p-1 form-text" role="alert">{{ $message }}</div>
                                                    </div>
                                                @enderror
                                                <div class="col-md-3"></div>
                                                <div class="col-md-8">
                                                    <p class="fs-small text-break">
                                                        管理者が退職されたり、一時的に有効化したアカウントを無効化する場合にご入力ください。
                                                    </p>
                                                </div>
                                            </div>

                                            {{-- 進むボタン --}}
                                            <div class="mt-3">
                                                <button type="submit" class="btn btn-success d-grid w-100 text-white text-break" id="submit">確認する</button>
                                            </div>

                                            {{-- 戻るボタン --}}
                                            @if (in_array('officeAdminIndex', Auth::user()->routes()))
                                                <div class="my-3">
                                                    <a href="{{ route('officeAdminIndex', session('officeAdminIndexSearchParams')) }}" class="text-break btn btn-outline-dark col-12 mb-0">前のページに戻る</a>
                                                </div>
                                            @endif
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
