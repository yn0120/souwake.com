<x-office.layout title="出欠回答詳細">
    <x-office.card title="出欠回答詳細">
        <x-slot:actions>
            @if (in_array('officeWeddingRsvpIndex*', Auth::user()->routes()))
                <x-office.button variant="outline-dark" :href="route('officeWeddingRsvpIndex', session('officeWeddingRsvpIndexSearchParams'))">戻る</x-office.button>
            @endif
            @if (in_array('officeWeddingRsvpEdit*', Auth::user()->routes()))
                <x-office.button variant="warning" :href="route('officeWeddingRsvpEditInput', ['id' => $assign['record']->id])">編集</x-office.button>
            @endif
        </x-slot:actions>

        @php $record = $assign['record']; @endphp

        <x-office.detail-row label="ID">{{ number_format($record->id) }}</x-office.detail-row>
        <x-office.detail-row label="出欠">{{ $record->attendanceLabel() }}</x-office.detail-row>
        <x-office.detail-row label="お名前">{{ $record->fullName() }}</x-office.detail-row>
        <x-office.detail-row label="ご住所の国">{{ $record->countryLabel() }}</x-office.detail-row>
        <x-office.detail-row label="ご住所">{{ $record->fullAddress() }}</x-office.detail-row>
        <x-office.detail-row label="電話番号">{{ $record->phone }}</x-office.detail-row>
        <x-office.detail-row label="メールアドレス">{{ $record->email }}</x-office.detail-row>

        @if ($record->attendance === App\Models\WeddingRsvpModel::ATTENDANCE_ATTENDING)
            <x-office.detail-row label="アレルギー・お食事のご要望">
                {!! nl2br(e($record->allergy ?: 'なし')) !!}
            </x-office.detail-row>
            <x-office.detail-row label="沖縄への到着日">
                {{ optional($record->arrival_date)->format('Y年m月d日') ?: '未定' }}
            </x-office.detail-row>
            <x-office.detail-row label="沖縄からの出発日">
                {{ optional($record->departure_date)->format('Y年m月d日') ?: '未定' }}
            </x-office.detail-row>
            <x-office.detail-row label="宿泊先ホテル名">{{ $record->hotel_name ?: '未定' }}</x-office.detail-row>
            <x-office.detail-row label="当日衣装のサイズ">{{ $record->costume_size ?: '選択なし' }}</x-office.detail-row>
        @endif

        <x-office.detail-row label="同伴者">
            {{ $record->companions->isEmpty() ? 'なし' : 'あり（'.number_format($record->companions->count()).'名）' }}
        </x-office.detail-row>

        @foreach ($record->companions as $companion)
            @php $companionLabel = "{$loop->iteration}人目の同伴者"; @endphp
            <x-office.detail-row :label="$companionLabel">
                お名前 : {{ $companion->fullName() }}<br>
                お食事 : {{ $companion->mealLabel() }}<br>
                お子様連れの場合の追加情報 : {!! nl2br(e($companion->child_info ?: 'なし')) !!}
            </x-office.detail-row>
        @endforeach

        {{-- 連名（wedding_rsvp_companions）へ移行する前の回答は旧カラムにしか同伴者が入っていないため、その場合のみ表示する --}}
        @if ($record->companions->isEmpty() && $record->companion_name)
            <x-office.detail-row label="同伴者（旧項目）">
                お名前 : {{ $record->companion_name }}{{ $record->companion_kana ? '（'.$record->companion_kana.'）' : '' }}<br>
                お食事 : {{ App\Models\WeddingRsvpCompanionModel::mealOptions()[$record->companion_meal] ?? '未選択' }}<br>
                お子様連れの場合の追加情報 : {!! nl2br(e($record->child_info ?: 'なし')) !!}
            </x-office.detail-row>
        @endif

        <x-office.detail-row label="新郎新婦へのメッセージ">
            {!! nl2br(e($record->message ?: 'なし')) !!}
        </x-office.detail-row>
        <x-office.detail-row label="楽曲リクエスト">{{ $record->song_request ?: 'なし' }}</x-office.detail-row>

        <x-office.detail-row label="お祝い画像">
            @if ($record->photos->isEmpty())
                なし
            @else
                <div class="flex flex-wrap gap-3">
                    @foreach ($record->photos as $photo)
                        @php
                            $photoUrl = route('officeWeddingRsvpPhotoShow', ['id' => $record->id, 'uuid' => $photo->uuid]);
                        @endphp
                        <div class="w-40 text-center">
                            @if (in_array($photo->status, ['ready', 'pending', 'processing'], true))
                                <a href="{{ $photoUrl }}" target="_blank" rel="noopener">
                                    <img src="{{ $photoUrl }}" alt="{{ $photo->original_name }}"
                                         class="max-h-40 max-w-full rounded-lg border border-default" />
                                </a>
                            @else
                                <span class="text-danger">変換に失敗した画像です。</span>
                            @endif
                            <div class="text-xs break-words text-body">{{ $photo->original_name }}</div>
                        </div>
                    @endforeach
                </div>
            @endif
        </x-office.detail-row>

        <x-office.detail-row label="回答日時">
            {{ optional($record->created_at)->format('Y年m月d日 H:i') }}
        </x-office.detail-row>

        @if ($record->updated_at)
            <x-office.detail-row label="更新日時">{{ $record->updated_at->format('Y年m月d日 H:i') }}</x-office.detail-row>
        @endif
    </x-office.card>
</x-office.layout>
