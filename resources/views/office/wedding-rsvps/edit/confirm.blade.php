@extends('office/parts/app')

@section('meta')
    <title>出欠回答編集確認 | {{ config('app.name') }}</title>
@endsection

@push('css')

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
                                <div class="row">
                                    <div class="col-12 pt-2">
                                        <h5 class="card-title">出欠回答編集確認</h5>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-12">
                                        <form method="POST" action="{{ route('officeWeddingRsvpEditExecute', ['id' => $assign['record']->id], false) }}" class="form" enctype="multipart/form-data">
                                            @csrf
                                            <div class="row">
                                                <label class="col-md-3 col-form-label d-flex align-items-center pt-2 pb-0 py-md-2 fs-6 fw-bold">
                                                    出欠
                                                </label>
                                                <div class="col-md-8 form-text d-flex align-items-center pt-0 pb-2 py-md-2 fs-6">
                                                    {{ $assign['confirm']['attendance'] ?? null }}
                                                </div>
                                            </div>

                                            <div class="row">
                                                <label class="col-md-3 col-form-label d-flex align-items-center pt-2 pb-0 py-md-2 fs-6 fw-bold">
                                                    お名前
                                                </label>
                                                <div class="col-md-8 form-text d-flex align-items-center pt-0 pb-2 py-md-2 fs-6">
                                                    {{ trim(($assign['confirm']['name_sei'] ?? '').' '.($assign['confirm']['name_mei'] ?? '')) }}
                                                </div>
                                            </div>

                                            <div class="row">
                                                <label class="col-md-3 col-form-label d-flex align-items-center pt-2 pb-0 py-md-2 fs-6 fw-bold">
                                                    フリガナ
                                                </label>
                                                <div class="col-md-8 form-text d-flex align-items-center pt-0 pb-2 py-md-2 fs-6">
                                                    {{ trim(($assign['confirm']['kana_sei'] ?? '').' '.($assign['confirm']['kana_mei'] ?? '')) ?: 'なし' }}
                                                </div>
                                            </div>

                                            <div class="row">
                                                <label class="col-md-3 col-form-label d-flex align-items-center pt-2 pb-0 py-md-2 fs-6 fw-bold">
                                                    ご住所の国
                                                </label>
                                                <div class="col-md-8 form-text d-flex align-items-center pt-0 pb-2 py-md-2 fs-6">
                                                    {{ $assign['confirm']['country'] ?? null }}
                                                </div>
                                            </div>

                                            <div class="row">
                                                <label class="col-md-3 col-form-label d-flex align-items-center pt-2 pb-0 py-md-2 fs-6 fw-bold">
                                                    郵便番号 / ZIP Code
                                                </label>
                                                <div class="col-md-8 form-text d-flex align-items-center pt-0 pb-2 py-md-2 fs-6">
                                                    {{ $assign['confirm']['postal_code'] ?? null }}
                                                </div>
                                            </div>

                                            <div class="row">
                                                <label class="col-md-3 col-form-label d-flex align-items-center pt-2 pb-0 py-md-2 fs-6 fw-bold">
                                                    都道府県 / 州
                                                </label>
                                                <div class="col-md-8 form-text d-flex align-items-center pt-0 pb-2 py-md-2 fs-6">
                                                    {{ $assign['confirm']['prefecture'] ?? null }}
                                                </div>
                                            </div>

                                            <div class="row">
                                                <label class="col-md-3 col-form-label d-flex align-items-center pt-2 pb-0 py-md-2 fs-6 fw-bold">
                                                    市区町村 / City
                                                </label>
                                                <div class="col-md-8 form-text d-flex align-items-center pt-0 pb-2 py-md-2 fs-6">
                                                    {{ $assign['confirm']['city'] ?? null }}
                                                </div>
                                            </div>

                                            <div class="row">
                                                <label class="col-md-3 col-form-label d-flex align-items-center pt-2 pb-0 py-md-2 fs-6 fw-bold">
                                                    番地 / Street Address
                                                </label>
                                                <div class="col-md-8 form-text d-flex align-items-center pt-0 pb-2 py-md-2 fs-6">
                                                    {{ $assign['confirm']['address'] ?? null }}
                                                </div>
                                            </div>

                                            <div class="row">
                                                <label class="col-md-3 col-form-label d-flex align-items-center pt-2 pb-0 py-md-2 fs-6 fw-bold">
                                                    建物名 / Apt / Suite
                                                </label>
                                                <div class="col-md-8 form-text d-flex align-items-center pt-0 pb-2 py-md-2 fs-6">
                                                    {{ ($assign['confirm']['building'] ?? null) ?: 'なし' }}
                                                </div>
                                            </div>

                                            <div class="row">
                                                <label class="col-md-3 col-form-label d-flex align-items-center pt-2 pb-0 py-md-2 fs-6 fw-bold">
                                                    電話番号
                                                </label>
                                                <div class="col-md-8 form-text d-flex align-items-center pt-0 pb-2 py-md-2 fs-6">
                                                    {{ $assign['confirm']['phone'] ?? null }}
                                                </div>
                                            </div>

                                            <div class="row">
                                                <label class="col-md-3 col-form-label d-flex align-items-center pt-2 pb-0 py-md-2 fs-6 fw-bold">
                                                    メールアドレス
                                                </label>
                                                <div class="col-md-8 form-text d-flex align-items-center pt-0 pb-2 py-md-2 fs-6 text-break">
                                                    {{ $assign['confirm']['email'] ?? null }}
                                                </div>
                                            </div>

                                            <div class="row">
                                                <label class="col-md-3 col-form-label d-flex align-items-center pt-2 pb-0 py-md-2 fs-6 fw-bold">
                                                    アレルギー・お食事のご要望
                                                </label>
                                                <div class="col-md-8 form-text pt-0 pb-2 py-md-2 fs-6 text-break">
                                                    {!! nl2br(e(($assign['confirm']['allergy'] ?? null) ?: 'なし')) !!}
                                                </div>
                                            </div>

                                            <div class="row">
                                                <label class="col-md-3 col-form-label d-flex align-items-center pt-2 pb-0 py-md-2 fs-6 fw-bold">
                                                    沖縄への到着日
                                                </label>
                                                <div class="col-md-8 form-text d-flex align-items-center pt-0 pb-2 py-md-2 fs-6">
                                                    {{ ($assign['confirm']['arrival_date'] ?? null) ?: '未定' }}
                                                </div>
                                            </div>

                                            <div class="row">
                                                <label class="col-md-3 col-form-label d-flex align-items-center pt-2 pb-0 py-md-2 fs-6 fw-bold">
                                                    沖縄からの出発日
                                                </label>
                                                <div class="col-md-8 form-text d-flex align-items-center pt-0 pb-2 py-md-2 fs-6">
                                                    {{ ($assign['confirm']['departure_date'] ?? null) ?: '未定' }}
                                                </div>
                                            </div>

                                            <div class="row">
                                                <label class="col-md-3 col-form-label d-flex align-items-center pt-2 pb-0 py-md-2 fs-6 fw-bold">
                                                    宿泊先ホテル名
                                                </label>
                                                <div class="col-md-8 form-text d-flex align-items-center pt-0 pb-2 py-md-2 fs-6 text-break">
                                                    {{ ($assign['confirm']['hotel_name'] ?? null) ?: '未定' }}
                                                </div>
                                            </div>

                                            <div class="row">
                                                <label class="col-md-3 col-form-label d-flex align-items-center pt-2 pb-0 py-md-2 fs-6 fw-bold">
                                                    当日衣装のサイズ
                                                </label>
                                                <div class="col-md-8 form-text d-flex align-items-center pt-0 pb-2 py-md-2 fs-6">
                                                    {{ ($assign['confirm']['costume_size'] ?? null) ?: '選択なし' }}
                                                </div>
                                            </div>

                                            <div class="row">
                                                <label class="col-md-3 col-form-label d-flex align-items-center pt-2 pb-0 py-md-2 fs-6 fw-bold">
                                                    同伴者の有無
                                                </label>
                                                <div class="col-md-8 form-text d-flex align-items-center pt-0 pb-2 py-md-2 fs-6">
                                                    {{ $assign['confirm']['companion_flag'] ?? null }}
                                                </div>
                                            </div>

                                            @foreach ($assign['confirmCompanions'] as $companion)
                                                <div class="row">
                                                    <label class="col-md-3 col-form-label d-flex align-items-center pt-2 pb-0 py-md-2 fs-6 fw-bold">
                                                        {{ $loop->iteration }}人目の同伴者
                                                    </label>
                                                    <div class="col-md-8 form-text pt-0 pb-2 py-md-2 fs-6 text-break">
                                                        お名前 : {{ $companion['name'] }}{{ $companion['kana'] ? '（'.$companion['kana'].'）' : '' }}<br>
                                                        お食事 : {{ $companion['meal'] }}<br>
                                                        お子様連れの場合の追加情報 : {!! nl2br(e($companion['child_info'] ?: 'なし')) !!}
                                                    </div>
                                                </div>
                                            @endforeach

                                            <div class="row">
                                                <label class="col-md-3 col-form-label d-flex align-items-center pt-2 pb-0 py-md-2 fs-6 fw-bold">
                                                    新郎新婦へのメッセージ
                                                </label>
                                                <div class="col-md-8 form-text pt-0 pb-2 py-md-2 fs-6 text-break">
                                                    {!! nl2br(e(($assign['confirm']['message'] ?? null) ?: 'なし')) !!}
                                                </div>
                                            </div>

                                            <div class="row">
                                                <label class="col-md-3 col-form-label d-flex align-items-center pt-2 pb-0 py-md-2 fs-6 fw-bold">
                                                    楽曲リクエスト
                                                </label>
                                                <div class="col-md-8 form-text d-flex align-items-center pt-0 pb-2 py-md-2 fs-6 text-break">
                                                    {{ ($assign['confirm']['song_request'] ?? null) ?: 'なし' }}
                                                </div>
                                            </div>

                                            {{-- 進むボタン --}}
                                            <div class="my-3">
                                                <button type="submit" class="btn btn-success d-grid w-100 text-white text-break" id="submit">編集する</button>
                                            </div>

                                            {{-- 戻るボタン --}}
                                            <div class="my-3">
                                                <button type="submit" name="back" value="1" class="text-break btn btn-outline-dark col-12 mb-0">前のページに戻る</button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
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
