<x-office.guest-layout title="パスワード確認">
    <form method="POST" action="{{ route('officeSecretsPasswordVerify', [], false) }}" enctype="multipart/form-data" class="space-y-5">
        @csrf

        <div>
            <x-office.form.label for="password">パスワード</x-office.form.label>
            <x-office.form.password name="password" id="password" autofocus />
            <x-office.form.error name="password" />
        </div>

        <x-office.button variant="primary" type="submit" class="w-full">確認</x-office.button>
    </form>
</x-office.guest-layout>
