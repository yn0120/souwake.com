<x-office.layout title="権限登録">
    <x-office.card title="権限登録">
        <form method="POST" action="{{ route('officeRoleCreateConfirm', [], false) }}" enctype="multipart/form-data">
            @csrf

            <x-office.form.row label="権限名" for="name" required name="name">
                <x-office.form.input name="name" id="name" :value="old('name')" />
            </x-office.form.row>

            <x-office.form.row label="備考" for="note" name="note">
                <x-office.form.textarea name="note" id="note" class="autoHeight">{{ old('note') }}</x-office.form.textarea>
            </x-office.form.row>

            <div class="mt-6 space-y-2">
                <x-office.button variant="success" type="submit" id="submit" class="w-full">確認する</x-office.button>
                @if (in_array('officeRoleIndex*', Auth::user()->routes()))
                    <x-office.button variant="outline-dark" class="w-full"
                                     :href="route('officeRoleIndex', session('officeRolesIndexSearchParams'))">前のページに戻る</x-office.button>
                @endif
            </div>
        </form>
    </x-office.card>
</x-office.layout>
