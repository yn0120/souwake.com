<x-office.layout title="管理者詳細">
    <x-office.card title="管理者詳細">
        <x-slot:actions>
            @if (in_array('officeAdminIndex*', Auth::user()->routes()))
                <x-office.button variant="outline-dark" :href="route('officeAdminIndex', session('officeAdminIndexSearchParams'))">戻る</x-office.button>
            @endif
            @if (in_array('officeAdminEdit*', Auth::user()->routes()) && $assign['record']->activated_at)
                <x-office.button variant="warning" :href="route('officeAdminEditInput', ['id' => $assign['record']->id])">編集</x-office.button>
            @endif
            @if (in_array('officeMemoIndex*', Auth::user()->routes()))
                @php
                    $memoUrl = route('officeMemoIndex', ['segment' => 'admins', 'target_id' => $assign['record']->id]);
                @endphp
                <x-office.button variant="info"
                                 data-memo-url="{{ $memoUrl }}">
                    メモ
                </x-office.button>
            @endif
        </x-slot:actions>

        <x-office.detail-row label="ID">{{ number_format($assign['record']->id) }}</x-office.detail-row>
        <x-office.detail-row label="氏名">{{ $assign['record']->name }}</x-office.detail-row>
        <x-office.detail-row label="権限">{{ $assign['roles'][$assign['record']->role_id] ?? '' }}</x-office.detail-row>
        <x-office.detail-row label="メールアドレス">{{ $assign['record']->email }}</x-office.detail-row>

        @if (! $assign['record']->activated_at)
            <x-office.detail-row label="登録状況">
                管理者がアカウント発行を完了していません。<br>
                パスワード設定用メールを送っていますので、パスワードを設定するようお伝えください。
            </x-office.detail-row>
        @endif

        @if ($assign['record']->terminated_at)
            <x-office.detail-row label="退職日">
                {{ date('Y年m月d日', strtotime($assign['record']->terminated_at)) }}
            </x-office.detail-row>
        @endif
    </x-office.card>
</x-office.layout>
