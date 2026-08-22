{{--
    権限付与。

    表のセルをクリックすると、その場で権限を付与/剥奪する（非同期）。
    セルは <label> がチェックボックスを包む構造にしているため、
    「セルのどこを押してもトグルされる」動きはブラウザ標準のまま成立する
    （旧実装のように td のクリック位置を判定するJSは不要）。
--}}
<x-office.layout title="権限付与">
    <x-office.card title="権限付与">
        @if (in_array('officeRoleCreate*', Auth::user()->routes()))
            <x-slot:actions>
                <x-office.button variant="primary" :href="route('officeRoleCreateInput')">権限登録</x-office.button>
            </x-slot:actions>
        @endif

        <p class="mb-4 text-sm break-words text-body">
            各種権限を付与します。<br>
            チェックがついている場合、機能を実行・閲覧することが可能です。
        </p>

        <x-office.table>
            <x-slot:head>
                <x-office.table.th>機能名</x-office.table.th>
                @foreach ($assign['roles'] as $role)
                    <x-office.table.th align="center">{{ $role->name }}</x-office.table.th>
                @endforeach
            </x-slot:head>

            @foreach ($assign['routes'] as $route)
                <tr>
                    <x-office.table.td>{{ $route->name }}</x-office.table.td>

                    @foreach ($assign['roles'] as $role)
                        <x-office.table.td align="center" clickable padding="" class="w-48">
                            <label class="flex cursor-pointer items-center justify-center px-3 py-3">
                                <input type="checkbox"
                                       name="is_allowed"
                                       value="1"
                                       id="is_allowed_{{ $role->id }}_{{ $route->id }}"
                                       data-role-id="{{ $role->id }}"
                                       data-route-id="{{ $route->id }}"
                                       class="size-7 cursor-pointer rounded border-default text-brand focus:ring-2 focus:ring-brand-medium"
                                       @checked($assign['routePermissions'][$route->id][$role->id])>
                            </label>
                        </x-office.table.td>
                    @endforeach
                </tr>
            @endforeach
        </x-office.table>
    </x-office.card>

    <x-slot:scripts>
        <script>
            window.roleRoutesConfig = {
                updateUrl: @json(route('officeRoleRouteEditExecute', [], false)),
                csrfToken: @json(csrf_token()),
            };
        </script>
        @vite('resources/js/office/role-routes.js')
    </x-slot:scripts>
</x-office.layout>
