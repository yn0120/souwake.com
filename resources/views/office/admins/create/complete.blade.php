@extends('office/parts/app')

@section('meta')
    <title>管理者登録完了 | {{ config('app.name') }}</title>
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
                                        <h5 class="card-title">管理者登録完了</h5>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-12">
                                        <p class="mt-3">
                                            <div class="text-break w-100">管理者を登録しました。</div>
                                        </p>
                                    </div>
                                    @if (in_array('officeAdminIndex', Auth::user()->routes()))
                                        <div class="col-auto pb-2">
                                            <a href="{{ route('officeAdminIndex', session('officeAdminIndexSearchParams')) }}" class="btn btn-primary">管理者一覧</a>
                                        </div>
                                    @endif
                                    @if (in_array('officeAdminCreate*', Auth::user()->routes()))
                                        <div class="col-auto pb-2">
                                            <a href="{{ route('officeAdminCreateInput') }}" class="btn btn-primary">引き続き管理者を登録する</a>
                                        </div>
                                    @endif
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
