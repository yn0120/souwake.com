<x-office.guest-layout title="ログイン">
    <form method="POST" action="{{ route('officeSecretsLoginExecute', [], false) }}" enctype="multipart/form-data" class="space-y-5">
        @csrf

        <div>
            <x-office.form.label for="email">メールアドレス</x-office.form.label>
            <x-office.form.input type="email" name="email" id="email" :value="old('email')" autofocus />
            <x-office.form.error name="email" />
        </div>

        <div>
            <x-office.form.label for="password">パスワード</x-office.form.label>
            <x-office.form.password name="password" id="password" />
            <x-office.form.error name="password" />
        </div>

        <x-office.button variant="primary" type="submit" class="w-full">ログイン</x-office.button>
    </form>
</x-office.guest-layout>
