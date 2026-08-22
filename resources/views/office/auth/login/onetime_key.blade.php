<x-office.guest-layout title="ワンタイムキー入力">
    <p class="mb-6 text-sm text-body">
        ワンタイムキーを送信しました。下記にご入力いただきログインしてください。<br>
        ワンタイムキーの有効期間は10分間です。
    </p>

    <form method="POST" action="{{ route('officeOnetimeExecute', [], false) }}" class="space-y-5">
        @csrf

        <div>
            <x-office.form.label for="onetime_key">ワンタイムキー</x-office.form.label>
            <x-office.form.input name="onetime_key" id="onetime_key" :value="old('onetime_key')"
                                 placeholder="1234567890" autocomplete="off" autofocus />
            <x-office.form.error name="onetime_key" />
        </div>

        <div class="space-y-2">
            <x-office.button variant="primary" type="submit" class="w-full">ログイン</x-office.button>
            <x-office.button variant="outline-dark" :href="route('officeLoginInput')" class="w-full">ログインページに戻る</x-office.button>
        </div>
    </form>
</x-office.guest-layout>
