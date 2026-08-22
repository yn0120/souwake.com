<x-office.layout title="権限編集完了">
    <x-office.card title="権限編集完了">
        <p class="text-sm break-words text-heading">権限を編集しました。</p>

        <div class="mt-6 flex flex-wrap gap-2">
            @if (in_array('officeRoleIndex*', Auth::user()->routes()))
                <x-office.button variant="primary" :href="route('officeRoleIndex', session('officeRolesIndexSearchParams'))">権限一覧</x-office.button>
            @endif
            @if (in_array('officeRoleShow*', Auth::user()->routes()))
                <x-office.button variant="primary" :href="route('officeRoleShow', ['id' => $assign['id']])">権限詳細</x-office.button>
            @endif
        </div>
    </x-office.card>
</x-office.layout>
