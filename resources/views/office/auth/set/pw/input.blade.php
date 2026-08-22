<x-office.guest-layout title="パスワード設定">
    <p class="mb-6 text-sm text-body">新しいパスワードを入力し、「設定する」をクリックしてください。</p>

    <form method="POST" action="{{ route('officeSetPwExecute', [], false) }}" class="space-y-5">
        @csrf

        <div>
            <x-office.form.label for="password">パスワード</x-office.form.label>
            <x-office.form.password name="password" id="password" placeholder="············" />
            <x-office.form.error name="password" />
        </div>

        <div class="space-y-2">
            <x-office.button variant="primary" type="submit" class="w-full">設定する</x-office.button>
            <x-office.button variant="outline-dark" :href="route('officeLoginInput')" class="w-full">ログインページに戻る</x-office.button>
        </div>
    </form>
</x-office.guest-layout>
