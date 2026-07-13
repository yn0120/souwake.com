<?php

namespace App\Http\Requests\Office\Auth;

use App\Http\Requests\Rules\PasswordRule;
use Illuminate\Foundation\Http\FormRequest;

class SetPwRequest extends FormRequest
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
            'password' => [
                'bail',
                'required',
                'max:50',
                new PasswordRule(),
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
            '*.max'      => ':max文字以内でご入力ください。',
            '*.required' => '必須項目です。',
        ];
    }
}
