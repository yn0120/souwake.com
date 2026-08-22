<x-office.layout title="権限詳細">
    <x-office.card title="権限詳細">
        <x-slot:actions>
            @if (in_array('officeRoleIndex*', Auth::user()->routes()))
                <x-office.button variant="outline-dark" :href="route('officeRoleIndex', session('officeRoleIndexSearchParams'))">戻る</x-office.button>
            @endif
            @if (in_array('officeRoleEdit*', Auth::user()->routes()))
                <x-office.button variant="warning" :href="route('officeRoleEditInput', ['id' => $assign['record']->id])">編集</x-office.button>
            @endif
            @if (in_array('officeMemoIndex*', Auth::user()->routes()))
                @php
                    $memoUrl = route('officeMemoIndex', ['segment' => 'roles', 'target_id' => $assign['record']->id]);
                @endphp
                <x-office.button variant="info"
                                 data-memo-url="{{ $memoUrl }}">
                    メモ
                </x-office.button>
            @endif
        </x-slot:actions>

        <x-office.detail-row label="権限名">{{ $assign['record']->name }}</x-office.detail-row>
        <x-office.detail-row label="備考">{!! nl2br(e($assign['record']->note)) !!}</x-office.detail-row>
    </x-office.card>
</x-office.layout>
