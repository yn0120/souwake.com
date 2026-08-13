@extends('office/parts/app')

@section('meta')
    <title>出欠回答一覧 | {{ config('app.name') }}</title>
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
                                        <h5 class="card-title">出欠回答一覧</h5>
                                    </div>
                                </div>

                                {{-- 検索条件 --}}
                                <div class="row">
                                    <div class="col-12 mb-4 order-0">
                                        <div class="accordion mt-3" id="accordionSearchArea">
                                            <div class="card p-3 accordion-item {{ request()->accordion ? 'active' : '' }}">
                                                <div class="row">
                                                    <h2 class="accordion-header" id="headingSearch">
                                                        <button type="button" class="p-0 text-warning accordion-button {{ request()->accordion ? '' : 'collapsed' }}" data-bs-toggle="collapse" data-bs-target="#collapseSearch" aria-expanded="{{ request()->accordion ? 'true' : 'false' }}" aria-controls="collapseSearch">
                                                            検索条件
                                                        </button>
                                                    </h2>
                                                </div>
                                                <form method="GET" action="" class="">
                                                    <input type="hidden" name="accordion" value="{{ request()->accordion }}">
                                                    <input type="hidden" name="per_page" value="{{ $assign['per_page'] }}">
                                                    <div id="collapseSearch" class="accordion-collapse collapse {{ request()->accordion ? 'show' : '' }}" aria-labelledby="headingSearch" data-bs-parent="#accordionSearchArea">
                                                        <div class="accordion-body p-0">
                                                            <div class="row">
                                                                <div class="col-6 col-md-3 pt-2">
                                                                    <label class="form-label" for="name" role="button">お名前・フリガナ</label>
                                                                    <input type="text" name="name" value="{{ $assign['input']['name'] ?? null }}" class="form-control" id="name">
                                                                </div>
                                                                <div class="col-6 col-md-3 pt-2">
                                                                    <label class="form-label" for="email" role="button">メールアドレス</label>
                                                                    <input type="text" name="email" value="{{ $assign['input']['email'] ?? null }}" class="form-control emailFmt" id="email">
                                                                </div>
                                                                <div class="col-6 col-md-3 pt-2">
                                                                    <div class="w-100">
                                                                        <label class="form-label" role="button">出欠</label><br>
                                                                        @foreach ($assign['attendances'] as $key => $label)
                                                                            <span class="text-nowrap">
                                                                                <input type="checkbox" name="attendance[]" value="{{ $key }}" class="form-check-input" id="attendance_{{ $key }}" role="button" @checked(in_array($key, $assign['input']['attendance'] ?? [], true))>
                                                                                <label for="attendance_{{ $key }}" class="" role="button">{{ $label }}</label>&nbsp;
                                                                            </span>
                                                                        @endforeach
                                                                    </div>
                                                                </div>
                                                                <div class="col-6 col-md-3 pt-2">
                                                                    <div class="w-100">
                                                                        <label class="form-label" role="button">同伴者</label><br>
                                                                        <span class="text-nowrap">
                                                                            <input type="checkbox" name="companion_flag[]" value="1" class="form-check-input" id="companion_flag_1" role="button" @checked(in_array('1', $assign['input']['companion_flag'] ?? [], true))>
                                                                            <label for="companion_flag_1" class="" role="button">あり</label>&nbsp;
                                                                        </span>
                                                                        <span class="text-nowrap">
                                                                            <input type="checkbox" name="companion_flag[]" value="0" class="form-check-input" id="companion_flag_0" role="button" @checked(in_array('0', $assign['input']['companion_flag'] ?? [], true))>
                                                                            <label for="companion_flag_0" class="" role="button">なし</label>&nbsp;
                                                                        </span>
                                                                    </div>
                                                                </div>
                                                                <div class="col-12 col-md-6 pt-2">
                                                                    <div class="w-100">
                                                                        <label class="form-label" role="button">ご住所の国</label><br>
                                                                        @foreach ($assign['countries'] as $key => $label)
                                                                            <span class="text-nowrap">
                                                                                <input type="checkbox" name="country[]" value="{{ $key }}" class="form-check-input" id="country_{{ $key }}" role="button" @checked(in_array($key, $assign['input']['country'] ?? [], true))>
                                                                                <label for="country_{{ $key }}" class="" role="button">{{ $label }}</label>&nbsp;
                                                                            </span>
                                                                        @endforeach
                                                                    </div>
                                                                </div>
                                                                <div class="col-6 col-md-3 pt-2">
                                                                    <label class="form-label" for="created_at_from" role="button">回答日（開始）</label>
                                                                    <input type="text" name="created_at_from" value="{{ $assign['input']['created_at_from'] ?? null }}" class="form-control datepicker" id="created_at_from" autocomplete="off">
                                                                </div>
                                                                <div class="col-6 col-md-3 pt-2">
                                                                    <label class="form-label" for="created_at_to" role="button">回答日（終了）</label>
                                                                    <input type="text" name="created_at_to" value="{{ $assign['input']['created_at_to'] ?? null }}" class="form-control datepicker" id="created_at_to" autocomplete="off">
                                                                </div>
                                                            </div>
                                                            <div class="row">
                                                                <div class="col-12">
                                                                    <button type="submit" class="btn btn-success w-100 text-white rounded-2 mt-3 py-1 form">検索する</button>
                                                                </div>
                                                            </div>
                                                            <div class="row">
                                                                <div class="col-12">
                                                                    <a href="{{ route('officeWeddingRsvpIndex') }}" class="btn btn-outline-dark w-100 rounded-2 mt-3 py-1">検索条件をクリアする</a>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="row my-3">
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
                                                        <th scope="col" class="text-white fw-bold py-2">ID</th>
                                                        <th scope="col" class="text-center text-white fw-bold py-2">出欠</th>
                                                        <th scope="col" class="text-white fw-bold py-2">お名前</th>
                                                        <th scope="col" class="text-center text-white fw-bold py-2">同伴者</th>
                                                        <th scope="col" class="text-white fw-bold py-2">メールアドレス</th>
                                                        <th scope="col" class="text-center text-white fw-bold py-2">お祝い画像</th>
                                                        <th scope="col" class="text-white fw-bold py-2">回答日時</th>
                                                        <th scope="col" class="text-center text-white fw-bold py-2">操作</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @forelse ($assign['records'] as $record)
                                                        <tr class="{{ $record->attendance === App\Models\WeddingRsvpModel::ATTENDANCE_ABSENT ? 'bg-lighter' : '' }}">
                                                            <td class="py-2">
                                                                {{ number_format($record->id) }}
                                                            </td>
                                                            <td class="text-center py-2">
                                                                {{ $record->attendanceLabel() }}
                                                            </td>
                                                            <td class="py-2">
                                                                {{ $record->fullName() }}
                                                            </td>
                                                            <td class="text-center py-2">
                                                                {{ $record->companions_count ? number_format($record->companions_count).'名' : 'なし' }}
                                                            </td>
                                                            <td class="py-2">
                                                                {{ $record->email }}
                                                            </td>
                                                            <td class="text-center py-2">
                                                                {{ $record->photos_count ? number_format($record->photos_count).'枚' : 'なし' }}
                                                            </td>
                                                            <td class="py-2">
                                                                {{ optional($record->created_at)->format('Y年m月d日 H:i') }}
                                                            </td>
                                                            <td class="text-center py-2">
                                                                @if (in_array('officeWeddingRsvpShow*', Auth::user()->routes()))
                                                                    <a href="{{ route('officeWeddingRsvpShow', ['id' => $record->id]) }}" class="btn btn-sm btn-icon btn-outline-info me-2" title="詳細">
                                                                        <i class="bx bx-xs bx-info-square"></i>
                                                                    </a>
                                                                @endif
                                                                @if (in_array('officeWeddingRsvpEdit*', Auth::user()->routes()))
                                                                    <a href="{{ route('officeWeddingRsvpEditInput', ['id' => $record->id]) }}" class="btn btn-sm btn-icon btn-outline-warning me-2" title="編集">
                                                                        <i class="bx bx-xs bxs-pencil"></i>
                                                                    </a>
                                                                @endif
                                                                @if (in_array('officeWeddingRsvpDelete*', Auth::user()->routes()))
                                                                    <form method="POST" action="{{ App\Libraries\Utils::urlToPath(route('officeWeddingRsvpDeleteExecute', ['id' => $record->id])) }}" enctype="multipart/form-data" class="d-inline" onsubmit="return confirmDelete()">
                                                                        @csrf
                                                                        <button type="submit" class="btn btn-sm btn-icon btn-outline-danger me-2" title="削除"><i class="bx bx-xs bxs-trash-alt"></i></button>
                                                                    </form>
                                                                @endif
                                                            </td>
                                                        </tr>
                                                    @empty
                                                        <tr>
                                                            <td colspan="8">
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
