<x-office.layout title="出欠回答編集完了">
    <x-office.card title="出欠回答編集完了">
        <p class="text-sm break-words text-heading">出欠回答を編集しました。</p>

        <div class="mt-6 flex flex-wrap gap-2">
            @if (in_array('officeWeddingRsvpIndex*', Auth::user()->routes()))
                <x-office.button variant="primary" :href="route('officeWeddingRsvpIndex', session('officeWeddingRsvpIndexSearchParams'))">出欠回答一覧</x-office.button>
            @endif
            @if (in_array('officeWeddingRsvpShow*', Auth::user()->routes()))
                <x-office.button variant="primary" :href="route('officeWeddingRsvpShow', ['id' => $assign['id']])">出欠回答詳細</x-office.button>
            @endif
        </div>
    </x-office.card>
</x-office.layout>
