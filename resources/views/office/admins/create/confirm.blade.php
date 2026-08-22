<x-office.layout title="管理者登録確認">
    <x-office.card title="管理者登録確認">
        <form method="POST" action="{{ route('officeAdminCreateExecute', [], false) }}" enctype="multipart/form-data">
            @csrf

            <x-office.detail-row label="氏名">{{ $assign['confirm']['name'] }}</x-office.detail-row>
            <x-office.detail-row label="権限">{{ $assign['confirm']['role_id'] }}</x-office.detail-row>
            <x-office.detail-row label="メールアドレス">{{ $assign['confirm']['email'] }}</x-office.detail-row>

            <div class="mt-6 space-y-2">
                <x-office.button variant="success" type="submit" id="submit" class="w-full">登録する</x-office.button>
                <x-office.button variant="outline-dark" type="submit" name="back" value="1" class="w-full">前のページに戻る</x-office.button>
            </div>
        </form>
    </x-office.card>
</x-office.layout>
