<x-office.plain-layout title="エラー" max-width="max-w-xl">
    <div class="py-6 text-center">
        <h1 class="text-4xl font-bold text-heading">{{ $assign['code'] }}</h1>
        <p class="mt-3 text-sm break-words text-body">{{ $assign['msg'] }}</p>

        <div class="mt-6">
            <x-office.button variant="outline-dark" href="javascript:history.back();">戻る</x-office.button>
        </div>
    </div>
</x-office.plain-layout>
