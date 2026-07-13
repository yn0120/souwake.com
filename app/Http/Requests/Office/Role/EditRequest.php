<?php

namespace App\Http\Requests\Office\Role;

use Illuminate\Foundation\Http\FormRequest;

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
        return [
            'name' => [
                'bail',
                'required',
                'max:100',
            ],

            'note' => [
                'bail',
                'nullable',
                'max:10000',
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
            '*.required'              => '必須項目です。',
            '*.max'                   => ':max文字以内でご入力ください。',
        ];
    }
}
