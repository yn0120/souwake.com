@extends('wedding/layout')

@section('title', 'Wedding Invitation | ご結婚式のご案内')
@section('description', '沖縄リゾートウェディングへのご招待と、ご出欠フォームのご案内です。')

@section('content')
    {{-- ▼▼ ここから下、新郎新婦のお名前・日付・会場情報などは仮の文言です。実際の内容に書き換えてください ▼▼ --}}
    @php
        $weddingDateText = '2027年03月21日（日）';
        $rsvpDeadlineText = config('services.wedding.rsvp_deadline', '2026年12月31日（木）');
        $ceremonyTime = '10時15分 挙式';
        $receptionTime = '12時15分 披露宴';
        $venueName = 'ザ・ギノザリゾート 美らの教会';
        $venueAddress = '〒904-1303 沖縄県国頭郡宜野座村惣慶1182';

        $prefectures = [
            '北海道', '青森県', '岩手県', '宮城県', '秋田県', '山形県', '福島県',
            '茨城県', '栃木県', '群馬県', '埼玉県', '千葉県', '東京都', '神奈川県',
            '新潟県', '富山県', '石川県', '福井県', '山梨県', '長野県', '岐阜県',
            '静岡県', '愛知県', '三重県', '滋賀県', '京都府', '大阪府', '兵庫県',
            '奈良県', '和歌山県', '鳥取県', '島根県', '岡山県', '広島県', '山口県',
            '徳島県', '香川県', '愛媛県', '高知県', '福岡県', '佐賀県', '長崎県',
            '熊本県', '大分県', '宮崎県', '鹿児島県', '沖縄県',
        ];

        $inputClass = 'w-full rounded-xl border border-sand-300 bg-white/70 px-4 py-2.5 text-ink-800 placeholder:text-ink-700/40 focus:border-moss-500 focus:outline-none focus:ring-2 focus:ring-moss-400/30';
        $labelClass = 'mb-1.5 block text-sm font-medium text-ink-800';
        $noteClass = 'mt-1 text-xs text-ink-700/60';
        $errorClass = 'mt-1 text-xs text-clay-600';
        $old = fn ($key, $default = null) => old($key, $default);

        // お祝い画像の上限（サーバー側のバリデーションと同じ値をJS・表示にも渡す）
        $photoMaxFiles = App\Http\Controllers\Wedding\WeddingRsvpPhotoController::MAX_FILES_PER_SESSION;
        $photoMaxSize = App\Http\Controllers\Wedding\WeddingRsvpPhotoController::MAX_FILE_SIZE;

        // 同伴者（連名）。バリデーションエラー後は入力済みの行数を復元し、初回表示は1行だけ出す
        $companionMaxCount = App\Models\WeddingRsvpCompanionModel::MAX_COUNT;
        $companionRows = array_values((array) old('companions', [[]])) ?: [[]];

        // ご住所の国。既定は日本
        $selectedCountry = $old('country', App\Models\WeddingRsvpModel::COUNTRY_JP);
    @endphp

    {{-- ナビゲーション --}}
    <header class="sticky top-0 z-40 border-b border-sand-200/70 bg-sand-50/85 backdrop-blur">
        <nav class="mx-auto flex max-w-5xl items-center justify-between px-5 py-3">
            <a href="#home" data-scroll class="font-serif-jp text-sm tracking-widest text-moss-700">WEDDING INVITATION</a>
            <ul class="hidden gap-6 text-sm text-ink-700 sm:flex">
                <li><a href="#greeting" data-scroll class="hover:text-moss-600">ご挨拶</a></li>
                <li><a href="#info" data-scroll class="hover:text-moss-600">日程・会場</a></li>
                <li><a href="#gallery" data-scroll class="hover:text-moss-600">ギャラリー</a></li>
                <li><a href="#rsvp" data-scroll class="hover:text-moss-600">ご出欠フォーム</a></li>
            </ul>
            <a href="#rsvp" data-scroll class="rounded-full bg-moss-600 px-4 py-1.5 text-sm font-medium text-sand-50 shadow-sm transition hover:bg-moss-700">
                ご回答はこちら
            </a>
        </nav>
    </header>

    {{-- ヒーロー --}}
    <section id="home" class="relative flex min-h-[92vh] items-end overflow-hidden">
        <div class="absolute inset-0">
            @include('wedding/parts/photo', [
                'path' => 'assets/img/wedding/hero.jpg',
                'alt' => '新郎新婦のメイン写真',
                'label' => 'メイン写真（縦長推奨 1600×2000程度）',
                'class' => 'h-full w-full rounded-none',
            ])
            <div class="absolute inset-0 bg-gradient-to-t from-ink-900/70 via-ink-900/15 to-ink-900/10"></div>
        </div>
        <div class="relative z-10 mx-auto w-full max-w-5xl px-6 pb-16 pt-40 text-sand-50">
            <p class="wedding-reveal animate-fade-up text-xs tracking-[0.4em] text-sand-100/90">OKINAWA RESORT WEDDING</p>
            <h1 class="wedding-reveal animate-fade-up mt-4 font-serif-jp text-4xl leading-relaxed sm:text-5xl">
                仲道 祐貴 &amp; 加藤 奈緒
            </h1>
            <p class="wedding-reveal animate-fade-up mt-5 text-base text-sand-100/95 sm:text-lg">{{ $weddingDateText }}</p>
            <a href="#rsvp" data-scroll class="wedding-reveal animate-fade-up mt-8 inline-flex items-center gap-2 rounded-full border border-sand-50/70 px-6 py-2.5 text-sm tracking-wide transition hover:bg-sand-50 hover:text-moss-700">
                ご出欠のご連絡はこちら
                <span aria-hidden="true">↓</span>
            </a>
        </div>
    </section>

    <main class="mx-auto max-w-5xl px-5">
        {{-- ご挨拶 --}}
        <section id="greeting" class="grid gap-10 py-20 sm:grid-cols-2 sm:items-center sm:gap-14 sm:py-28">
            <div class="wedding-reveal">
                @include('wedding/parts/photo', [
                    'path' => 'assets/img/wedding/greeting.jpg',
                    'alt' => '新郎新婦のスナップ写真',
                    'label' => 'お二人のスナップ写真',
                    'class' => 'aspect-[4/5]',
                ])
            </div>
            <div class="wedding-reveal">
                <p class="text-xs tracking-[0.35em] text-moss-600">GREETING</p>
                <h2 class="mt-3 font-serif-jp text-2xl text-ink-800">ご挨拶</h2>
                <p class="mt-6 leading-loose text-ink-700">
                    拝啓　皆様におかれましては、ますますご清祥のこととお慶び申し上げます。<br class="hidden sm:block">
                    このたび私たちは、結婚式を沖縄の地で執り行うこととなりました。<br class="hidden sm:block">
                    日頃お世話になっている皆様に見守っていただきながら、<br class="hidden sm:block">
                    ふたりで新しい生活の出発を報告したく存じます。<br class="hidden sm:block">
                    ご多用中、また遠方への旅となり恐縮ではございますが、<br class="hidden sm:block">
                    ぜひご出席いただけますよう、謹んでご案内申し上げます。
                </p>
                <p class="mt-8 text-right text-ink-700">仲道 祐貴 ・ 加藤 奈緒</p>
            </div>
        </section>

        {{-- 日程・会場 --}}
        <section id="info" class="wedding-reveal py-20 sm:py-28">
            <p class="text-center text-xs tracking-[0.35em] text-moss-600">DATE &amp; VENUE</p>
            <h2 class="mt-3 text-center font-serif-jp text-2xl text-ink-800">日程・会場のご案内</h2>

            <div class="mx-auto mt-12 grid gap-6 sm:grid-cols-2">
                <div class="rounded-2xl border border-sand-200 bg-white/60 p-7">
                    <p class="font-serif-jp text-lg text-moss-700">{{ $weddingDateText }}</p>
                    <ul class="mt-4 space-y-1.5 text-sm text-ink-700">
                        <li>{{ $ceremonyTime }}</li>
                        <li>{{ $receptionTime }}</li>
                    </ul>
                </div>
                <div class="rounded-2xl border border-sand-200 bg-white/60 p-7">
                    <p class="font-serif-jp text-lg text-moss-700">{{ $venueName }}</p>
                    <p class="mt-4 text-sm leading-relaxed text-ink-700"><a href="https://maps.app.goo.gl/E2vn1awSKASX7pN57" target="_blank">{{ $venueAddress }}</a></p>
                    <p class="mt-3 text-xs text-ink-700/60"><a href="https://maps.app.goo.gl/E2vn1awSKASX7pN57" target="_blank">https://maps.app.goo.gl/E2vn1awSKASX7pN57</a></p>
                </div>
            </div>

            <div class="mt-8">
                @include('wedding/parts/photo', [
                    'path' => 'assets/img/wedding/venue.jpg',
                    'alt' => '会場の外観・チャペル',
                    'label' => '会場・チャペルの写真',
                    'class' => 'aspect-[16/7]',
                ])
            </div>
        </section>

        {{-- 沖縄リゾート婚のご案内 --}}
        <section class="wedding-reveal rounded-3xl bg-moss-700/95 px-6 py-14 text-sand-50 sm:px-12 sm:py-16">
            <p class="text-xs tracking-[0.35em] text-sand-100/80">FOR YOUR TRIP TO OKINAWA</p>
            <h2 class="mt-3 font-serif-jp text-2xl">沖縄への旅程についてのお願い</h2>
            <p class="mt-6 max-w-2xl leading-loose text-sand-100/95">
                当日は沖縄の地でのおもてなしとなるため、下記フォームにて到着日・出発日、ご宿泊先などをあわせてお知らせいただけますと幸いです。<br>
                お食事にはえびや豚肉、マンゴーなど沖縄の食材を多く使用する予定です。アレルギーやご要望がございましたら、遠慮なくご記入ください。
            </p>
            <p class="mt-6 text-sm text-sand-100/80">
                当日はお二人からゲストの皆様へ「かりゆしウェア」をご用意する予定です。サイズのご確認にご協力ください。
            </p>
        </section>

        {{-- ギャラリー --}}
        <section id="gallery" class="wedding-reveal py-20 sm:py-28">
            <p class="text-center text-xs tracking-[0.35em] text-moss-600">GALLERY</p>
            <h2 class="mt-3 text-center font-serif-jp text-2xl text-ink-800">ギャラリー</h2>
            <div class="mt-12 grid grid-cols-2 gap-3 sm:grid-cols-3 sm:gap-4">
                @for ($i = 1; $i <= 6; $i++)
                    @include('wedding/parts/photo', [
                        'path' => "assets/img/wedding/gallery-{$i}.jpg",
                        'alt' => "ギャラリー写真{$i}",
                        'label' => "ギャラリー写真 {$i}",
                        'class' => 'aspect-square',
                    ])
                @endfor
            </div>
        </section>

        {{-- ご出欠フォーム --}}
        <section id="rsvp" class="wedding-reveal py-20 sm:py-28">
            <p class="text-center text-xs tracking-[0.35em] text-moss-600">RSVP</p>
            <h2 class="mt-3 text-center font-serif-jp text-2xl text-ink-800">ご出欠のご連絡</h2>

            <div class="mx-auto mt-6 max-w-xl rounded-2xl border border-clay-400/40 bg-clay-500/10 px-6 py-4 text-center">
                <p class="text-sm text-clay-700">
                    大変お手数ではございますが、<span class="font-semibold">{{ $rsvpDeadlineText }}</span> までにご回答いただけますと幸いです。
                </p>
            </div>

            @if ($errors->any())
                <div class="mx-auto mt-8 max-w-2xl rounded-2xl border border-clay-500/50 bg-clay-500/10 p-5">
                    <p class="font-medium text-clay-700">ご入力内容をご確認ください</p>
                    <ul class="mt-2 list-disc space-y-0.5 pl-5 text-sm text-clay-700">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @if (session('error'))
                <div class="mx-auto mt-8 max-w-2xl rounded-2xl border border-clay-500/50 bg-clay-500/10 p-5 text-sm text-clay-700">
                    {{ session('error') }}
                </div>
            @endif

            {{-- data-restore-input: 入力途中の内容をlocalStorageへ退避・復元する（wedding.js）。
                 バリデーションエラー等でold()が入っている場合はサーバー側の値を優先するため復元しない。 --}}
            <form
                method="POST"
                action="{{ route('weddingRsvpCreateExecute') }}"
                class="mx-auto mt-10 max-w-2xl space-y-12"
                data-rsvp-form
                data-restore-input="{{ old() ? '0' : '1' }}"
            >
                @csrf

                {{-- ハニーポット（人間には非表示。ボット対策） --}}
                <div class="absolute -left-[9999px] top-auto h-px w-px overflow-hidden" aria-hidden="true">
                    <label for="contact_note">ご連絡事項</label>
                    <input type="text" id="contact_note" name="contact_note" tabindex="-1" autocomplete="off">
                </div>

                {{-- 1. 基本情報 --}}
                <fieldset class="space-y-6">
                    <legend class="font-serif-jp text-lg text-moss-700">1. 基本情報</legend>

                    <div>
                        <span class="{{ $labelClass }}">ご出欠 <span class="text-clay-600">※必須</span></span>
                        <div class="grid grid-cols-2 gap-3">
                            <label class="relative cursor-pointer">
                                <input type="radio" name="attendance" value="attending" class="peer sr-only" {{ $old('attendance', 'attending') === 'attending' ? 'checked' : '' }}>
                                <span class="block rounded-xl border border-sand-300 bg-white/60 px-4 py-3 text-center font-medium text-ink-700 transition peer-checked:border-moss-600 peer-checked:bg-moss-600 peer-checked:text-sand-50">
                                    ご出席
                                </span>
                            </label>
                            <label class="relative cursor-pointer">
                                <input type="radio" name="attendance" value="absent" class="peer sr-only" {{ $old('attendance') === 'absent' ? 'checked' : '' }}>
                                <span class="block rounded-xl border border-sand-300 bg-white/60 px-4 py-3 text-center font-medium text-ink-700 transition peer-checked:border-clay-600 peer-checked:bg-clay-600 peer-checked:text-sand-50">
                                    ご欠席
                                </span>
                            </label>
                        </div>
                        @error('attendance') <p class="{{ $errorClass }}">{{ $message }}</p> @enderror
                    </div>

                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <label class="{{ $labelClass }}" for="name_sei">お名前（姓） <span class="text-clay-600">※必須</span></label>
                            <input class="{{ $inputClass }}" type="text" id="name_sei" name="name_sei" placeholder="山田" value="{{ $old('name_sei') }}">
                            @error('name_sei') <p class="{{ $errorClass }}">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="{{ $labelClass }}" for="name_mei">お名前（名） <span class="text-clay-600">※必須</span></label>
                            <input class="{{ $inputClass }}" type="text" id="name_mei" name="name_mei" placeholder="太郎" value="{{ $old('name_mei') }}">
                            @error('name_mei') <p class="{{ $errorClass }}">{{ $message }}</p> @enderror
                        </div>
                    </div>

                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <label class="{{ $labelClass }}" for="kana_sei">フリガナ（姓） <span class="text-clay-600">※必須</span></label>
                            <input class="{{ $inputClass }}" type="text" id="kana_sei" name="kana_sei" placeholder="ヤマダ" value="{{ $old('kana_sei') }}">
                            @error('kana_sei') <p class="{{ $errorClass }}">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="{{ $labelClass }}" for="kana_mei">フリガナ（名） <span class="text-clay-600">※必須</span></label>
                            <input class="{{ $inputClass }}" type="text" id="kana_mei" name="kana_mei" placeholder="タロウ" value="{{ $old('kana_mei') }}">
                            @error('kana_mei') <p class="{{ $errorClass }}">{{ $message }}</p> @enderror
                        </div>
                    </div>

                    {{-- ご住所の国。日本／アメリカで住所欄の項目・自動入力先のAPIを切り替える（wedding.js） --}}
                    <div data-country-switch>
                        <span class="{{ $labelClass }}">お住まいの国 / Country <span class="text-clay-600">※必須</span></span>
                        <div class="grid grid-cols-2 gap-3">
                            @foreach (App\Models\WeddingRsvpModel::countryOptions() as $value => $label)
                                <label class="relative cursor-pointer">
                                    <input type="radio" name="country" value="{{ $value }}" class="peer sr-only" {{ $selectedCountry === $value ? 'checked' : '' }} data-country-option>
                                    <span class="block rounded-xl border border-sand-300 bg-white/60 px-4 py-3 text-center font-medium text-ink-700 transition peer-checked:border-moss-600 peer-checked:bg-moss-600 peer-checked:text-sand-50">
                                        {{ $label }}
                                    </span>
                                </label>
                            @endforeach
                        </div>
                        <p class="{{ $noteClass }} hidden" data-country-note="US">
                            フリガナは任意です。/ Furigana (katakana) is optional for guests outside Japan.
                        </p>
                        @error('country') <p class="{{ $errorClass }}">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <p class="{{ $labelClass }}">ご住所 / Address <span class="text-clay-600">※必須</span></p>
                        <p class="{{ $noteClass }} mb-3">引き出物や内祝いの発送、案内状の送付に使用いたします。</p>
                        <div class="grid gap-4 sm:grid-cols-2">
                            <div>
                                {{-- 入力後（フォーカスアウト時）に数字以外を取り除き、住所を自動セットする（日本=Zipcloud／アメリカ=Zippopotam.us） --}}
                                <input class="{{ $inputClass }}" type="text" name="postal_code" inputmode="numeric" autocomplete="postal-code" maxlength="10" value="{{ $old('postal_code') }}"
                                    data-postal-code
                                    data-placeholder-jp="9000001"
                                    data-placeholder-us="90210 (ZIP Code)">
                                <p class="{{ $noteClass }}" data-postal-note
                                    data-note-jp="ハイフンなしの数字7桁でご入力いただくと、住所が自動で入力されます。"
                                    data-note-us="Enter your 5-digit ZIP code and your state and city will be filled in automatically."></p>
                                <p class="{{ $errorClass }} hidden" data-postal-message></p>
                                @error('postal_code') <p class="{{ $errorClass }}">{{ $message }}</p> @enderror
                            </div>
                            <div data-country-only="JP">
                                <select class="{{ $inputClass }}" name="prefecture" data-address-prefecture>
                                    <option value="">都道府県を選択</option>
                                    @foreach ($prefectures as $prefecture)
                                        <option value="{{ $prefecture }}" @selected($old('prefecture') === $prefecture)>{{ $prefecture }}</option>
                                    @endforeach
                                </select>
                                @error('prefecture') <p class="{{ $errorClass }}">{{ $message }}</p> @enderror
                            </div>
                            <div data-country-only="US" class="hidden">
                                <select class="{{ $inputClass }}" name="state" data-address-state>
                                    <option value="">Select your state</option>
                                    @foreach (App\Models\WeddingRsvpModel::usStates() as $value => $label)
                                        <option value="{{ $value }}" @selected($old('state') === $value)>{{ $label }}</option>
                                    @endforeach
                                </select>
                                @error('state') <p class="{{ $errorClass }}">{{ $message }}</p> @enderror
                            </div>
                        </div>
                        <div class="mt-4">
                            <input class="{{ $inputClass }}" type="text" name="city" value="{{ $old('city') }}" data-address-city
                                data-placeholder-jp="市区町村"
                                data-placeholder-us="City">
                            @error('city') <p class="{{ $errorClass }}">{{ $message }}</p> @enderror
                        </div>
                        <div class="mt-4">
                            <input class="{{ $inputClass }}" type="text" name="address" value="{{ $old('address') }}" data-address-street
                                data-placeholder-jp="番地"
                                data-placeholder-us="Street address（例：123 Main St）">
                            @error('address') <p class="{{ $errorClass }}">{{ $message }}</p> @enderror
                        </div>
                        <div class="mt-4">
                            <input class="{{ $inputClass }}" type="text" name="building" value="{{ $old('building') }}"
                                data-placeholder-jp="建物名・部屋番号（任意）"
                                data-placeholder-us="Apt / Suite（optional）">
                            @error('building') <p class="{{ $errorClass }}">{{ $message }}</p> @enderror
                        </div>
                    </div>

                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <label class="{{ $labelClass }}" for="phone">電話番号（当日連絡がつく携帯番号） <span class="text-clay-600">※必須</span></label>
                            <input class="{{ $inputClass }}" type="tel" id="phone" name="phone" value="{{ $old('phone') }}"
                                data-placeholder-jp="090-1234-5678"
                                data-placeholder-us="+1 555-123-4567">
                            @error('phone') <p class="{{ $errorClass }}">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="{{ $labelClass }}" for="email">メールアドレス <span class="text-clay-600">※必須</span></label>
                            <input class="{{ $inputClass }}" type="email" id="email" name="email" placeholder="taro@example.com" value="{{ $old('email') }}">
                            <p class="{{ $noteClass }}">ご回答の控えをお送りいたします。</p>
                            @error('email') <p class="{{ $errorClass }}">{{ $message }}</p> @enderror
                        </div>
                    </div>
                </fieldset>

                {{-- 2. 沖縄リゾート婚特有の項目（ご出席の場合のみ表示） --}}
                <fieldset id="attending-only-fields" class="space-y-6 {{ $old('attendance', 'attending') === 'attending' ? '' : 'hidden' }}">
                    <legend class="font-serif-jp text-lg text-moss-700">2. ご旅程について</legend>

                    <div>
                        <label class="{{ $labelClass }}" for="allergy">アレルギー・お食事に関するご要望</label>
                        <textarea class="{{ $inputClass }}" id="allergy" name="allergy" rows="3" placeholder="えび・小麦・アルコールNGなど、沖縄の食材（海老・豚肉・マンゴー等）を多く使用いたしますので苦手なものも含めお気軽にご入力ください。">{{ $old('allergy') }}</textarea>
                        @error('allergy') <p class="{{ $errorClass }}">{{ $message }}</p> @enderror
                    </div>

                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <label class="{{ $labelClass }}" for="arrival_date">沖縄への到着日</label>
                            {{-- bootstrap-datepicker（public/assets/vendor/libs/bootstrap-datepicker）でカレンダーを表示する --}}
                            <input class="{{ $inputClass }} datepicker" type="text" id="arrival_date" name="arrival_date" placeholder="2027-03-20" autocomplete="off" value="{{ $old('arrival_date') }}">
                            @error('arrival_date') <p class="{{ $errorClass }}">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="{{ $labelClass }}" for="departure_date">沖縄からの出発日</label>
                            <input class="{{ $inputClass }} datepicker" type="text" id="departure_date" name="departure_date" placeholder="2027-03-22" autocomplete="off" value="{{ $old('departure_date') }}">
                            @error('departure_date') <p class="{{ $errorClass }}">{{ $message }}</p> @enderror
                        </div>
                    </div>

                    <div>
                        <label class="{{ $labelClass }}" for="hotel_name">ご宿泊先ホテル名（任意）</label>
                        <input class="{{ $inputClass }}" type="text" id="hotel_name" name="hotel_name" placeholder="○○リゾート＆スパ" value="{{ $old('hotel_name') }}">
                        @error('hotel_name') <p class="{{ $errorClass }}">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="{{ $labelClass }}" for="costume_size">当日の衣装（かりゆしウェア等）のサイズ</label>
                        <select class="{{ $inputClass }}" id="costume_size" name="costume_size">
                            <option value="">選択しない</option>
                            @foreach (['XS', 'S', 'M', 'L', 'LL', '3L'] as $size)
                                <option value="{{ $size }}" @selected($old('costume_size') === $size)>{{ $size }}</option>
                            @endforeach
                        </select>
                        @error('costume_size') <p class="{{ $errorClass }}">{{ $message }}</p> @enderror
                    </div>
                </fieldset>

                {{-- 3. 同伴者・ご家族の情報 --}}
                <fieldset class="space-y-6">
                    <legend class="font-serif-jp text-lg text-moss-700">3. 同伴者・ご家族の情報</legend>

                    <div>
                        <span class="{{ $labelClass }}">同伴者の有無</span>
                        <div class="grid grid-cols-2 gap-3">
                            <label class="relative cursor-pointer">
                                <input type="radio" name="companion_flag" value="0" class="peer sr-only" {{ $old('companion_flag', '0') === '0' ? 'checked' : '' }}>
                                <span class="block rounded-xl border border-sand-300 bg-white/60 px-4 py-3 text-center font-medium text-ink-700 transition peer-checked:border-moss-600 peer-checked:bg-moss-600 peer-checked:text-sand-50">
                                    なし
                                </span>
                            </label>
                            <label class="relative cursor-pointer">
                                <input type="radio" name="companion_flag" value="1" class="peer sr-only" {{ $old('companion_flag') === '1' ? 'checked' : '' }}>
                                <span class="block rounded-xl border border-sand-300 bg-white/60 px-4 py-3 text-center font-medium text-ink-700 transition peer-checked:border-moss-600 peer-checked:bg-moss-600 peer-checked:text-sand-50">
                                    あり
                                </span>
                            </label>
                        </div>
                        @error('companion_flag') <p class="{{ $errorClass }}">{{ $message }}</p> @enderror
                    </div>

                    {{-- 同伴者は連名（複数）で入力できる。行の追加・削除はwedding.jsが担当 --}}
                    <div
                        id="companion-fields"
                        class="space-y-6 {{ $old('companion_flag') === '1' ? '' : 'hidden' }}"
                        data-companion-fields
                        data-max-count="{{ $companionMaxCount }}"
                    >
                        <p class="{{ $noteClass }}">
                            ご家族・ご夫婦など複数名でご出席される場合は、「＋同伴者を追加」からご同伴者の人数分ご入力ください（最大{{ $companionMaxCount }}名）。
                        </p>

                        @error('companions') <p class="{{ $errorClass }}">{{ $message }}</p> @enderror

                        <div class="space-y-5" data-companion-rows>
                            @foreach ($companionRows as $index => $companion)
                                @include('wedding/parts/companion_row', [
                                    'index' => $index,
                                    'number' => $loop->iteration,
                                    'companion' => (array) $companion,
                                ])
                            @endforeach
                        </div>

                        <div class="flex flex-wrap items-center gap-3">
                            <button type="button" data-companion-add class="inline-flex items-center gap-1.5 rounded-full border border-moss-600 px-5 py-2 text-sm font-medium text-moss-700 transition hover:bg-moss-600 hover:text-sand-50">
                                <span aria-hidden="true">＋</span>同伴者を追加
                            </button>
                            <p data-companion-message class="{{ $errorClass }} mt-0 hidden"></p>
                        </div>
                    </div>

                    {{-- 同伴者1名分のテンプレート（__INDEX__・__NUMBER__はJSが実際の番号へ置換する） --}}
                    <template data-companion-row-template>
                        @include('wedding/parts/companion_row', [
                            'index' => '__INDEX__',
                            'number' => '__NUMBER__',
                            'companion' => [],
                        ])
                    </template>
                </fieldset>

                {{-- 4. 任意項目・メッセージ --}}
                <fieldset class="space-y-6">
                    <legend class="font-serif-jp text-lg text-moss-700">4. 任意項目・メッセージ</legend>

                    <div>
                        <label class="{{ $labelClass }}" for="message">新郎新婦へのメッセージ</label>
                        <textarea class="{{ $inputClass }}" id="message" name="message" rows="4" placeholder="お祝いのメッセージをお願いします">{{ $old('message') }}</textarea>
                        @error('message') <p class="{{ $errorClass }}">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="{{ $labelClass }}" for="song_request">楽曲のリクエスト（任意）</label>
                        <p class="{{ $noteClass }} mb-2">披露宴・パーティーで流してほしい曲があればお書きください。</p>
                        <input class="{{ $inputClass }}" type="text" id="song_request" name="song_request" placeholder="アーティスト名・曲名" value="{{ $old('song_request') }}">
                        @error('song_request') <p class="{{ $errorClass }}">{{ $message }}</p> @enderror
                    </div>

                    {{-- お祝い画像。選択した時点で1枚ずつ非同期アップロードし、サーバー側（Horizon）で縮小処理を行う --}}
                    <div
                        data-photo-uploader
                        data-upload-url="{{ route('weddingRsvpPhotoStore') }}"
                        data-status-url="{{ route('weddingRsvpPhotoStatus') }}"
                        data-destroy-url="{{ route('weddingRsvpPhotoDestroy', ['uuid' => '__UUID__']) }}"
                        data-max-files="{{ $photoMaxFiles }}"
                        data-max-size="{{ $photoMaxSize }}"
                    >
                        <label class="{{ $labelClass }}" for="celebration_photos">お祝い画像（任意）</label>
                        <p class="{{ $noteClass }} mb-3">
                            お二人との思い出のお写真や、お祝いメッセージの画像をお送りいただけます。<br>
                            JPEG・PNG・WebP・GIF・HEIC形式、1枚{{ (int) ($photoMaxSize / 1024 / 1024) }}MBまで、最大{{ $photoMaxFiles }}枚までご選択いただけます。
                        </p>

                        <input type="file" id="celebration_photos" class="sr-only" accept="image/*" multiple data-photo-input>

                        <div class="flex flex-wrap items-center gap-3">
                            <button type="button" data-photo-add class="inline-flex items-center gap-1.5 rounded-full border border-moss-600 px-5 py-2 text-sm font-medium text-moss-700 transition hover:bg-moss-600 hover:text-sand-50">
                                <span aria-hidden="true">＋</span>画像を追加
                            </button>
                            <button type="button" data-photo-clear class="hidden items-center gap-1.5 rounded-full border border-clay-500/60 px-5 py-2 text-sm font-medium text-clay-700 transition hover:bg-clay-600 hover:text-sand-50">
                                <span aria-hidden="true">×</span>すべて削除
                            </button>
                        </div>

                        <p data-photo-error class="{{ $errorClass }} hidden"></p>

                        <p data-photo-empty class="{{ $noteClass }} mt-3">まだ画像は選択されていません。</p>
                        <ul data-photo-list class="mt-4 hidden grid-cols-2 gap-3 sm:grid-cols-3"></ul>

                        {{-- アップロード済み画像のuuid（hidden inputとしてJSが生成）--}}
                        <div data-photo-tokens class="hidden"></div>
                        <input type="hidden" name="photo_session_token" value="" data-photo-session-token>
                    </div>

                    {{-- 画像1枚分のテンプレート --}}
                    <template data-photo-item-template>
                        <li class="group relative overflow-hidden rounded-xl border border-sand-300 bg-white/60" data-photo-item>
                            <div class="aspect-square w-full overflow-hidden bg-sand-100">
                                <img alt="" class="h-full w-full object-cover" data-photo-thumb>
                            </div>
                            <div class="flex items-center justify-between gap-2 px-2.5 py-2">
                                <span class="truncate text-xs text-ink-700/80" data-photo-name></span>
                                <button type="button" data-photo-remove class="shrink-0 rounded-full border border-clay-500/60 px-2.5 py-1 text-xs text-clay-700 transition hover:bg-clay-600 hover:text-sand-50">
                                    削除
                                </button>
                            </div>
                            <span data-photo-status class="pointer-events-none absolute left-2 top-2 rounded-full bg-ink-900/70 px-2 py-0.5 text-[10px] tracking-wide text-sand-50"></span>
                        </li>
                    </template>
                </fieldset>

                <div class="pt-4 text-center">
                    <button type="submit" class="inline-flex w-full items-center justify-center rounded-full bg-clay-600 px-10 py-3.5 font-medium text-sand-50 shadow-md transition hover:bg-clay-700 sm:w-auto">
                        この内容で送信する
                    </button>
                    <p class="{{ $noteClass }} mt-4">送信後、ご入力いただいたメールアドレス宛に確認メールをお送りします。</p>
                </div>
            </form>
        </section>
    </main>

    <footer class="border-t border-sand-200 py-10 text-center text-xs text-ink-700/60">
        <p>ご不明な点がございましたら、新郎新婦まで直接ご連絡ください。</p>
        <p class="mt-2">© {{ date('Y') }} wedding.souwake.com</p>
    </footer>
@endsection
