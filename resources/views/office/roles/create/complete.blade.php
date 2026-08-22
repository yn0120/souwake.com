<x-office.layout title="権限登録完了">
    <x-office.card title="権限登録完了">
        <p class="text-sm break-words text-heading">権限を登録しました。</p>

        <div class="mt-6 flex flex-wrap gap-2">
            @if (in_array('officeRoleIndex*', Auth::user()->routes()))
                <x-office.button variant="primary" :href="route('officeRoleIndex', session('officeRolesIndexSearchParams'), false)">権限一覧</x-office.button>
            @endif
            @if (in_array('officeRoleCreate*', Auth::user()->routes()))
                <x-office.button variant="primary" :href="route('officeRoleCreateInput', [], false)">引き続き権限を登録する</x-office.button>
            @endif
        </div>
    </x-office.card>
</x-office.layout>
