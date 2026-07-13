@extends('office/parts/app')

@section('meta')
    <title>管理者一覧 | {{ config('app.name') }}</title>
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
                                    <div class="col-6 pt-2">
                                        <h5 class="card-title">管理者一覧</h5>
                                    </div>
                                    @if (in_array('officeAdminCreate*', Auth::user()->routes()))
                                        <div class="col-6 pt-2 text-end">
                                            <a href="{{ route('officeAdminCreateInput') }}" class="btn btn-primary">登録</a>
                                        </div>
                                    @endif
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
                                                                {{--
                                                                <div class="col-6 col-md-3 pt-2">
                                                                    <div class="w-100">
                                                                        <label class="form-label" role="button">チェックボックス</label><br>
                                                                        <span class="text-nowrap">
                                                                            <input type="checkbox" name="checkboxSample[]" value="1" class="form-check-input" id="checkboxSample_1" role="button" @checked(in_array(1, $assign['input']['checkboxSample'] ?? [], false))>
                                                                            <label for="checkboxSample_1" class="" role="button">選択肢1</label>&nbsp;
                                                                        </span>
                                                                        <span class="text-nowrap">
                                                                            <input type="checkbox" name="checkboxSample[]" value="2" class="form-check-input" id="checkboxSample_2" role="button" @checked(in_array(2, $assign['input']['checkboxSample'] ?? [], false))>
                                                                            <label for="checkboxSample_2" class="" role="button">選択肢2</label>&nbsp;
                                                                        </span>
                                                                    </div>
                                                                </div>
                                                                <div class="col-6 col-md-3 pt-2">
                                                                    <label class="form-label" for="selectSample" role="button">プルダウン</label>
                                                                    <select name="selectSample" class="form-select" id="selectSample" role="button">
                                                                        <option value="">未選択</option>
                                                                        <option value="1" id="selectSample_1" @selected($assign['input']['selectSample'] ?? null == 1)>選択肢1</option>
                                                                        <option value="2" id="selectSample_2" @selected($assign['input']['selectSample'] ?? null == 2)>選択肢2</option>
                                                                    </select>
                                                                </div>
                                                                <div class="col-6 col-md-3 pt-2">
                                                                    <label class="form-label" for="textSample" role="button">テキスト</label>
                                                                    <input type="text" name="textSample" value="{{ $assign['input']['textSample'] ?? null }}" class="form-control" id="textSample">
                                                                </div>
                                                                --}}
                                                                <div class="col-6 col-md-3 pt-2">
                                                                    <label class="form-label" for="name" role="button">氏名</label>
                                                                    <input type="text" name="name" value="{{ $assign['input']['name'] ?? null }}" class="form-control" id="name">
                                                                </div>
                                                                <div class="col-6 col-md-3 pt-2">
                                                                    <label class="form-label" for="email" role="button">メールアドレス</label>
                                                                    <input type="text" name="email" value="{{ $assign['input']['email'] ?? null }}" class="form-control emailFmt" id="email">
                                                                </div>
                                                                <div class="col-12 col-md-6 pt-2">
                                                                    <div class="w-100">
                                                                        <label class="form-label" role="button">権限</label><br>
                                                                        @foreach ($assign['roles'] as $key => $role)
                                                                            <span class="text-nowrap">
                                                                                <input type="checkbox" name="role_id[]" value="{{ $key }}" class="form-check-input" id="role_id_{{ $key }}" role="button" @checked(in_array($key, $assign['input']['role_id'] ?? [], false))>
                                                                                <label for="role_id_{{ $key }}" class="" role="button">{{ $role }}</label>&nbsp;
                                                                            </span>
                                                                        @endforeach
                                                                    </div>
                                                                </div>
                                                                <div class="col-6 col-md-3 pt-2">
                                                                    <label class="form-label" for="statuses" role="button"></label><br>
                                                                    <span class="text-nowrap">
                                                                        <input type="checkbox" name="statuses[]" value="is_activated" class="form-check-input" id="statuses_activated" role="button" @checked(in_array('is_activated', $assign['input']['statuses'] ?? [], false))>
                                                                        <label for="statuses_activated" role="button">初期登録中</label>&nbsp;
                                                                    </span>
                                                                    <span class="text-nowrap">
                                                                        <input type="checkbox" name="statuses[]" value="is_terminated" class="form-check-input" id="statuses_terminated" role="button" @checked(in_array('is_terminated', $assign['input']['statuses'] ?? [], false))>
                                                                        <label for="statuses_terminated" role="button">退職済み</label>&nbsp;
                                                                    </span>
                                                                </div>
                                                            </div>
                                                            <div class="row">
                                                                <div class="col-12">
                                                                    <button type="submit" class="btn btn-success w-100 text-white rounded-2 mt-3 py-1 form">検索する</button>
                                                                </div>
                                                            </div>
                                                            <div class="row">
                                                                <div class="col-12">
                                                                    <a href="{{ route('officeAdminIndex') }}" class="btn btn-outline-dark w-100 rounded-2 mt-3 py-1">検索条件をクリアする</a>
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
                                                        <th scope="col" class="text-white fw-bold py-2">氏名</th>
                                                        <th scope="col" class="text-white fw-bold py-2">権限</th>
                                                        <th scope="col" class="text-white fw-bold py-2">メールアドレス</th>
                                                        <th scope="col" class="text-center text-white fw-bold py-2">利用状況</th>
                                                        <th scope="col" class="text-center text-white fw-bold py-2">操作</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @forelse ($assign['records'] as $index => $record)
                                                        <tr class="{{ $record->terminated_at ? 'bg-lighter' : '' }}">
                                                            <td class="py-2">
                                                                {{ number_format($record->id) }}
                                                            </td>
                                                            <td class="py-2">
                                                                {{ $record->name }}
                                                            </td>
                                                            <td class="py-2">
                                                                {{ $assign['roles'][$record->role_id] ?? '' }}
                                                            </td>
                                                            <td class="py-2">
                                                                {{ $record->email }}
                                                            </td>
                                                            <td class="text-center py-2">
                                                                @if ($record->terminated_at)
                                                                    退職済み
                                                                @else
                                                                    @if (! $record->activated_at)
                                                                        初期登録中
                                                                    @else
                                                                        利用中
                                                                    @endif
                                                                @endif
                                                            </td>
                                                            <td class="text-center py-2">
                                                                @if (in_array('officeAdminShow', Auth::user()->routes()))
                                                                    <a href="{{ route('officeAdminShow', ['id' => $record->id]) }}" class="btn btn-sm btn-icon btn-outline-info me-2" title="詳細">
                                                                        <i class="bx bx-xs bx-info-square"></i>
                                                                    </a>
                                                                @endif
                                                                @if (in_array('officeAdminRemind*', Auth::user()->routes()) && ! $record->activated_at)
                                                                    <form method="POST" action="{{ App\Libraries\Utils::urlToPath(route('officeAdminRemindExecute', ['id' => $record->id])) }}" enctype="multipart/form-data" class="d-inline" onsubmit="return confirmRemind()">
                                                                        @csrf
                                                                        <button type="submit" class="btn btn-sm btn-icon btn-outline-dark me-2" title="再送信"><i class="bx bx-xs bx-mail-send"></i></button>
                                                                    </form>
                                                                @endif
                                                                @if (in_array('officeAdminEdit*', Auth::user()->routes()) && $record->activated_at)
                                                                    <a href="{{ route('officeAdminEditInput', ['id' => $record->id]) }}" class="btn btn-sm btn-icon btn-outline-warning me-2" title="編集">
                                                                        <i class="bx bx-xs bxs-pencil"></i>
                                                                    </a>
                                                                @endif
                                                                @if (in_array('officeAdminDelete*', Auth::user()->routes()))
                                                                    <form method="POST" action="{{ App\Libraries\Utils::urlToPath(route('officeAdminDeleteExecute', ['id' => $record->id])) }}" enctype="multipart/form-data" class="d-inline" onsubmit="return confirmDelete()">
                                                                        @csrf
                                                                        <button type="submit" class="btn btn-sm btn-icon btn-outline-danger me-2" title="削除"><i class="bx bx-xs bxs-trash-alt"></i></button>
                                                                    </form>
                                                                @endif
                                                                @if (in_array('officeMemoIndex', Auth::user()->routes()))
                                                                    @php
                                                                        $memoUrl = route('officeMemoIndex', ['segment' => 'admins', 'target_id' => $record->id]);
                                                                    @endphp
                                                                    <button type="button" class="btn btn-sm btn-icon btn-outline-secondary me-2" onclick="window.open('{{ $memoUrl }}', 'memo', 'width=600,height='+ window.innerHeight +',scrollbars=yes,left=' + (window.screen.width) + ',top=0')">
                                                                        <i class="bx bx-xs bx-note"></i>
                                                                    </button>
                                                                @endif
                                                            </td>
                                                        </tr>
                                                    @empty
                                                        <tr>
                                                            <td colspan="6">
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
            <script>
                // パスワード設定メール再送信確認のポップアップ表示
                window.confirmRemind = function() {
                    return confirm('パスワード設定メールを再送信しますか？');
                }
            </script>
        @endpush
    </div>

@endsection
