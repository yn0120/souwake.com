<x-office.layout title="出欠回答編集">
    @php
        $record = $assign['record'];
        $country = old('country', $record->country);
        $isUnitedStates = $country === App\Models\WeddingRsvpModel::COUNTRY_US;
        $companionFlag = (string) old('companion_flag', $record->companion_flag ? '1' : '0');
        $companions = $assign['companions'] ?: [];
    @endphp

    <x-office.card title="出欠回答編集">
        <form method="POST" action="{{ route('officeWeddingRsvpEditConfirm', ['id' => $record->id], false) }}" enctype="multipart/form-data">
            @csrf

            <x-office.form.row label="出欠" for="attendance" required name="attendance"
                               help="ご欠席に変更した場合、当日のご予定（アレルギー・到着日・出発日・宿泊先・衣装サイズ）は保存されません。">
                <x-office.form.select name="attendance" id="attendance">
                    <option value="">未選択</option>
                    @foreach ($assign['attendances'] as $key => $label)
                        <option value="{{ $key }}" @selected($key === old('attendance', $record->attendance))>{{ $label }}</option>
                    @endforeach
                </x-office.form.select>
            </x-office.form.row>

            <x-office.form.row label="お名前（姓・名）" for="name_sei" required :name="['name_sei', 'name_mei']">
                <div class="flex gap-2">
                    <x-office.form.input name="name_sei" id="name_sei" :value="old('name_sei', $record->name_sei)" />
                    <x-office.form.input name="name_mei" id="name_mei" :value="old('name_mei', $record->name_mei)" />
                </div>
            </x-office.form.row>

            <x-office.form.row label="フリガナ（姓・名）" for="kana_sei" :required="! $isUnitedStates"
                               :name="['kana_sei', 'kana_mei']"
                               help="アメリカ在住の方（ご住所の国＝アメリカ）は未入力でも保存できます。">
                <div class="flex gap-2">
                    <x-office.form.input name="kana_sei" id="kana_sei" :value="old('kana_sei', $record->kana_sei)" />
                    <x-office.form.input name="kana_mei" id="kana_mei" :value="old('kana_mei', $record->kana_mei)" />
                </div>
            </x-office.form.row>

            <x-office.form.row label="ご住所の国" for="country" required name="country">
                <x-office.form.select name="country" id="country">
                    @foreach ($assign['countries'] as $key => $label)
                        <option value="{{ $key }}" @selected($key === $country)>{{ $label }}</option>
                    @endforeach
                </x-office.form.select>
            </x-office.form.row>

            <x-office.form.row label="郵便番号 / ZIP Code" for="postal_code" required name="postal_code"
                               help="日本はハイフンなしの数字7桁、アメリカは5桁（またはZIP+4の9桁）でご入力ください。">
                <x-office.form.input name="postal_code" id="postal_code" class="castHalfWidthDigit trimSpace"
                                     :value="old('postal_code', $record->postal_code)" />
            </x-office.form.row>

            {{-- 日本の都道府県とアメリカの州は同じprefectureカラムに保存するため、国に応じて入力欄を切り替える --}}
            <div data-country="{{ App\Models\WeddingRsvpModel::COUNTRY_JP }}" @style(['display: none' => $isUnitedStates])>
                <x-office.form.row label="都道府県" for="prefecture" required>
                    <x-office.form.input name="prefecture" id="prefecture" :disabled="$isUnitedStates"
                                         :value="$isUnitedStates ? '' : old('prefecture', $record->prefecture)" />
                </x-office.form.row>
            </div>

            <div data-country="{{ App\Models\WeddingRsvpModel::COUNTRY_US }}" @style(['display: none' => ! $isUnitedStates])>
                <x-office.form.row label="州（State）" for="state" required>
                    <x-office.form.select name="prefecture" id="state" :disabled="! $isUnitedStates">
                        <option value="">未選択</option>
                        @foreach ($assign['states'] as $key => $label)
                            <option value="{{ $key }}" @selected($isUnitedStates && $key === old('prefecture', $record->prefecture))>{{ $label }}</option>
                        @endforeach
                    </x-office.form.select>
                </x-office.form.row>
            </div>

            {{-- 都道府県／州のエラーは入力欄を出し分けているため、外に1つだけ置く --}}
            <div class="md:grid md:grid-cols-12 md:gap-4">
                <div class="md:col-start-4 md:col-end-13">
                    <x-office.form.error name="prefecture" />
                </div>
            </div>

            <x-office.form.row label="市区町村 / City" for="city" required name="city">
                <x-office.form.input name="city" id="city" :value="old('city', $record->city)" />
            </x-office.form.row>

            <x-office.form.row label="番地 / Street Address" for="address" required name="address">
                <x-office.form.input name="address" id="address" :value="old('address', $record->address)" />
            </x-office.form.row>

            <x-office.form.row label="建物名 / Apt / Suite" for="building" name="building">
                <x-office.form.input name="building" id="building" :value="old('building', $record->building)" />
            </x-office.form.row>

            <x-office.form.row label="電話番号" for="phone" required name="phone">
                <x-office.form.input name="phone" id="phone" :value="old('phone', $record->phone)" />
            </x-office.form.row>

            <x-office.form.row label="メールアドレス" for="email" required name="email">
                <x-office.form.input name="email" id="email" class="emailFmt" :value="old('email', $record->email)" />
            </x-office.form.row>

            <x-office.form.row label="アレルギー・お食事のご要望" for="allergy" name="allergy">
                <x-office.form.textarea name="allergy" id="allergy" rows="3">{{ old('allergy', $record->allergy) }}</x-office.form.textarea>
            </x-office.form.row>

            <x-office.form.row label="沖縄への到着日" for="arrival_date" name="arrival_date">
                <x-office.form.datepicker name="arrival_date" id="arrival_date"
                                          :value="old('arrival_date', optional($record->arrival_date)->format('Y/m/d'))" />
            </x-office.form.row>

            <x-office.form.row label="沖縄からの出発日" for="departure_date" name="departure_date">
                <x-office.form.datepicker name="departure_date" id="departure_date"
                                          :value="old('departure_date', optional($record->departure_date)->format('Y/m/d'))" />
            </x-office.form.row>

            <x-office.form.row label="宿泊先ホテル名" for="hotel_name" name="hotel_name">
                <x-office.form.input name="hotel_name" id="hotel_name" :value="old('hotel_name', $record->hotel_name)" />
            </x-office.form.row>

            <x-office.form.row label="当日衣装のサイズ" for="costume_size" name="costume_size">
                <x-office.form.select name="costume_size" id="costume_size">
                    <option value="">未選択</option>
                    @foreach ($assign['costumeSizes'] as $key => $label)
                        <option value="{{ $key }}" @selected($key === old('costume_size', $record->costume_size))>{{ $label }}</option>
                    @endforeach
                </x-office.form.select>
            </x-office.form.row>

            <x-office.form.row label="同伴者の有無" required name="companion_flag">
                <div class="flex flex-wrap gap-x-6 gap-y-2">
                    <x-office.form.check type="radio" name="companion_flag" value="1" id="companion_flag_1"
                                         :checked="$companionFlag === '1'">あり</x-office.form.check>
                    <x-office.form.check type="radio" name="companion_flag" value="0" id="companion_flag_0"
                                         :checked="$companionFlag !== '1'">なし</x-office.form.check>
                </div>
            </x-office.form.row>

            <div id="companionArea" @style(['display: none' => $companionFlag !== '1'])>
                <x-office.form.error name="companions" />

                <div id="companionRows">
                    @foreach ($companions as $index => $companion)
                        @include ('office/wedding-rsvps/edit/companion_row', [
                            'index' => $index,
                            'number' => $loop->iteration,
                            'companion' => $companion,
                            'meals' => $assign['meals'],
                        ])
                    @endforeach
                </div>

                <x-office.button variant="outline-secondary" id="companionAdd">＋ 同伴者を追加</x-office.button>
            </div>

            <x-office.form.row label="新郎新婦へのメッセージ" for="message" name="message" class="mt-3">
                <x-office.form.textarea name="message" id="message" rows="4">{{ old('message', $record->message) }}</x-office.form.textarea>
            </x-office.form.row>

            <x-office.form.row label="楽曲リクエスト" for="song_request" name="song_request">
                <x-office.form.input name="song_request" id="song_request" :value="old('song_request', $record->song_request)" />
            </x-office.form.row>

            <div class="mt-6 space-y-2">
                <x-office.button variant="success" type="submit" id="submit" class="w-full">確認する</x-office.button>
                @if (in_array('officeWeddingRsvpIndex*', Auth::user()->routes()))
                    <x-office.button variant="outline-dark" class="w-full"
                                     :href="route('officeWeddingRsvpIndex', session('officeWeddingRsvpIndexSearchParams'))">前のページに戻る</x-office.button>
                @endif
            </div>
        </form>
    </x-office.card>

    <x-slot:scripts>
        {{-- 「＋ 同伴者を追加」で複製する行のテンプレート（__INDEX__・__NUMBER__はJSが置換する） --}}
        <template id="companionRowTemplate">
            @include ('office/wedding-rsvps/edit/companion_row', [
                'index' => '__INDEX__',
                'number' => '__NUMBER__',
                'companion' => [],
                'meals' => $assign['meals'],
            ])
        </template>

        <script>
            window.weddingRsvpFormConfig = {
                maxCompanions: @json(App\Models\WeddingRsvpCompanionModel::MAX_COUNT),
                companionCount: @json(count($companions)),
                countryJp: @json(App\Models\WeddingRsvpModel::COUNTRY_JP),
                countryUs: @json(App\Models\WeddingRsvpModel::COUNTRY_US),
            };
        </script>
        @vite('resources/js/office/wedding-rsvp-form.js')
    </x-slot:scripts>
</x-office.layout>
