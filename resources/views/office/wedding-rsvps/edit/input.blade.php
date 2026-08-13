@extends('office/parts/app')

@section('meta')
    <title>出欠回答編集 | {{ config('app.name') }}</title>
@endsection

@push('css')

@endpush

@section('content')

    @php
        $record = $assign['record'];
        $country = old('country', $record->country);
        $isUnitedStates = $country === App\Models\WeddingRsvpModel::COUNTRY_US;
        $companionFlag = (string) old('companion_flag', $record->companion_flag ? '1' : '0');
        $companions = $assign['companions'] ?: [];
    @endphp

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
                                        <h5 class="card-title">出欠回答編集</h5>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-12">
                                        <form method="POST" action="{{ route('officeWeddingRsvpEditConfirm', ['id' => $record->id], false) }}" class="form" enctype="multipart/form-data">
                                            @csrf
                                            <div class="row">
                                                <label class="col-md-3 col-form-label d-flex align-items-center pt-2 pb-0 py-md-2 fs-6 fw-bold" for="attendance" role="button">
                                                    <span class="text-danger">※&nbsp;</span> 出欠
                                                </label>
                                                <div class="col-md-8 form-text d-flex align-items-center pt-0 pb-2 py-md-2 fs-6">
                                                    <select name="attendance" id="attendance" class="form-control">
                                                        <option value="">未選択</option>
                                                        @foreach ($assign['attendances'] as $key => $label)
                                                            <option value="{{ $key }}" @selected($key === old('attendance', $record->attendance))>{{ $label }}</option>
                                                        @endforeach
                                                    </select>
                                                </div>
                                                @error ('attendance')
                                                    <div class="col-md-3"></div>
                                                    <div class="col-md-8">
                                                        <div class="alert alert-danger mt-0 p-1 form-text" role="alert">{{ $message }}</div>
                                                    </div>
                                                @enderror
                                                <div class="col-md-3"></div>
                                                <div class="col-md-8">
                                                    <p class="fs-small text-break">ご欠席に変更した場合、当日のご予定（アレルギー・到着日・出発日・宿泊先・衣装サイズ）は保存されません。</p>
                                                </div>
                                            </div>

                                            <div class="row">
                                                <label class="col-md-3 col-form-label d-flex align-items-center pt-2 pb-0 py-md-2 fs-6 fw-bold" for="name_sei" role="button">
                                                    <span class="text-danger">※&nbsp;</span> お名前（姓・名）
                                                </label>
                                                <div class="col-md-8 form-text d-flex align-items-center pt-0 pb-2 py-md-2 fs-6 gap-2">
                                                    <input type="text" name="name_sei" value="{{ old('name_sei', $record->name_sei) }}" id="name_sei" class="form-control">
                                                    <input type="text" name="name_mei" value="{{ old('name_mei', $record->name_mei) }}" id="name_mei" class="form-control">
                                                </div>
                                                @error ('name_sei')
                                                    <div class="col-md-3"></div>
                                                    <div class="col-md-8">
                                                        <div class="alert alert-danger mt-0 p-1 form-text" role="alert">{{ $message }}</div>
                                                    </div>
                                                @enderror
                                                @error ('name_mei')
                                                    <div class="col-md-3"></div>
                                                    <div class="col-md-8">
                                                        <div class="alert alert-danger mt-0 p-1 form-text" role="alert">{{ $message }}</div>
                                                    </div>
                                                @enderror
                                            </div>

                                            <div class="row">
                                                <label class="col-md-3 col-form-label d-flex align-items-center pt-2 pb-0 py-md-2 fs-6 fw-bold" for="kana_sei" role="button">
                                                    @unless ($isUnitedStates)
                                                        <span class="text-danger">※&nbsp;</span>
                                                    @endunless
                                                    フリガナ（姓・名）
                                                </label>
                                                <div class="col-md-8 form-text d-flex align-items-center pt-0 pb-2 py-md-2 fs-6 gap-2">
                                                    <input type="text" name="kana_sei" value="{{ old('kana_sei', $record->kana_sei) }}" id="kana_sei" class="form-control">
                                                    <input type="text" name="kana_mei" value="{{ old('kana_mei', $record->kana_mei) }}" id="kana_mei" class="form-control">
                                                </div>
                                                @error ('kana_sei')
                                                    <div class="col-md-3"></div>
                                                    <div class="col-md-8">
                                                        <div class="alert alert-danger mt-0 p-1 form-text" role="alert">{{ $message }}</div>
                                                    </div>
                                                @enderror
                                                @error ('kana_mei')
                                                    <div class="col-md-3"></div>
                                                    <div class="col-md-8">
                                                        <div class="alert alert-danger mt-0 p-1 form-text" role="alert">{{ $message }}</div>
                                                    </div>
                                                @enderror
                                                <div class="col-md-3"></div>
                                                <div class="col-md-8">
                                                    <p class="fs-small text-break">アメリカ在住の方（ご住所の国＝アメリカ）は未入力でも保存できます。</p>
                                                </div>
                                            </div>

                                            <div class="row">
                                                <label class="col-md-3 col-form-label d-flex align-items-center pt-2 pb-0 py-md-2 fs-6 fw-bold" for="country" role="button">
                                                    <span class="text-danger">※&nbsp;</span> ご住所の国
                                                </label>
                                                <div class="col-md-8 form-text d-flex align-items-center pt-0 pb-2 py-md-2 fs-6">
                                                    <select name="country" id="country" class="form-control">
                                                        @foreach ($assign['countries'] as $key => $label)
                                                            <option value="{{ $key }}" @selected($key === $country)>{{ $label }}</option>
                                                        @endforeach
                                                    </select>
                                                </div>
                                                @error ('country')
                                                    <div class="col-md-3"></div>
                                                    <div class="col-md-8">
                                                        <div class="alert alert-danger mt-0 p-1 form-text" role="alert">{{ $message }}</div>
                                                    </div>
                                                @enderror
                                            </div>

                                            <div class="row">
                                                <label class="col-md-3 col-form-label d-flex align-items-center pt-2 pb-0 py-md-2 fs-6 fw-bold" for="postal_code" role="button">
                                                    <span class="text-danger">※&nbsp;</span> 郵便番号 / ZIP Code
                                                </label>
                                                <div class="col-md-8 form-text d-flex align-items-center pt-0 pb-2 py-md-2 fs-6">
                                                    <input type="text" name="postal_code" value="{{ old('postal_code', $record->postal_code) }}" id="postal_code" class="form-control castHalfWidthDigit trimSpace">
                                                </div>
                                                @error ('postal_code')
                                                    <div class="col-md-3"></div>
                                                    <div class="col-md-8">
                                                        <div class="alert alert-danger mt-0 p-1 form-text" role="alert">{{ $message }}</div>
                                                    </div>
                                                @enderror
                                                <div class="col-md-3"></div>
                                                <div class="col-md-8">
                                                    <p class="fs-small text-break">日本はハイフンなしの数字7桁、アメリカは5桁（またはZIP+4の9桁）でご入力ください。</p>
                                                </div>
                                            </div>

                                            {{-- 日本の都道府県とアメリカの州は同じprefectureカラムに保存するため、国に応じて入力欄を切り替える --}}
                                            <div class="row" data-country="{{ App\Models\WeddingRsvpModel::COUNTRY_JP }}" @style(['display: none' => $isUnitedStates])>
                                                <label class="col-md-3 col-form-label d-flex align-items-center pt-2 pb-0 py-md-2 fs-6 fw-bold" for="prefecture" role="button">
                                                    <span class="text-danger">※&nbsp;</span> 都道府県
                                                </label>
                                                <div class="col-md-8 form-text d-flex align-items-center pt-0 pb-2 py-md-2 fs-6">
                                                    <input type="text" name="prefecture" value="{{ $isUnitedStates ? '' : old('prefecture', $record->prefecture) }}" id="prefecture" class="form-control" @disabled($isUnitedStates)>
                                                </div>
                                            </div>

                                            <div class="row" data-country="{{ App\Models\WeddingRsvpModel::COUNTRY_US }}" @style(['display: none' => ! $isUnitedStates])>
                                                <label class="col-md-3 col-form-label d-flex align-items-center pt-2 pb-0 py-md-2 fs-6 fw-bold" for="state" role="button">
                                                    <span class="text-danger">※&nbsp;</span> 州（State）
                                                </label>
                                                <div class="col-md-8 form-text d-flex align-items-center pt-0 pb-2 py-md-2 fs-6">
                                                    <select name="prefecture" id="state" class="form-control" @disabled(! $isUnitedStates)>
                                                        <option value="">未選択</option>
                                                        @foreach ($assign['states'] as $key => $label)
                                                            <option value="{{ $key }}" @selected($isUnitedStates && $key === old('prefecture', $record->prefecture))>{{ $label }}</option>
                                                        @endforeach
                                                    </select>
                                                </div>
                                            </div>

                                            @error ('prefecture')
                                                <div class="row">
                                                    <div class="col-md-3"></div>
                                                    <div class="col-md-8">
                                                        <div class="alert alert-danger mt-0 p-1 form-text" role="alert">{{ $message }}</div>
                                                    </div>
                                                </div>
                                            @enderror

                                            <div class="row">
                                                <label class="col-md-3 col-form-label d-flex align-items-center pt-2 pb-0 py-md-2 fs-6 fw-bold" for="city" role="button">
                                                    <span class="text-danger">※&nbsp;</span> 市区町村 / City
                                                </label>
                                                <div class="col-md-8 form-text d-flex align-items-center pt-0 pb-2 py-md-2 fs-6">
                                                    <input type="text" name="city" value="{{ old('city', $record->city) }}" id="city" class="form-control">
                                                </div>
                                                @error ('city')
                                                    <div class="col-md-3"></div>
                                                    <div class="col-md-8">
                                                        <div class="alert alert-danger mt-0 p-1 form-text" role="alert">{{ $message }}</div>
                                                    </div>
                                                @enderror
                                            </div>

                                            <div class="row">
                                                <label class="col-md-3 col-form-label d-flex align-items-center pt-2 pb-0 py-md-2 fs-6 fw-bold" for="address" role="button">
                                                    <span class="text-danger">※&nbsp;</span> 番地 / Street Address
                                                </label>
                                                <div class="col-md-8 form-text d-flex align-items-center pt-0 pb-2 py-md-2 fs-6">
                                                    <input type="text" name="address" value="{{ old('address', $record->address) }}" id="address" class="form-control">
                                                </div>
                                                @error ('address')
                                                    <div class="col-md-3"></div>
                                                    <div class="col-md-8">
                                                        <div class="alert alert-danger mt-0 p-1 form-text" role="alert">{{ $message }}</div>
                                                    </div>
                                                @enderror
                                            </div>

                                            <div class="row">
                                                <label class="col-md-3 col-form-label d-flex align-items-center pt-2 pb-0 py-md-2 fs-6 fw-bold" for="building" role="button">
                                                    建物名 / Apt / Suite
                                                </label>
                                                <div class="col-md-8 form-text d-flex align-items-center pt-0 pb-2 py-md-2 fs-6">
                                                    <input type="text" name="building" value="{{ old('building', $record->building) }}" id="building" class="form-control">
                                                </div>
                                                @error ('building')
                                                    <div class="col-md-3"></div>
                                                    <div class="col-md-8">
                                                        <div class="alert alert-danger mt-0 p-1 form-text" role="alert">{{ $message }}</div>
                                                    </div>
                                                @enderror
                                            </div>

                                            <div class="row">
                                                <label class="col-md-3 col-form-label d-flex align-items-center pt-2 pb-0 py-md-2 fs-6 fw-bold" for="phone" role="button">
                                                    <span class="text-danger">※&nbsp;</span> 電話番号
                                                </label>
                                                <div class="col-md-8 form-text d-flex align-items-center pt-0 pb-2 py-md-2 fs-6">
                                                    <input type="text" name="phone" value="{{ old('phone', $record->phone) }}" id="phone" class="form-control">
                                                </div>
                                                @error ('phone')
                                                    <div class="col-md-3"></div>
                                                    <div class="col-md-8">
                                                        <div class="alert alert-danger mt-0 p-1 form-text" role="alert">{{ $message }}</div>
                                                    </div>
                                                @enderror
                                            </div>

                                            <div class="row">
                                                <label class="col-md-3 col-form-label d-flex align-items-center pt-2 pb-0 py-md-2 fs-6 fw-bold" for="email" role="button">
                                                    <span class="text-danger">※&nbsp;</span> メールアドレス
                                                </label>
                                                <div class="col-md-8 form-text d-flex align-items-center pt-0 pb-2 py-md-2 fs-6">
                                                    <input type="text" name="email" value="{{ old('email', $record->email) }}" id="email" class="form-control emailFmt">
                                                </div>
                                                @error ('email')
                                                    <div class="col-md-3"></div>
                                                    <div class="col-md-8">
                                                        <div class="alert alert-danger mt-0 p-1 form-text" role="alert">{{ $message }}</div>
                                                    </div>
                                                @enderror
                                            </div>

                                            <div class="row">
                                                <label class="col-md-3 col-form-label d-flex align-items-center pt-2 pb-0 py-md-2 fs-6 fw-bold" for="allergy" role="button">
                                                    アレルギー・お食事のご要望
                                                </label>
                                                <div class="col-md-8 form-text d-flex align-items-center pt-0 pb-2 py-md-2 fs-6">
                                                    <textarea name="allergy" id="allergy" rows="3" class="form-control">{{ old('allergy', $record->allergy) }}</textarea>
                                                </div>
                                                @error ('allergy')
                                                    <div class="col-md-3"></div>
                                                    <div class="col-md-8">
                                                        <div class="alert alert-danger mt-0 p-1 form-text" role="alert">{{ $message }}</div>
                                                    </div>
                                                @enderror
                                            </div>

                                            <div class="row">
                                                <label class="col-md-3 col-form-label d-flex align-items-center pt-2 pb-0 py-md-2 fs-6 fw-bold" for="arrival_date" role="button">
                                                    沖縄への到着日
                                                </label>
                                                <div class="col-md-8 form-text d-flex flex-row align-items-center pt-0 pb-2 py-md-2">
                                                    <input type="text" name="arrival_date" value="{{ old('arrival_date', optional($record->arrival_date)->format('Y/m/d')) }}" id="arrival_date" class="form-control datepicker" autocomplete="off">
                                                </div>
                                                @error ('arrival_date')
                                                    <div class="col-md-3"></div>
                                                    <div class="col-md-8">
                                                        <div class="alert alert-danger mt-0 p-1 form-text" role="alert">{{ $message }}</div>
                                                    </div>
                                                @enderror
                                            </div>

                                            <div class="row">
                                                <label class="col-md-3 col-form-label d-flex align-items-center pt-2 pb-0 py-md-2 fs-6 fw-bold" for="departure_date" role="button">
                                                    沖縄からの出発日
                                                </label>
                                                <div class="col-md-8 form-text d-flex flex-row align-items-center pt-0 pb-2 py-md-2">
                                                    <input type="text" name="departure_date" value="{{ old('departure_date', optional($record->departure_date)->format('Y/m/d')) }}" id="departure_date" class="form-control datepicker" autocomplete="off">
                                                </div>
                                                @error ('departure_date')
                                                    <div class="col-md-3"></div>
                                                    <div class="col-md-8">
                                                        <div class="alert alert-danger mt-0 p-1 form-text" role="alert">{{ $message }}</div>
                                                    </div>
                                                @enderror
                                            </div>

                                            <div class="row">
                                                <label class="col-md-3 col-form-label d-flex align-items-center pt-2 pb-0 py-md-2 fs-6 fw-bold" for="hotel_name" role="button">
                                                    宿泊先ホテル名
                                                </label>
                                                <div class="col-md-8 form-text d-flex align-items-center pt-0 pb-2 py-md-2 fs-6">
                                                    <input type="text" name="hotel_name" value="{{ old('hotel_name', $record->hotel_name) }}" id="hotel_name" class="form-control">
                                                </div>
                                                @error ('hotel_name')
                                                    <div class="col-md-3"></div>
                                                    <div class="col-md-8">
                                                        <div class="alert alert-danger mt-0 p-1 form-text" role="alert">{{ $message }}</div>
                                                    </div>
                                                @enderror
                                            </div>

                                            <div class="row">
                                                <label class="col-md-3 col-form-label d-flex align-items-center pt-2 pb-0 py-md-2 fs-6 fw-bold" for="costume_size" role="button">
                                                    当日衣装のサイズ
                                                </label>
                                                <div class="col-md-8 form-text d-flex align-items-center pt-0 pb-2 py-md-2 fs-6">
                                                    <select name="costume_size" id="costume_size" class="form-control">
                                                        <option value="">未選択</option>
                                                        @foreach ($assign['costumeSizes'] as $key => $label)
                                                            <option value="{{ $key }}" @selected($key === old('costume_size', $record->costume_size))>{{ $label }}</option>
                                                        @endforeach
                                                    </select>
                                                </div>
                                                @error ('costume_size')
                                                    <div class="col-md-3"></div>
                                                    <div class="col-md-8">
                                                        <div class="alert alert-danger mt-0 p-1 form-text" role="alert">{{ $message }}</div>
                                                    </div>
                                                @enderror
                                            </div>

                                            <div class="row">
                                                <label class="col-md-3 col-form-label d-flex align-items-center pt-2 pb-0 py-md-2 fs-6 fw-bold" role="button">
                                                    <span class="text-danger">※&nbsp;</span> 同伴者の有無
                                                </label>
                                                <div class="col-md-8 form-text d-flex align-items-center pt-0 pb-2 py-md-2 fs-6">
                                                    <span class="text-nowrap me-3">
                                                        <input type="radio" name="companion_flag" value="1" class="form-check-input" id="companion_flag_1" role="button" @checked($companionFlag === '1')>
                                                        <label for="companion_flag_1" role="button">あり</label>
                                                    </span>
                                                    <span class="text-nowrap">
                                                        <input type="radio" name="companion_flag" value="0" class="form-check-input" id="companion_flag_0" role="button" @checked($companionFlag !== '1')>
                                                        <label for="companion_flag_0" role="button">なし</label>
                                                    </span>
                                                </div>
                                                @error ('companion_flag')
                                                    <div class="col-md-3"></div>
                                                    <div class="col-md-8">
                                                        <div class="alert alert-danger mt-0 p-1 form-text" role="alert">{{ $message }}</div>
                                                    </div>
                                                @enderror
                                            </div>

                                            <div id="companionArea" @style(['display: none' => $companionFlag !== '1'])>
                                                @error ('companions')
                                                    <div class="row">
                                                        <div class="col-md-3"></div>
                                                        <div class="col-md-8">
                                                            <div class="alert alert-danger mt-0 p-1 form-text" role="alert">{{ $message }}</div>
                                                        </div>
                                                    </div>
                                                @enderror
                                                <div id="companionRows">
                                                    @foreach ($companions as $index => $companion)
                                                        @include ('office/wedding-rsvps/edit/companion_row', ['index' => $index, 'number' => $loop->iteration, 'companion' => $companion, 'meals' => $assign['meals']])
                                                    @endforeach
                                                </div>
                                                <div class="row">
                                                    <div class="col-12">
                                                        <button type="button" id="companionAdd" class="btn btn-outline-secondary">＋ 同伴者を追加</button>
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="row mt-3">
                                                <label class="col-md-3 col-form-label d-flex align-items-center pt-2 pb-0 py-md-2 fs-6 fw-bold" for="message" role="button">
                                                    新郎新婦へのメッセージ
                                                </label>
                                                <div class="col-md-8 form-text d-flex align-items-center pt-0 pb-2 py-md-2 fs-6">
                                                    <textarea name="message" id="message" rows="4" class="form-control">{{ old('message', $record->message) }}</textarea>
                                                </div>
                                                @error ('message')
                                                    <div class="col-md-3"></div>
                                                    <div class="col-md-8">
                                                        <div class="alert alert-danger mt-0 p-1 form-text" role="alert">{{ $message }}</div>
                                                    </div>
                                                @enderror
                                            </div>

                                            <div class="row">
                                                <label class="col-md-3 col-form-label d-flex align-items-center pt-2 pb-0 py-md-2 fs-6 fw-bold" for="song_request" role="button">
                                                    楽曲リクエスト
                                                </label>
                                                <div class="col-md-8 form-text d-flex align-items-center pt-0 pb-2 py-md-2 fs-6">
                                                    <input type="text" name="song_request" value="{{ old('song_request', $record->song_request) }}" id="song_request" class="form-control">
                                                </div>
                                                @error ('song_request')
                                                    <div class="col-md-3"></div>
                                                    <div class="col-md-8">
                                                        <div class="alert alert-danger mt-0 p-1 form-text" role="alert">{{ $message }}</div>
                                                    </div>
                                                @enderror
                                            </div>

                                            {{-- 進むボタン --}}
                                            <div class="mt-3">
                                                <button type="submit" class="btn btn-success d-grid w-100 text-white text-break" id="submit">確認する</button>
                                            </div>

                                            {{-- 戻るボタン --}}
                                            @if (in_array('officeWeddingRsvpIndex*', Auth::user()->routes()))
                                                <div class="my-3">
                                                    <a href="{{ route('officeWeddingRsvpIndex', session('officeWeddingRsvpIndexSearchParams')) }}" class="text-break btn btn-outline-dark col-12 mb-0">前のページに戻る</a>
                                                </div>
                                            @endif
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

        {{-- 「＋ 同伴者を追加」で複製する行のテンプレート（__INDEX__・__NUMBER__はJSが置換する） --}}
        <template id="companionRowTemplate">
            @include ('office/wedding-rsvps/edit/companion_row', ['index' => '__INDEX__', 'number' => '__NUMBER__', 'companion' => [], 'meals' => $assign['meals']])
        </template>

        <!-- Page JS -->
        @push ('js')
            <script>
                $(function () {
                    const maxCompanions = {{ App\Models\WeddingRsvpCompanionModel::MAX_COUNT }};
                    let companionIndex = {{ count($companions) }};

                    // ご住所の国に応じて都道府県／州の入力欄を切り替える（使わない欄はdisabledにして送信しない）
                    function toggleCountry() {
                        const isUnitedStates = $('#country').val() === '{{ App\Models\WeddingRsvpModel::COUNTRY_US }}';
                        $('[data-country="{{ App\Models\WeddingRsvpModel::COUNTRY_US }}"]').toggle(isUnitedStates).find('input, select').prop('disabled', ! isUnitedStates);
                        $('[data-country="{{ App\Models\WeddingRsvpModel::COUNTRY_JP }}"]').toggle(! isUnitedStates).find('input, select').prop('disabled', isUnitedStates);
                    }

                    // 「○人目の同伴者」の番号を並び順に振り直す
                    function renumberCompanions() {
                        $('#companionRows [data-companion-row]').each(function (index) {
                            $(this).find('[data-companion-number]').text(index + 1);
                        });
                    }

                    function addCompanion() {
                        if ($('#companionRows [data-companion-row]').length >= maxCompanions) {
                            alert('同伴者は' + maxCompanions + '名までご入力いただけます。');
                            return;
                        }

                        const html = $('#companionRowTemplate').html()
                            .replace(/__INDEX__/g, companionIndex)
                            .replace(/__NUMBER__/g, companionIndex + 1);
                        companionIndex++;
                        $('#companionRows').append(html);
                        renumberCompanions();
                    }

                    $('#country').on('change', toggleCountry);
                    toggleCountry();

                    $('#companionAdd').on('click', addCompanion);

                    $('#companionRows').on('click', '[data-companion-remove]', function () {
                        $(this).closest('[data-companion-row]').remove();
                        renumberCompanions();
                    });

                    // 同伴者「あり」に変えたときは1名分の入力行を用意する
                    $('input[name="companion_flag"]').on('change', function () {
                        const hasCompanion = $('input[name="companion_flag"]:checked').val() === '1';
                        $('#companionArea').toggle(hasCompanion);
                        if (hasCompanion && $('#companionRows [data-companion-row]').length === 0) {
                            addCompanion();
                        }
                    });
                });
            </script>
        @endpush
    </div>

@endsection
