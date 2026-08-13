<?php

namespace App\Http\Requests\Office\WeddingRsvp;

use App\Http\Requests\Rules\KatakanaRule;
use App\Models\WeddingRsvpCompanionModel;
use App\Models\WeddingRsvpModel;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class EditRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        $isUnitedStates = $this->input('country') === WeddingRsvpModel::COUNTRY_US;

        // アメリカ在住のご家族はフリガナを持たないため、アメリカを選んだ場合は任意にする（結婚式サイトのフォームと同じ扱い）
        $kanaRules = $isUnitedStates
            ? ['bail', 'nullable', 'max:255']
            : ['bail', 'required', 'max:255', new KatakanaRule];

        return [
            'attendance' => [
                'bail',
                'required',
                Rule::in(array_keys(WeddingRsvpModel::attendanceOptions())),
            ],

            'name_sei' => ['bail', 'required', 'max:255'],
            'name_mei' => ['bail', 'required', 'max:255'],
            'kana_sei' => $kanaRules,
            'kana_mei' => $kanaRules,

            // 住所は日本・アメリカで書式が異なるため、国に応じて検証を切り替える
            'country' => [
                'bail',
                'required',
                Rule::in(array_keys(WeddingRsvpModel::countryOptions())),
            ],
            'postal_code' => $isUnitedStates
                ? ['bail', 'required', 'regex:/^\d{5}(-?\d{4})?$/']
                : ['bail', 'required', 'regex:/^\d{3}-?\d{4}$/'],
            // アメリカの州は日本の都道府県と同じprefectureカラムに保存している
            'prefecture' => $isUnitedStates
                ? ['bail', 'required', Rule::in(array_keys(WeddingRsvpModel::usStates()))]
                : ['bail', 'required', 'max:20'],
            'city' => ['bail', 'required', 'max:255'],
            'address' => ['bail', 'required', 'max:255'],
            'building' => ['bail', 'nullable', 'max:255'],

            // 国際電話番号（+1 555-123-4567 など）も受け付ける
            'phone' => ['bail', 'required', 'regex:/^[0-9+\-\s()]{10,20}$/'],
            'email' => ['bail', 'required', 'email', 'max:255'],

            'allergy' => ['bail', 'nullable', 'max:1000'],

            'arrival_date' => ['bail', 'nullable', 'date', 'date_format:Y/m/d'],
            'departure_date' => ['bail', 'nullable', 'date', 'date_format:Y/m/d'],
            'hotel_name' => ['bail', 'nullable', 'max:255'],
            'costume_size' => [
                'bail',
                'nullable',
                Rule::in(array_keys(WeddingRsvpModel::costumeSizeOptions())),
            ],

            // 同伴者は連名（複数）で受け取る。「同伴者なし」を選んだ場合は行が送られてきても丸ごと無視する。
            'companion_flag' => ['bail', 'required', Rule::in(['1', '0'])],
            'companions' => ['bail', 'exclude_if:companion_flag,0', 'required', 'array', 'min:1', 'max:'.WeddingRsvpCompanionModel::MAX_COUNT],
            // 既存行はidを持って送られてくる（空なら新規行）
            'companions.*.id' => ['bail', 'exclude_if:companion_flag,0', 'nullable', 'integer'],
            'companions.*.name_sei' => ['bail', 'exclude_if:companion_flag,0', 'required', 'max:255'],
            'companions.*.name_mei' => ['bail', 'exclude_if:companion_flag,0', 'required', 'max:255'],
            'companions.*.kana_sei' => array_merge(['bail', 'exclude_if:companion_flag,0'], array_slice($kanaRules, 1)),
            'companions.*.kana_mei' => array_merge(['bail', 'exclude_if:companion_flag,0'], array_slice($kanaRules, 1)),
            'companions.*.meal' => ['bail', 'exclude_if:companion_flag,0', 'nullable', Rule::in(array_keys(WeddingRsvpCompanionModel::mealOptions()))],
            'companions.*.child_info' => ['bail', 'exclude_if:companion_flag,0', 'nullable', 'max:1000'],

            'message' => ['bail', 'nullable', 'max:2000'],
            'song_request' => ['bail', 'nullable', 'max:255'],
        ];
    }

    /**
     * Get the validation error messages.
     *
     * @return array
     */
    public function messages()
    {
        // Laravelはこの配列を宣言順に走査し、先に一致したものを採用する。
        // '*.required' などのワイルドカードより後ろに個別指定を置くと打ち消されるため、個別指定を先に書く。
        return [
            'companions.required' => '同伴者「あり」の場合は、同伴者を1名以上ご入力ください。',
            'companions.max' => '同伴者は:max名までご入力いただけます。',
            'postal_code.regex' => $this->input('country') === WeddingRsvpModel::COUNTRY_US
                ? 'ZIP Codeは5桁（またはZIP+4の9桁）の数字でご入力ください。'
                : '郵便番号はハイフンなしの数字7桁でご入力ください。',
            'phone.regex' => '電話番号は半角数字（＋・ハイフン・括弧を含む）でご入力ください。',
            'arrival_date.*' => '2000/01/01〜9999/12/31の日付を入力してください。',
            'departure_date.*' => '2000/01/01〜9999/12/31の日付を入力してください。',
            '*.email' => 'メールアドレスが正しくありません。',
            '*.in' => '正しい値を入力または選択してください。',
            '*.max' => ':max文字以内でご入力ください。',
            '*.required' => '必須項目です。',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     *
     * @return array
     */
    public function attributes()
    {
        // 同伴者は行ごとにエラーが出るため、「2人目の同伴者お名前（姓）」のように何人目かが分かる名前を付ける
        $companionAttributes = [];
        $companionLabels = [
            'name_sei' => '同伴者お名前（姓）',
            'name_mei' => '同伴者お名前（名）',
            'kana_sei' => '同伴者フリガナ（姓）',
            'kana_mei' => '同伴者フリガナ（名）',
            'meal' => '同伴者のお食事',
            'child_info' => 'お子様連れの場合の追加情報',
        ];
        foreach (array_keys((array) $this->input('companions', [])) as $index) {
            $number = is_numeric($index) ? ((int) $index + 1) : 1;
            foreach ($companionLabels as $key => $label) {
                $companionAttributes["companions.{$index}.{$key}"] = "{$number}人目の{$label}";
            }
        }

        return $companionAttributes + [
            'attendance' => '出欠',
            'name_sei' => 'お名前（姓）',
            'name_mei' => 'お名前（名）',
            'kana_sei' => 'フリガナ（姓）',
            'kana_mei' => 'フリガナ（名）',
            'country' => 'ご住所の国',
            'postal_code' => $this->input('country') === WeddingRsvpModel::COUNTRY_US ? 'ZIP Code' : '郵便番号',
            'prefecture' => $this->input('country') === WeddingRsvpModel::COUNTRY_US ? '州（State）' : '都道府県',
            'city' => $this->input('country') === WeddingRsvpModel::COUNTRY_US ? 'City' : '市区町村',
            'address' => $this->input('country') === WeddingRsvpModel::COUNTRY_US ? 'Street Address' : '番地',
            'building' => $this->input('country') === WeddingRsvpModel::COUNTRY_US ? 'Apt / Suite' : '建物名',
            'phone' => '電話番号',
            'email' => 'メールアドレス',
            'allergy' => 'アレルギー・お食事のご要望',
            'arrival_date' => '沖縄への到着日',
            'departure_date' => '沖縄からの出発日',
            'hotel_name' => '宿泊先ホテル名',
            'costume_size' => '当日衣装のサイズ',
            'companion_flag' => '同伴者の有無',
            'companions' => '同伴者',
            'message' => '新郎新婦へのメッセージ',
            'song_request' => '楽曲リクエスト',
        ];
    }
}
