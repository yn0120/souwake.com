<x-office.layout title="出欠回答編集確認">
    <x-office.card title="出欠回答編集確認">
        @php $confirm = $assign['confirm']; @endphp

        <form method="POST" action="{{ route('officeWeddingRsvpEditExecute', ['id' => $assign['record']->id], false) }}" enctype="multipart/form-data">
            @csrf

            <x-office.detail-row label="出欠">{{ $confirm['attendance'] ?? null }}</x-office.detail-row>
            <x-office.detail-row label="お名前">
                {{ trim(($confirm['name_sei'] ?? '').' '.($confirm['name_mei'] ?? '')) }}
            </x-office.detail-row>
            <x-office.detail-row label="フリガナ">
                {{ trim(($confirm['kana_sei'] ?? '').' '.($confirm['kana_mei'] ?? '')) ?: 'なし' }}
            </x-office.detail-row>
            <x-office.detail-row label="ご住所の国">{{ $confirm['country'] ?? null }}</x-office.detail-row>
            <x-office.detail-row label="郵便番号 / ZIP Code">{{ $confirm['postal_code'] ?? null }}</x-office.detail-row>
            <x-office.detail-row label="都道府県 / 州">{{ $confirm['prefecture'] ?? null }}</x-office.detail-row>
            <x-office.detail-row label="市区町村 / City">{{ $confirm['city'] ?? null }}</x-office.detail-row>
            <x-office.detail-row label="番地 / Street Address">{{ $confirm['address'] ?? null }}</x-office.detail-row>
            <x-office.detail-row label="建物名 / Apt / Suite">{{ ($confirm['building'] ?? null) ?: 'なし' }}</x-office.detail-row>
            <x-office.detail-row label="電話番号">{{ $confirm['phone'] ?? null }}</x-office.detail-row>
            <x-office.detail-row label="メールアドレス">{{ $confirm['email'] ?? null }}</x-office.detail-row>
            <x-office.detail-row label="アレルギー・お食事のご要望">
                {!! nl2br(e(($confirm['allergy'] ?? null) ?: 'なし')) !!}
            </x-office.detail-row>
            <x-office.detail-row label="沖縄への到着日">{{ ($confirm['arrival_date'] ?? null) ?: '未定' }}</x-office.detail-row>
            <x-office.detail-row label="沖縄からの出発日">{{ ($confirm['departure_date'] ?? null) ?: '未定' }}</x-office.detail-row>
            <x-office.detail-row label="宿泊先ホテル名">{{ ($confirm['hotel_name'] ?? null) ?: '未定' }}</x-office.detail-row>
            <x-office.detail-row label="当日衣装のサイズ">{{ ($confirm['costume_size'] ?? null) ?: '選択なし' }}</x-office.detail-row>
            <x-office.detail-row label="同伴者の有無">{{ $confirm['companion_flag'] ?? null }}</x-office.detail-row>

            @foreach ($assign['confirmCompanions'] as $companion)
                @php $companionLabel = "{$loop->iteration}人目の同伴者"; @endphp
                <x-office.detail-row :label="$companionLabel">
                    お名前 : {{ $companion['name'] }}{{ $companion['kana'] ? '（'.$companion['kana'].'）' : '' }}<br>
                    お食事 : {{ $companion['meal'] }}<br>
                    お子様連れの場合の追加情報 : {!! nl2br(e($companion['child_info'] ?: 'なし')) !!}
                </x-office.detail-row>
            @endforeach

            <x-office.detail-row label="新郎新婦へのメッセージ">
                {!! nl2br(e(($confirm['message'] ?? null) ?: 'なし')) !!}
            </x-office.detail-row>
            <x-office.detail-row label="楽曲リクエスト">{{ ($confirm['song_request'] ?? null) ?: 'なし' }}</x-office.detail-row>

            <div class="mt-6 space-y-2">
                <x-office.button variant="success" type="submit" id="submit" class="w-full">編集する</x-office.button>
                <x-office.button variant="outline-dark" type="submit" name="back" value="1" class="w-full">前のページに戻る</x-office.button>
            </div>
        </form>
    </x-office.card>
</x-office.layout>
