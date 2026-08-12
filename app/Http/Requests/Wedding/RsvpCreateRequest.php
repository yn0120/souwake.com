<?php

namespace App\Http\Requests\Wedding;

use App\Http\Controllers\Wedding\WeddingRsvpPhotoController;
use App\Http\Requests\Rules\KatakanaRule;
use App\Models\WeddingRsvpCompanionModel;
use App\Models\WeddingRsvpModel;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RsvpCreateRequest extends FormRequest
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

        // アメリカ在住のご家族はフリガナを書けないことがあるため、アメリカを選んだ場合は任意にする
        $kanaRules = $isUnitedStates
            ? ['bail', 'nullable', 'max:255']
            : ['bail', 'required', 'max:255', new KatakanaRule];

        return [
            'attendance' => ['bail', 'required', Rule::in(['attending', 'absent'])],

            'name_sei' => ['bail', 'required', 'max:255'],
            'name_mei' => ['bail', 'required', 'max:255'],
            'kana_sei' => $kanaRules,
            'kana_mei' => $kanaRules,

            // 住所は日本・アメリカで書式が異なるため、国に応じて検証を切り替える
            'country' => ['bail', 'required', Rule::in(array_keys(WeddingRsvpModel::countryOptions()))],
            'postal_code' => $isUnitedStates
                ? ['bail', 'required', 'regex:/^\d{5}(-?\d{4})?$/']
                : ['bail', 'required', 'regex:/^\d{3}-?\d{4}$/'],
            'prefecture' => $isUnitedStates
                ? ['bail', 'nullable', 'max:20']
                : ['bail', 'required', 'max:20'],
            'state' => $isUnitedStates
                ? ['bail', 'required', Rule::in(array_keys(WeddingRsvpModel::usStates()))]
                : ['bail', 'nullable'],
            'city' => ['bail', 'required', 'max:255'],
            'address' => ['bail', 'required', 'max:255'],
            'building' => ['bail', 'nullable', 'max:255'],

            // 国際電話番号（+1 555-123-4567 など）も受け付ける
            'phone' => ['bail', 'required', 'regex:/^[0-9+\-\s()]{10,20}$/'],
            'email' => ['bail', 'required', 'email', 'max:255'],

            'allergy' => ['bail', 'nullable', 'max:1000'],

            'arrival_date' => ['bail', 'nullable', 'date'],
            'departure_date' => ['bail', 'nullable', 'date'],
            'hotel_name' => ['bail', 'nullable', 'max:255'],
            'costume_size' => ['bail', 'nullable', Rule::in(['XS', 'S', 'M', 'L', 'LL', '3L'])],

            // 同伴者は連名（複数）で受け取る。「同伴者なし」を選んだ場合は行が送られてきても丸ごと無視する。
            'companion_flag' => ['bail', 'required', Rule::in(['1', '0'])],
            'companions' => ['bail', 'exclude_if:companion_flag,0', 'required', 'array', 'min:1', 'max:'.WeddingRsvpCompanionModel::MAX_COUNT],
            'companions.*.name_sei' => ['bail', 'exclude_if:companion_flag,0', 'required', 'max:255'],
            'companions.*.name_mei' => ['bail', 'exclude_if:companion_flag,0', 'required', 'max:255'],
            'companions.*.kana_sei' => array_merge(['bail', 'exclude_if:companion_flag,0'], array_slice($kanaRules, 1)),
            'companions.*.kana_mei' => array_merge(['bail', 'exclude_if:companion_flag,0'], array_slice($kanaRules, 1)),
            'companions.*.meal' => ['bail', 'exclude_if:companion_flag,0', 'nullable', Rule::in(array_keys(WeddingRsvpCompanionModel::mealOptions()))],
            'companions.*.child_info' => ['bail', 'exclude_if:companion_flag,0', 'nullable', 'max:1000'],

            'message' => ['bail', 'nullable', 'max:2000'],
            'song_request' => ['bail', 'nullable', 'max:255'],

            // お祝い画像。実体はフォーム送信前に非同期アップロード済みで、
            // ここではアップロード済み画像のuuidと、アップロード元ブラウザのトークンのみを受け取る。
            'photo_session_token' => ['bail', 'nullable', 'string', 'regex:/^[0-9a-fA-F-]{36}$/'],
            'photo_tokens' => ['bail', 'nullable', 'array', 'max:'.WeddingRsvpPhotoController::MAX_FILES_PER_SESSION],
            'photo_tokens.*' => ['bail', 'string', 'regex:/^[0-9a-fA-F-]{36}$/'],

            // スパム対策のハニーポット（人間には見えない項目。値が入っていたら弾く）
            'contact_note' => ['bail', 'prohibited'],
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
            'photo_tokens.max' => 'お祝い画像は:max枚までアップロードいただけます。',
            'photo_tokens.*.regex' => 'お祝い画像の情報が不正です。お手数ですが画像を選び直してください。',
            'photo_session_token.regex' => 'お祝い画像の情報が不正です。お手数ですが画像を選び直してください。',
            'postal_code.regex' => $this->input('country') === WeddingRsvpModel::COUNTRY_US
                ? 'ZIP Codeは5桁（またはZIP+4の9桁）の数字でご入力ください。'
                : '郵便番号はハイフンなしの数字7桁でご入力ください。',
            'phone.regex' => '電話番号は半角数字（＋・ハイフン・括弧を含む）でご入力ください。',
            'state.required' => '必須項目です。',
            'state.in' => '州の選択が不正です。',
            'contact_note.prohibited' => '不正な送信が検出されました。',
            '*.required' => '必須項目です。',
            '*.required_if' => '必須項目です。',
            '*.max' => ':max文字以内でご入力ください。',
            '*.email' => 'メールアドレスの形式でご入力ください。',
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
            'prefecture' => '都道府県',
            'state' => '州（State）',
            'city' => $this->input('country') === WeddingRsvpModel::COUNTRY_US ? 'City' : '市区町村',
            'address' => $this->input('country') === WeddingRsvpModel::COUNTRY_US ? 'Street Address' : '番地',
            'building' => $this->input('country') === WeddingRsvpModel::COUNTRY_US ? 'Apt / Suite' : '建物名',
            'phone' => '電話番号',
            'email' => 'メールアドレス',
            'companions' => '同伴者',
            'photo_tokens' => 'お祝い画像',
        ];
    }
}
