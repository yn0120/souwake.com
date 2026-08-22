<x-office.layout title="権限編集確認">
    <x-office.card title="権限編集確認">
        <form method="POST" action="{{ route('officeRoleEditExecute', ['id' => $assign['record']->id], false) }}" enctype="multipart/form-data">
            @csrf

            <x-office.detail-row label="権限名">{{ $assign['confirm']['name'] }}</x-office.detail-row>
            <x-office.detail-row label="備考">{!! nl2br(e($assign['confirm']['note'])) !!}</x-office.detail-row>

            <div class="mt-6 space-y-2">
                <x-office.button variant="success" type="submit" id="submit" class="w-full">編集する</x-office.button>
                <x-office.button variant="outline-dark" type="submit" name="back" value="1" class="w-full">前のページに戻る</x-office.button>
            </div>
        </form>
    </x-office.card>
</x-office.layout>
