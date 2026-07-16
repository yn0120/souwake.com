<?php

namespace App\Http\Requests\Office\PasswordManager;

use App\Enums\PasswordItemType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class EntryCreateRequest extends FormRequest
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
            'name' => [
                'bail',
                'required',
                'max:255',
            ],

            'items' => [
                'bail',
                'array',
            ],

            'items.*.label' => [
                'bail',
                'nullable',
                'max:255',
            ],

            'items.*.type' => [
                'bail',
                'nullable',
                Rule::in(PasswordItemType::getKeys()),
            ],

            'items.*.value' => [
                'bail',
                'nullable',
                'string',
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
            '*.max'      => ':max文字以内でご入力ください。',
            '*.required' => '必須項目です。',
        ];
    }
}
