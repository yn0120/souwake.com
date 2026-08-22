<x-office.layout title="権限一覧">
    <x-office.card title="権限一覧">
        <x-office.list-toolbar :paginator="$assign['records']" :per-pages="$assign['perPages']" :per-page="$assign['per_page']" />

        <x-office.table>
            <x-slot:head>
                <x-office.table.th>権限名</x-office.table.th>
                <x-office.table.th>備考</x-office.table.th>
                <x-office.table.th align="center">操作</x-office.table.th>
            </x-slot:head>

            @forelse ($assign['records'] as $record)
                <tr>
                    <x-office.table.td>{{ $record->name }}</x-office.table.td>
                    <x-office.table.td :nowrap="false">{!! nl2br(e($record->note)) !!}</x-office.table.td>
                    <x-office.table.td align="center">
                        <div class="flex items-center justify-center gap-2">
                            @if (in_array('officeRoleShow*', Auth::user()->routes()))
                                <x-office.button variant="outline-info" size="icon" title="詳細"
                                                 :href="route('officeRoleShow', ['id' => $record->id])">
                                    <x-office.icon name="info" class="size-4" />
                                </x-office.button>
                            @endif
                            @if (in_array('officeRoleEdit*', Auth::user()->routes()))
                                <x-office.button variant="outline-warning" size="icon" title="編集"
                                                 :href="route('officeRoleEditInput', ['id' => $record->id])">
                                    <x-office.icon name="pencil" class="size-4" />
                                </x-office.button>
                            @endif
                            @if (in_array('officeRoleDelete*', Auth::user()->routes()) && ! $record->inuse)
                                <form method="POST" enctype="multipart/form-data" onsubmit="return confirmDelete()"
                                      action="{{ App\Libraries\Utils::urlToPath(route('officeRoleDeleteExecute', ['id' => $record->id])) }}">
                                    @csrf
                                    <x-office.button variant="outline-danger" size="icon" type="submit" title="削除">
                                        <x-office.icon name="trash" class="size-4" />
                                    </x-office.button>
                                </form>
                            @endif
                            @if (in_array('officeMemoIndex*', Auth::user()->routes()))
                                @php
                                    $memoUrl = route('officeMemoIndex', ['segment' => 'roles', 'target_id' => $record->id]);
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
                    <x-office.table.td colspan="3" :nowrap="false">データがありません。</x-office.table.td>
                </tr>
            @endforelse
        </x-office.table>

        <x-office.list-toolbar :paginator="$assign['records']" />
    </x-office.card>
</x-office.layout>
