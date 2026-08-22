<x-office.layout title="出欠回答一覧">
    <x-office.card title="出欠回答一覧">
        <x-office.search-panel :per-page="$assign['per_page']" :clear-href="route('officeWeddingRsvpIndex')">
            <div class="col-span-12 sm:col-span-6 lg:col-span-3">
                <x-office.form.label for="name">お名前・フリガナ</x-office.form.label>
                <x-office.form.input name="name" id="name" :value="$assign['input']['name'] ?? null" />
            </div>

            <div class="col-span-12 sm:col-span-6 lg:col-span-3">
                <x-office.form.label for="email">メールアドレス</x-office.form.label>
                <x-office.form.input name="email" id="email" class="emailFmt" :value="$assign['input']['email'] ?? null" />
            </div>

            <div class="col-span-12 sm:col-span-6 lg:col-span-3">
                <x-office.form.label>出欠</x-office.form.label>
                <div class="flex flex-wrap gap-x-4 gap-y-2">
                    @foreach ($assign['attendances'] as $key => $label)
                        <x-office.form.check name="attendance[]" value="{{ $key }}" id="attendance_{{ $key }}"
                                             :checked="in_array($key, $assign['input']['attendance'] ?? [], true)">{{ $label }}</x-office.form.check>
                    @endforeach
                </div>
            </div>

            <div class="col-span-12 sm:col-span-6 lg:col-span-3">
                <x-office.form.label>同伴者</x-office.form.label>
                <div class="flex flex-wrap gap-x-4 gap-y-2">
                    <x-office.form.check name="companion_flag[]" value="1" id="companion_flag_1"
                                         :checked="in_array('1', $assign['input']['companion_flag'] ?? [], true)">あり</x-office.form.check>
                    <x-office.form.check name="companion_flag[]" value="0" id="companion_flag_0"
                                         :checked="in_array('0', $assign['input']['companion_flag'] ?? [], true)">なし</x-office.form.check>
                </div>
            </div>

            <div class="col-span-12 lg:col-span-6">
                <x-office.form.label>ご住所の国</x-office.form.label>
                <div class="flex flex-wrap gap-x-4 gap-y-2">
                    @foreach ($assign['countries'] as $key => $label)
                        <x-office.form.check name="country[]" value="{{ $key }}" id="country_{{ $key }}"
                                             :checked="in_array($key, $assign['input']['country'] ?? [], true)">{{ $label }}</x-office.form.check>
                    @endforeach
                </div>
            </div>

            <div class="col-span-12 sm:col-span-6 lg:col-span-3">
                <x-office.form.label for="created_at_from">回答日（開始）</x-office.form.label>
                <x-office.form.datepicker name="created_at_from" id="created_at_from"
                                          :value="$assign['input']['created_at_from'] ?? null" />
            </div>

            <div class="col-span-12 sm:col-span-6 lg:col-span-3">
                <x-office.form.label for="created_at_to">回答日（終了）</x-office.form.label>
                <x-office.form.datepicker name="created_at_to" id="created_at_to"
                                          :value="$assign['input']['created_at_to'] ?? null" />
            </div>
        </x-office.search-panel>

        <x-office.list-toolbar :paginator="$assign['records']" :per-pages="$assign['perPages']" :per-page="$assign['per_page']" />

        <x-office.table>
            <x-slot:head>
                <x-office.table.th>ID</x-office.table.th>
                <x-office.table.th align="center">出欠</x-office.table.th>
                <x-office.table.th>お名前</x-office.table.th>
                <x-office.table.th align="center">同伴者</x-office.table.th>
                <x-office.table.th>メールアドレス</x-office.table.th>
                <x-office.table.th align="center">お祝い画像</x-office.table.th>
                <x-office.table.th>回答日時</x-office.table.th>
                <x-office.table.th align="center">操作</x-office.table.th>
            </x-slot:head>

            @forelse ($assign['records'] as $record)
                <tr @class(['bg-neutral-secondary' => $record->attendance === App\Models\WeddingRsvpModel::ATTENDANCE_ABSENT])>
                    <x-office.table.td>{{ number_format($record->id) }}</x-office.table.td>
                    <x-office.table.td align="center">{{ $record->attendanceLabel() }}</x-office.table.td>
                    <x-office.table.td>{{ $record->fullName() }}</x-office.table.td>
                    <x-office.table.td align="center">
                        {{ $record->companions_count ? number_format($record->companions_count).'名' : 'なし' }}
                    </x-office.table.td>
                    <x-office.table.td>{{ $record->email }}</x-office.table.td>
                    <x-office.table.td align="center">
                        {{ $record->photos_count ? number_format($record->photos_count).'枚' : 'なし' }}
                    </x-office.table.td>
                    <x-office.table.td>{{ optional($record->created_at)->format('Y年m月d日 H:i') }}</x-office.table.td>
                    <x-office.table.td align="center">
                        <div class="flex items-center justify-center gap-2">
                            @if (in_array('officeWeddingRsvpShow*', Auth::user()->routes()))
                                <x-office.button variant="outline-info" size="icon" title="詳細"
                                                 :href="route('officeWeddingRsvpShow', ['id' => $record->id])">
                                    <x-office.icon name="info" class="size-4" />
                                </x-office.button>
                            @endif
                            @if (in_array('officeWeddingRsvpEdit*', Auth::user()->routes()))
                                <x-office.button variant="outline-warning" size="icon" title="編集"
                                                 :href="route('officeWeddingRsvpEditInput', ['id' => $record->id])">
                                    <x-office.icon name="pencil" class="size-4" />
                                </x-office.button>
                            @endif
                            @if (in_array('officeWeddingRsvpDelete*', Auth::user()->routes()))
                                <form method="POST" enctype="multipart/form-data" onsubmit="return confirmDelete()"
                                      action="{{ App\Libraries\Utils::urlToPath(route('officeWeddingRsvpDeleteExecute', ['id' => $record->id])) }}">
                                    @csrf
                                    <x-office.button variant="outline-danger" size="icon" type="submit" title="削除">
                                        <x-office.icon name="trash" class="size-4" />
                                    </x-office.button>
                                </form>
                            @endif
                        </div>
                    </x-office.table.td>
                </tr>
            @empty
                <tr>
                    <x-office.table.td colspan="8" :nowrap="false">データがありません。</x-office.table.td>
                </tr>
            @endforelse
        </x-office.table>

        <x-office.list-toolbar :paginator="$assign['records']" />
    </x-office.card>
</x-office.layout>
