<?php

namespace App\Http\Requests\Wedding;

use App\Http\Requests\Rules\KatakanaRule;
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
        return [
            'attendance' => ['bail', 'required', Rule::in(['attending', 'absent'])],

            'name_sei' => ['bail', 'required', 'max:255'],
            'name_mei' => ['bail', 'required', 'max:255'],
            'kana_sei' => ['bail', 'required', 'max:255', new KatakanaRule],
            'kana_mei' => ['bail', 'required', 'max:255', new KatakanaRule],

            'postal_code' => ['bail', 'required', 'regex:/^\d{3}-?\d{4}$/'],
            'prefecture' => ['bail', 'required', 'max:20'],
            'city' => ['bail', 'required', 'max:255'],
            'address' => ['bail', 'required', 'max:255'],
            'building' => ['bail', 'nullable', 'max:255'],

            'phone' => ['bail', 'required', 'regex:/^[0-9-]{10,15}$/'],
            'email' => ['bail', 'required', 'email', 'max:255'],

            'allergy' => ['bail', 'nullable', 'max:1000'],

            'arrival_date' => ['bail', 'nullable', 'date'],
            'departure_date' => ['bail', 'nullable', 'date'],
            'hotel_name' => ['bail', 'nullable', 'max:255'],
            'costume_size' => ['bail', 'nullable', Rule::in(['XS', 'S', 'M', 'L', 'LL', '3L'])],

            'companion_flag' => ['bail', 'required', Rule::in(['1', '0'])],
            'companion_name' => ['bail', 'required_if:companion_flag,1', 'nullable', 'max:255'],
            'companion_kana' => ['bail', 'required_if:companion_flag,1', 'nullable', 'max:255', new KatakanaRule],
            'companion_meal' => ['bail', 'nullable', Rule::in(['adult', 'child_lunch', 'child_plate', 'none'])],
            'child_info' => ['bail', 'nullable', 'max:1000'],

            'message' => ['bail', 'nullable', 'max:2000'],
            'song_request' => ['bail', 'nullable', 'max:255'],

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
        return [
            '*.required' => '必須項目です。',
            '*.required_if' => '必須項目です。',
            '*.max' => ':max文字以内でご入力ください。',
            '*.email' => 'メールアドレスの形式でご入力ください。',
            'postal_code.regex' => '郵便番号はハイフンありなし問わず7桁の数字でご入力ください。',
            'phone.regex' => '電話番号はハイフンを含む半角数字でご入力ください。',
            'contact_note.prohibited' => '不正な送信が検出されました。',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     *
     * @return array
     */
    public function attributes()
    {
        return [
            'attendance' => '出欠',
            'name_sei' => 'お名前（姓）',
            'name_mei' => 'お名前（名）',
            'kana_sei' => 'フリガナ（姓）',
            'kana_mei' => 'フリガナ（名）',
            'postal_code' => '郵便番号',
            'prefecture' => '都道府県',
            'city' => '市区町村',
            'address' => '番地',
            'building' => '建物名',
            'phone' => '電話番号',
            'email' => 'メールアドレス',
            'companion_name' => '同伴者お名前',
            'companion_kana' => '同伴者フリガナ',
        ];
    }
}
