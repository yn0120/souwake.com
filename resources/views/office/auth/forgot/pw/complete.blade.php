<x-office.guest-layout title="パスワードを忘れたら">
    <p class="mb-6 text-sm text-body">
        パスワード設定用のメールを送信しました。<br>
        パスワードの設定を行ってください。
    </p>

    <x-office.button variant="primary" :href="route('officeLoginInput', [], false)" class="w-full">ログイン</x-office.button>
</x-office.guest-layout>
