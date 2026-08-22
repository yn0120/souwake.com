<x-office.layout title="管理者一覧">
    <x-office.card title="管理者一覧">
        @if (in_array('officeAdminCreate*', Auth::user()->routes()))
            <x-slot:actions>
                <x-office.button variant="primary" :href="route('officeAdminCreateInput')">登録</x-office.button>
            </x-slot:actions>
        @endif

        <x-office.search-panel :per-page="$assign['per_page']" :clear-href="route('officeAdminIndex')">
            <div class="col-span-12 sm:col-span-6 lg:col-span-3">
                <x-office.form.label for="name">氏名</x-office.form.label>
                <x-office.form.input name="name" id="name" :value="$assign['input']['name'] ?? null" />
            </div>

            <div class="col-span-12 sm:col-span-6 lg:col-span-3">
                <x-office.form.label for="email">メールアドレス</x-office.form.label>
                <x-office.form.input name="email" id="email" class="emailFmt" :value="$assign['input']['email'] ?? null" />
            </div>

            <div class="col-span-12 lg:col-span-6">
                <x-office.form.label>権限</x-office.form.label>
                <div class="flex flex-wrap gap-x-4 gap-y-2">
                    @foreach ($assign['roles'] as $key => $role)
                        <x-office.form.check name="role_id[]" value="{{ $key }}" id="role_id_{{ $key }}"
                                             :checked="in_array($key, $assign['input']['role_id'] ?? [], false)">{{ $role }}</x-office.form.check>
                    @endforeach
                </div>
            </div>

            <div class="col-span-12 sm:col-span-6 lg:col-span-3">
                <x-office.form.label>状態</x-office.form.label>
                <div class="flex flex-wrap gap-x-4 gap-y-2">
                    <x-office.form.check name="statuses[]" value="is_activated" id="statuses_activated"
                                         :checked="in_array('is_activated', $assign['input']['statuses'] ?? [], false)">初期登録中</x-office.form.check>
                    <x-office.form.check name="statuses[]" value="is_terminated" id="statuses_terminated"
                                         :checked="in_array('is_terminated', $assign['input']['statuses'] ?? [], false)">退職済み</x-office.form.check>
                </div>
            </div>
        </x-office.search-panel>

        <x-office.list-toolbar :paginator="$assign['records']" :per-pages="$assign['perPages']" :per-page="$assign['per_page']" />

        <x-office.table>
            <x-slot:head>
                <x-office.table.th>ID</x-office.table.th>
                <x-office.table.th>氏名</x-office.table.th>
                <x-office.table.th>権限</x-office.table.th>
                <x-office.table.th>メールアドレス</x-office.table.th>
                <x-office.table.th align="center">利用状況</x-office.table.th>
                <x-office.table.th align="center">操作</x-office.table.th>
            </x-slot:head>

            @forelse ($assign['records'] as $record)
                <tr @class(['bg-neutral-secondary' => (bool) $record->terminated_at])>
                    <x-office.table.td>{{ number_format($record->id) }}</x-office.table.td>
                    <x-office.table.td>{{ $record->name }}</x-office.table.td>
                    <x-office.table.td>{{ $assign['roles'][$record->role_id] ?? '' }}</x-office.table.td>
                    <x-office.table.td>{{ $record->email }}</x-office.table.td>
                    <x-office.table.td align="center">
                        @if ($record->terminated_at)
                            退職済み
                        @elseif (! $record->activated_at)
                            初期登録中
                        @else
                            利用中
                        @endif
                    </x-office.table.td>
                    <x-office.table.td align="center">
                        <div class="flex items-center justify-center gap-2">
                            @if (in_array('officeAdminShow*', Auth::user()->routes()))
                                <x-office.button variant="outline-info" size="icon" title="詳細"
                                                 :href="route('officeAdminShow', ['id' => $record->id])">
                                    <x-office.icon name="info" class="size-4" />
                                </x-office.button>
                            @endif
                            @if (in_array('officeAdminRemind*', Auth::user()->routes()) && ! $record->activated_at)
                                <form method="POST" enctype="multipart/form-data" onsubmit="return confirmRemind()"
                                      action="{{ App\Libraries\Utils::urlToPath(route('officeAdminRemindExecute', ['id' => $record->id])) }}">
                                    @csrf
                                    <x-office.button variant="outline-dark" size="icon" type="submit" title="再送信">
                                        <x-office.icon name="mail-send" class="size-4" />
                                    </x-office.button>
                                </form>
                            @endif
                            @if (in_array('officeAdminEdit*', Auth::user()->routes()) && $record->activated_at)
                                <x-office.button variant="outline-warning" size="icon" title="編集"
                                                 :href="route('officeAdminEditInput', ['id' => $record->id])">
                                    <x-office.icon name="pencil" class="size-4" />
                                </x-office.button>
                            @endif
                            @if (in_array('officeAdminDelete*', Auth::user()->routes()))
                                <form method="POST" enctype="multipart/form-data" onsubmit="return confirmDelete()"
                                      action="{{ App\Libraries\Utils::urlToPath(route('officeAdminDeleteExecute', ['id' => $record->id])) }}">
                                    @csrf
                                    <x-office.button variant="outline-danger" size="icon" type="submit" title="削除">
                                        <x-office.icon name="trash" class="size-4" />
                                    </x-office.button>
                                </form>
                            @endif
                            @if (in_array('officeMemoIndex*', Auth::user()->routes()))
                                @php
                                    $memoUrl = route('officeMemoIndex', ['segment' => 'admins', 'target_id' => $record->id]);
                                @endphp
                                <x-office.button variant="outline-secondary" size="icon" title="メモ"
                                                 data-memo-url="{{ $memoUrl }}">
                                    <x-office.icon name="note" class="size-4" />
                                </x-office.button>
                            @endif
                        </div>
                    </x-office.table.td>
                </tr>
            @empty
                <tr>
                    <x-office.table.td colspan="6" :nowrap="false">データがありません。</x-office.table.td>
                </tr>
            @endforelse
        </x-office.table>

        <x-office.list-toolbar :paginator="$assign['records']" />
    </x-office.card>

    <x-slot:scripts>
        <script>
            // パスワード設定メール再送信の確認
            window.confirmRemind = function () {
                return window.confirm('パスワード設定メールを再送信しますか？');
            };
        </script>
    </x-slot:scripts>
</x-office.layout>
