@extends('office/parts/app')

@section('meta')
    <title>権限付与 | {{ config('app.name') }}</title>
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
                                        <h5 class="card-title">権限付与</h5>
                                    </div>
                                    @if (in_array('officeRoleCreate*', Auth::user()->routes()))
                                        <div class="col-6 pt-2 text-end">
                                            <a href="{{ route('officeRoleCreateInput') }}" class="btn btn-primary">権限登録</a>
                                        </div>
                                    @endif
                                </div>
                                <div class="row">
                                    <div class="row pb-2">
                                        <div class="col-12">
                                            <p class="mt-3">
                                                <div class="text-break w-100">
                                                    各種権限を付与します。<br>
                                                    チェックがついている場合、機能を実行・閲覧することが可能です。
                                                </div>
                                            </p>
                                        </div>
                                    </div>

                                    <div class="row my-3">
                                        <div class="col-12">
                                            <div class="table-responsive text-nowrap">
                                                <table class="table table-bordered">
                                                    <thead>
                                                        <tr class="bg-black">
                                                            <th scope="col" class="text-white fw-bold py-2">機能名</th>
                                                            @foreach ($assign['roles'] as $role)
                                                                <th scope="col" class="text-white fw-bold py-2 text-center">{{ $role->name }}</th>
                                                            @endforeach
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        @foreach ($assign['routes'] as $route)
                                                            <tr>
                                                                <td>
                                                                    {{ $route->name }}
                                                                </td>
                                                                @foreach ($assign['roles'] as $role)
                                                                    <td class="text-center w-px-200" role="button">
                                                                        <input type="checkbox" name="is_allowed" value="1" class="form-check-input w-px-30 h-px-30" id="is_allowed_{{ $role->id }}_{{ $route->id }}" data-role_id="{{ $role->id }}" data-route_id="{{ $route->id }}" @checked($assign['routePermissions'][$route->id][$role->id])>
                                                                    </td>
                                                                @endforeach
                                                            </tr>
                                                        @endforeach
                                                    </tbody>
                                                </table>
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
                $(document).ready(function() {
                    $("input[name='is_allowed']").on("click", function() {
                        const isChecked = $(this).prop("checked");

                        $.ajax({
                            url: "/role-route/edit/complete",
                            method: "POST",
                            data: {
                                _token: $('meta[name="csrf-token"]').attr('content'),
                                role_id: $(this).data("role_id"),
                                route_id: $(this).data("route_id"),
                                is_allowed: isChecked ? 1 : 0
                            },
                            error: function(xhr, status, error) {
                                console.error(error);
                                alert("予期せぬエラーが発生しました。時間をおいて再度お試しください。");
                                // エラー時はチェックボックスを元の状態に戻す
                                $(this).prop("checked", !isChecked);
                            }
                        });
                    });

                    $("td").on("click", function(e) {
                        // クリックされた要素が直接チェックボックスでない場合のみ実行
                        if (! $(e.target).is(":checkbox")) {
                            // td内のチェックボックスを取得して状態を反転
                            const $checkbox = $(this).find("input[type='checkbox']");
                            $checkbox.prop("checked", ! $checkbox.prop("checked"));

                            // チェックボックスの値を取得
                            const roleId = $checkbox.data("role_id");
                            const routeId = $checkbox.data("route_id");
                            const isChecked = $checkbox.prop("checked");

                            // Ajax送信
                            $.ajax({
                                url: "/role-route/edit/complete",
                                method: "POST",
                                data: {
                                    _token: $('meta[name="csrf-token"]').attr('content'),
                                    role_id: roleId,
                                    route_id: routeId,
                                    is_allowed: isChecked ? 1 : 0
                                },
                                error: function(xhr, status, error) {
                                    console.error(error);
                                    alert("予期せぬエラーが発生しました。時間をおいて再度お試しください。");
                                    // エラー時はチェックボックスを元の状態に戻す
                                    $checkbox.prop("checked", !isChecked);
                                }
                            });
                        }
                    });
                });
            </script>
        @endpush
    </div>

@endsection
