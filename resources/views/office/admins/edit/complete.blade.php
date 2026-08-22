<x-office.layout title="管理者編集完了">
    <x-office.card title="管理者編集完了">
        <p class="text-sm break-words text-heading">管理者を編集しました。</p>

        <div class="mt-6 flex flex-wrap gap-2">
            @if (in_array('officeAdminIndex*', Auth::user()->routes()))
                <x-office.button variant="primary" :href="route('officeAdminIndex', session('officeAdminIndexSearchParams'))">管理者一覧</x-office.button>
            @endif
            @if (in_array('officeAdminShow*', Auth::user()->routes()))
                <x-office.button variant="primary" :href="route('officeAdminShow', ['id' => $assign['id']])">管理者詳細</x-office.button>
            @endif
        </div>
    </x-office.card>
</x-office.layout>
