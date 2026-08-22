<x-office.layout title="管理者登録">
    <x-office.card title="管理者登録">
        <form method="POST" action="{{ route('officeAdminCreateConfirm', [], false) }}" enctype="multipart/form-data">
            @csrf

            <x-office.form.row label="氏名" for="name" required name="name">
                <x-office.form.input name="name" id="name" :value="old('name')" />
            </x-office.form.row>

            <x-office.form.row label="権限" for="role_id" required name="role_id">
                <x-office.form.select name="role_id" id="role_id">
                    <option value="">未選択</option>
                    @foreach ($assign['roles'] as $key => $role)
                        <option value="{{ $key }}" @selected($key == old('role_id'))>{{ $role }}</option>
                    @endforeach
                </x-office.form.select>
            </x-office.form.row>

            <x-office.form.row label="メールアドレス" for="email" required name="email">
                <x-office.form.input name="email" id="email" :value="old('email')" />
            </x-office.form.row>

            <div class="mt-6 space-y-2">
                <x-office.button variant="success" type="submit" id="submit" class="w-full">確認する</x-office.button>
                @if (in_array('officeAdminIndex*', Auth::user()->routes()))
                    <x-office.button variant="outline-dark" class="w-full"
                                     :href="route('officeAdminIndex', session('officeAdminIndexSearchParams'))">前のページに戻る</x-office.button>
                @endif
            </div>
        </form>
    </x-office.card>
</x-office.layout>
