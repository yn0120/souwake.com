@extends('office/parts/app')

@section('meta')
    <title>出欠回答詳細 | {{ config('app.name') }}</title>
@endsection

@push('css')
    <style>
        .rsvp-photo { max-width: 160px; max-height: 160px; }
    </style>
@endpush

@section('content')

    <div class="container-fluid flex-grow-1 container-p-y">
        <!-- Layout wrapper -->
        <div class="layout-wrapper layout-content-navbar">
            <div class="layout-container">
                <!-- Menu -->
                @include ('office/parts/side')
                <!-- / Menu -->
                <!-- Layout container -->
                <div class="layout-page">
                    <!-- Content wrapper -->
                    <div class="content-wrapper">
                        <!-- Content -->
                        <div class="container-fluid flex-grow-1 container-p-y">
                            {{-- エラー/サクセス メッセージ --}}
                            @include ('office/parts/item/alert')
                            <div class="card p-5">
                                <div class="col-12 pb-2 text-end">
                                    @if (in_array('officeWeddingRsvpIndex*', Auth::user()->routes()))
                                        <a href="{{ route('officeWeddingRsvpIndex', session('officeWeddingRsvpIndexSearchParams')) }}" class="btn btn-outline-dark">戻る</a>
                                    @endif
                                    @if (in_array('officeWeddingRsvpEdit*', Auth::user()->routes()))
                                        <a href="{{ route('officeWeddingRsvpEditInput', ['id' => $assign['record']->id]) }}" class="btn btn-warning">編集</a>
                                    @endif
                                </div>
                                <div class="row">
                                    <div class="col-12 pt-2">
                                        <h5 class="card-title">出欠回答詳細</h5>
                                    </div>
                                </div>

                                <div class="row">
                                    <label class="col-md-3 col-form-label d-flex align-items-center pt-2 pb-0 py-md-2 fs-6 fw-bold">
                                        ID
                                    </label>
                                    <div class="col-md-8 form-text d-flex align-items-center pt-0 pb-2 py-md-2 fs-6">
                                        {{ number_format($assign['record']->id) }}
                                    </div>
                                </div>

                                <div class="row">
                                    <label class="col-md-3 col-form-label d-flex align-items-center pt-2 pb-0 py-md-2 fs-6 fw-bold">
                                        出欠
                                    </label>
                                    <div class="col-md-8 form-text d-flex align-items-center pt-0 pb-2 py-md-2 fs-6">
                                        {{ $assign['record']->attendanceLabel() }}
                                    </div>
                                </div>

                                <div class="row">
                                    <label class="col-md-3 col-form-label d-flex align-items-center pt-2 pb-0 py-md-2 fs-6 fw-bold">
                                        お名前
                                    </label>
                                    <div class="col-md-8 form-text d-flex align-items-center pt-0 pb-2 py-md-2 fs-6">
                                        {{ $assign['record']->fullName() }}
                                    </div>
                                </div>

                                <div class="row">
                                    <label class="col-md-3 col-form-label d-flex align-items-center pt-2 pb-0 py-md-2 fs-6 fw-bold">
                                        ご住所の国
                                    </label>
                                    <div class="col-md-8 form-text d-flex align-items-center pt-0 pb-2 py-md-2 fs-6">
                                        {{ $assign['record']->countryLabel() }}
                                    </div>
                                </div>

                                <div class="row">
                                    <label class="col-md-3 col-form-label d-flex align-items-center pt-2 pb-0 py-md-2 fs-6 fw-bold">
                                        ご住所
                                    </label>
                                    <div class="col-md-8 form-text d-flex align-items-center pt-0 pb-2 py-md-2 fs-6 text-break">
                                        {{ $assign['record']->fullAddress() }}
                                    </div>
                                </div>

                                <div class="row">
                                    <label class="col-md-3 col-form-label d-flex align-items-center pt-2 pb-0 py-md-2 fs-6 fw-bold">
                                        電話番号
                                    </label>
                                    <div class="col-md-8 form-text d-flex align-items-center pt-0 pb-2 py-md-2 fs-6">
                                        {{ $assign['record']->phone }}
                                    </div>
                                </div>

                                <div class="row">
                                    <label class="col-md-3 col-form-label d-flex align-items-center pt-2 pb-0 py-md-2 fs-6 fw-bold">
                                        メールアドレス
                                    </label>
                                    <div class="col-md-8 form-text d-flex align-items-center pt-0 pb-2 py-md-2 fs-6 text-break">
                                        {{ $assign['record']->email }}
                                    </div>
                                </div>

                                @if ($assign['record']->attendance === App\Models\WeddingRsvpModel::ATTENDANCE_ATTENDING)
                                    <div class="row">
                                        <label class="col-md-3 col-form-label d-flex align-items-center pt-2 pb-0 py-md-2 fs-6 fw-bold">
                                            アレルギー・お食事のご要望
                                        </label>
                                        <div class="col-md-8 form-text pt-0 pb-2 py-md-2 fs-6 text-break">
                                            {!! nl2br(e($assign['record']->allergy ?: 'なし')) !!}
                                        </div>
                                    </div>

                                    <div class="row">
                                        <label class="col-md-3 col-form-label d-flex align-items-center pt-2 pb-0 py-md-2 fs-6 fw-bold">
                                            沖縄への到着日
                                        </label>
                                        <div class="col-md-8 form-text d-flex align-items-center pt-0 pb-2 py-md-2 fs-6">
                                            {{ optional($assign['record']->arrival_date)->format('Y年m月d日') ?: '未定' }}
                                        </div>
                                    </div>

                                    <div class="row">
                                        <label class="col-md-3 col-form-label d-flex align-items-center pt-2 pb-0 py-md-2 fs-6 fw-bold">
                                            沖縄からの出発日
                                        </label>
                                        <div class="col-md-8 form-text d-flex align-items-center pt-0 pb-2 py-md-2 fs-6">
                                            {{ optional($assign['record']->departure_date)->format('Y年m月d日') ?: '未定' }}
                                        </div>
                                    </div>

                                    <div class="row">
                                        <label class="col-md-3 col-form-label d-flex align-items-center pt-2 pb-0 py-md-2 fs-6 fw-bold">
                                            宿泊先ホテル名
                                        </label>
                                        <div class="col-md-8 form-text d-flex align-items-center pt-0 pb-2 py-md-2 fs-6 text-break">
                                            {{ $assign['record']->hotel_name ?: '未定' }}
                                        </div>
                                    </div>

                                    <div class="row">
                                        <label class="col-md-3 col-form-label d-flex align-items-center pt-2 pb-0 py-md-2 fs-6 fw-bold">
                                            当日衣装のサイズ
                                        </label>
                                        <div class="col-md-8 form-text d-flex align-items-center pt-0 pb-2 py-md-2 fs-6">
                                            {{ $assign['record']->costume_size ?: '選択なし' }}
                                        </div>
                                    </div>
                                @endif

                                <div class="row">
                                    <label class="col-md-3 col-form-label d-flex align-items-center pt-2 pb-0 py-md-2 fs-6 fw-bold">
                                        同伴者
                                    </label>
                                    <div class="col-md-8 form-text d-flex align-items-center pt-0 pb-2 py-md-2 fs-6">
                                        {{ $assign['record']->companions->isEmpty() ? 'なし' : 'あり（'.number_format($assign['record']->companions->count()).'名）' }}
                                    </div>
                                </div>

                                @foreach ($assign['record']->companions as $companion)
                                    <div class="row">
                                        <label class="col-md-3 col-form-label d-flex align-items-center pt-2 pb-0 py-md-2 fs-6 fw-bold">
                                            {{ $loop->iteration }}人目の同伴者
                                        </label>
                                        <div class="col-md-8 form-text pt-0 pb-2 py-md-2 fs-6 text-break">
                                            お名前 : {{ $companion->fullName() }}<br>
                                            お食事 : {{ $companion->mealLabel() }}<br>
                                            お子様連れの場合の追加情報 : {!! nl2br(e($companion->child_info ?: 'なし')) !!}
                                        </div>
                                    </div>
                                @endforeach

                                {{-- 連名（wedding_rsvp_companions）へ移行する前の回答は旧カラムにしか同伴者が入っていないため、その場合のみ表示する --}}
                                @if ($assign['record']->companions->isEmpty() && $assign['record']->companion_name)
                                    <div class="row">
                                        <label class="col-md-3 col-form-label d-flex align-items-center pt-2 pb-0 py-md-2 fs-6 fw-bold">
                                            同伴者（旧項目）
                                        </label>
                                        <div class="col-md-8 form-text pt-0 pb-2 py-md-2 fs-6 text-break">
                                            お名前 : {{ $assign['record']->companion_name }}{{ $assign['record']->companion_kana ? '（'.$assign['record']->companion_kana.'）' : '' }}<br>
                                            お食事 : {{ App\Models\WeddingRsvpCompanionModel::mealOptions()[$assign['record']->companion_meal] ?? '未選択' }}<br>
                                            お子様連れの場合の追加情報 : {!! nl2br(e($assign['record']->child_info ?: 'なし')) !!}
                                        </div>
                                    </div>
                                @endif

                                <div class="row">
                                    <label class="col-md-3 col-form-label d-flex align-items-center pt-2 pb-0 py-md-2 fs-6 fw-bold">
                                        新郎新婦へのメッセージ
                                    </label>
                                    <div class="col-md-8 form-text pt-0 pb-2 py-md-2 fs-6 text-break">
                                        {!! nl2br(e($assign['record']->message ?: 'なし')) !!}
                                    </div>
                                </div>

                                <div class="row">
                                    <label class="col-md-3 col-form-label d-flex align-items-center pt-2 pb-0 py-md-2 fs-6 fw-bold">
                                        楽曲リクエスト
                                    </label>
                                    <div class="col-md-8 form-text d-flex align-items-center pt-0 pb-2 py-md-2 fs-6 text-break">
                                        {{ $assign['record']->song_request ?: 'なし' }}
                                    </div>
                                </div>

                                <div class="row">
                                    <label class="col-md-3 col-form-label d-flex align-items-center pt-2 pb-0 py-md-2 fs-6 fw-bold">
                                        お祝い画像
                                    </label>
                                    <div class="col-md-8 form-text pt-0 pb-2 py-md-2 fs-6">
                                        @if ($assign['record']->photos->isEmpty())
                                            なし
                                        @else
                                            <div class="d-flex flex-wrap gap-3">
                                                @foreach ($assign['record']->photos as $photo)
                                                    @php
                                                        $photoUrl = route('officeWeddingRsvpPhotoShow', ['id' => $assign['record']->id, 'uuid' => $photo->uuid]);
                                                    @endphp
                                                    <div class="text-center">
                                                        @if ($photo->status === 'ready' || $photo->status === 'pending' || $photo->status === 'processing')
                                                            <a href="{{ $photoUrl }}" target="_blank" rel="noopener">
                                                                <img src="{{ $photoUrl }}" alt="{{ $photo->original_name }}" class="rsvp-photo rounded border">
                                                            </a>
                                                        @else
                                                            <span class="text-danger">変換に失敗した画像です。</span>
                                                        @endif
                                                        <div class="fs-small text-break" style="max-width: 160px;">{{ $photo->original_name }}</div>
                                                    </div>
                                                @endforeach
                                            </div>
                                        @endif
                                    </div>
                                </div>

                                <div class="row">
                                    <label class="col-md-3 col-form-label d-flex align-items-center pt-2 pb-0 py-md-2 fs-6 fw-bold">
                                        回答日時
                                    </label>
                                    <div class="col-md-8 form-text d-flex align-items-center pt-0 pb-2 py-md-2 fs-6">
                                        {{ optional($assign['record']->created_at)->format('Y年m月d日 H:i') }}
                                    </div>
                                </div>

                                @if ($assign['record']->updated_at)
                                    <div class="row">
                                        <label class="col-md-3 col-form-label d-flex align-items-center pt-2 pb-0 py-md-2 fs-6 fw-bold">
                                            更新日時
                                        </label>
                                        <div class="col-md-8 form-text d-flex align-items-center pt-0 pb-2 py-md-2 fs-6">
                                            {{ $assign['record']->updated_at->format('Y年m月d日 H:i') }}
                                        </div>
                                    </div>
                                @endif
                            </div>
                        </div>
                        <!-- / Content -->
                    </div>
                    <!-- Content wrapper -->
                </div>
                <!-- / Layout page -->
            </div>
            <!-- Overlay -->
            <div class="layout-overlay layout-menu-toggle"></div>
            <!-- Drag Target Area To SlideIn Menu On Small Screens -->
            <div class="drag-target" style="touch-action: pan-y; user-select: none; -webkit-user-drag: none; -webkit-tap-highlight-color: rgba(0, 0, 0, 0);" ></div>
        </div>
        <!-- / Layout wrapper -->
        <!-- Page JS -->
        @push ('js')

        @endpush
    </div>

@endsection
