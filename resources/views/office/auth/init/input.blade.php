<x-office.guest-layout title="管理者初期IDPW編集">
    <p class="mb-6 text-sm text-body">管理者情報を入力し、「設定する」をクリックしてください。</p>

    <form method="POST" action="{{ route('officeInitExecute', [], false) }}" class="space-y-5">
        @csrf

        <div>
            <x-office.form.label for="name">氏名</x-office.form.label>
            <x-office.form.input name="name" id="name" :value="old('name')" placeholder="そうすけ" autofocus />
            <x-office.form.error name="name" />
        </div>

        <div>
            <x-office.form.label for="email">メールアドレス</x-office.form.label>
            <x-office.form.input name="email" id="email" :value="old('email')" placeholder="email@souwake.com" />
            <x-office.form.error name="email" />
        </div>

        <div>
            <x-office.form.label for="password">パスワード</x-office.form.label>
            <x-office.form.password name="password" id="password" placeholder="············" />
            <x-office.form.error name="password" />
        </div>

        <x-office.button variant="primary" type="submit" class="w-full">設定する</x-office.button>
    </form>
</x-office.guest-layout>
