@extends('office/parts/app')

@section('meta')
    <title>権限一覧 | {{ config('app.name') }}</title>
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
                                        <h5 class="card-title">権限一覧</h5>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-12">
                                        {{-- ページャー --}}
                                        <div class="row mt-4 align-items-center">
                                            <div class="col-md-6 text-start">
                                                該当件数 : {{ number_format($assign['records']->total()) }}件
                                            </div>
                                            <div class="col-md-6 text-end">
                                                <label for="per_page" class="me-2">表示件数 : </label>
                                                <select name="per_page" id="perPage" class="form-select d-inline w-auto">
                                                    @foreach($assign['perPages'] as $key => $label)
                                                        <option value="{{ $key }}" @if($assign['per_page'] == $key) selected @endif>{{ $label }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                        </div>
                                        <div class="row mt-4">
                                            <div class="col-12 text-end">
                                                {{ $assign['records']->appends(request()->query())->links('office/parts/item/pagination') }}
                                            </div>
                                        </div>
                                        <div class="table-responsive text-nowrap">
                                            <table class="table table-bordered">
                                                <thead>
                                                    <tr class="bg-black">
                                                        <th scope="col" class="text-white fw-bold py-2">権限名</th>
                                                        <th scope="col" class="text-white fw-bold py-2">備考</th>
                                                        <th scope="col" class="text-center text-white fw-bold py-2">操作</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @forelse ($assign['records'] as $index => $record)
                                                        <tr>
                                                            <td class="py-2">
                                                                {{ $record->name }}
                                                            </td>
                                                            <td class="py-2">
                                                                {!! nl2br(e($record->note)) !!}
                                                            </td>
                                                            <td class="text-center py-2">
                                                                @if (in_array('officeRoleShow*', Auth::user()->routes()))
                                                                    <a href="{{ route('officeRoleShow', ['id' => $record->id]) }}" class="btn btn-sm btn-icon btn-outline-info me-2" title="詳細">
                                                                        <i class="bx bx-xs bx-info-square"></i>
                                                                    </a>
                                                                @endif
                                                                @if (in_array('officeRoleEdit*', Auth::user()->routes()))
                                                                    <a href="{{ route('officeRoleEditInput', ['id' => $record->id]) }}" class="btn btn-sm btn-icon btn-outline-warning me-2" title="編集">
                                                                        <i class="bx bxs-edit"></i>
                                                                    </a>
                                                                @endif
                                                                @if (in_array('officeRoleDelete*', Auth::user()->routes()) && ! $record->inuse)
                                                                    <form method="POST" action="{{ App\Libraries\Utils::urlToPath(route('officeRoleDeleteExecute', ['id' => $record->id])) }}" enctype="multipart/form-data" class="d-inline" onsubmit="return confirmDelete()">
                                                                        @csrf
                                                                        <button type="submit" class="btn btn-sm btn-icon btn-outline-danger me-2" title="削除"><i class="bx bx-xs bxs-trash-alt"></i></button>
                                                                    </form>
                                                                @endif
                                                                @if (in_array('officeMemoIndex*', Auth::user()->routes()))
                                                                    @php
                                                                        $memoUrl = route('officeMemoIndex', ['segment' => 'roles', 'target_id' => $record->id]);
                                                                    @endphp
                                                                    <button type="button" class="btn btn-sm btn-icon btn-outline-secondary me-2" onclick="window.open('{{ $memoUrl }}', 'memo', 'width=600,height='+ window.innerHeight +',scrollbars=yes,left=' + (window.screen.width) + ',top=0')">
                                                                        <i class="bx bx-xs bx-note"></i>
                                                                    </button>
                                                                @endif
                                                            </td>
                                                        </tr>
                                                    @empty
                                                        <tr>
                                                            <td colspan="3">
                                                                データがありません。
                                                            </td>
                                                        </tr>
                                                    @endforelse
                                                </tbody>
                                            </table>
                                        </div>
                                        <div class="row mt-4">
                                            <div class="col-12">
                                                該当件数 : {{ number_format($assign['records']->total()) }}件
                                            </div>
                                        </div>
                                        <div class="row">
                                            <div class="col-12 text-end">
                                                {{-- ページャー --}}
                                                {{ $assign['records']->appends(request()->query())->links('office/parts/item/pagination') }}
                                            </div>
                                        </div>
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
