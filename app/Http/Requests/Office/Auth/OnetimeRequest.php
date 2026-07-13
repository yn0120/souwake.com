<?php

namespace App\Http\Requests\Office\Auth;

use Illuminate\Foundation\Http\FormRequest;

class OnetimeRequest extends FormRequest
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
            'onetime_key' => [
                'bail',
                'required',
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
            '*.required' => '必須項目です。',
        ];
    }
}
