<?php

namespace App\Http\Requests\Office\Budget;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
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
            'occurred_on' => [
                'bail',
                'required',
                'date_format:Ymd',
            ],

            'amount' => [
                'bail',
                'required',
                'integer',
            ],

            'account_id' => [
                'bail',
                'required',
                Rule::exists('budget_accounts', 'id')->where(function ($query) {
                    $query->where('admin_id', Auth::id())->whereNull('deleted_at');
                }),
            ],

            'category_id' => [
                'bail',
                'required',
                Rule::exists('budget_categories', 'id')->where(function ($query) {
                    $query->where('admin_id', Auth::id())->whereNull('deleted_at');
                }),
            ],

            'memo' => [
                'bail',
                'nullable',
                'max:255',
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
            '*.date_format' => 'YYYYMMDD形式（例: 20260716）で入力してください。',
            '*.exists'       => '正しい値を選択してください。',
            '*.integer'      => '数値で入力してください。',
            '*.max'          => ':max文字以内でご入力ください。',
            '*.required'     => '必須項目です。',
        ];
    }
}
