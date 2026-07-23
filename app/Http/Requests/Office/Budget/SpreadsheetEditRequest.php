<?php

namespace App\Http\Requests\Office\Budget;

use Illuminate\Foundation\Http\FormRequest;

class SpreadsheetEditRequest extends FormRequest
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
            'url' => [
                'bail',
                'nullable',
                'max:2048',
                'regex:/^https:\/\/docs\.google\.com\/spreadsheets\/d\/[a-zA-Z0-9_-]+/',
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
            '*.max'   => ':max文字以内でご入力ください。',
            '*.regex' => 'GoogleスプレッドシートのURL（https://docs.google.com/spreadsheets/d/...）を入力してください。',
        ];
    }
}
