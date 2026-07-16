<?php

namespace App\Http\Requests\Office\PasswordManager;

use App\Enums\PasswordItemType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ItemRequest extends FormRequest
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
            'label' => [
                'bail',
                'required',
                'max:255',
            ],

            'type' => [
                'bail',
                'required',
                Rule::in(PasswordItemType::getKeys()),
            ],

            'value' => [
                'bail',
                'nullable',
                'string',
            ],

            'display_order' => [
                'bail',
                'nullable',
                'integer',
            ],
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
            '*.in'       => '正しい値を入力または選択してください。',
            '*.integer'  => '数値で入力してください。',
            '*.max'      => ':max文字以内でご入力ください。',
            '*.required' => '必須項目です。',
        ];
    }
}
