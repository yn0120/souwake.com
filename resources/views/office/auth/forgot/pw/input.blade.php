<x-office.guest-layout title="パスワードを忘れたら">
    <p class="mb-6 text-sm text-body">
        登録されたメールアドレスを入力してください。<br>
        パスワード設定のご案内メールをお送りします。
    </p>

    <form method="POST" action="{{ route('officeForgotPwExecute', [], false) }}" class="space-y-5">
        @csrf

        <div>
            <x-office.form.label for="email">メールアドレス</x-office.form.label>
            <x-office.form.input name="email" id="email" :value="old('email')" placeholder="email@souwake.com" />
            <x-office.form.error name="email" />
        </div>

        <div class="space-y-2">
            <x-office.button variant="primary" type="submit" class="w-full">送信する</x-office.button>
            <x-office.button variant="outline-dark" :href="route('officeLoginInput')" class="w-full">ログインページに戻る</x-office.button>
        </div>
    </form>
</x-office.guest-layout>
