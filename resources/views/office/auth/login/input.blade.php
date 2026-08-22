<x-office.guest-layout title="ログイン">
    <form method="POST" action="{{ route('officeLoginExecute', [], false) }}" enctype="multipart/form-data" class="space-y-5">
        @csrf

        <div>
            <x-office.form.label for="email">メールアドレス</x-office.form.label>
            <x-office.form.input type="email" name="email" id="email" :value="old('email')"
                                 placeholder="email@souwake.com" autofocus />
            <x-office.form.error name="email" />
        </div>

        <div>
            <x-office.form.label for="password">パスワード</x-office.form.label>
            <x-office.form.password name="password" id="password" placeholder="············" />
            <x-office.form.error name="password" />
        </div>

        <div class="text-sm">
            <a href="{{ route('officeForgotPwInput', [], false) }}" class="text-brand hover:underline">
                パスワードを忘れたら
            </a>
        </div>

        <x-office.button variant="primary" type="submit" class="w-full">ワンタイムキーチェック</x-office.button>
    </form>
</x-office.guest-layout>
