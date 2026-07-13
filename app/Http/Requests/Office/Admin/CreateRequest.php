<?php

namespace App\Http\Requests\Office\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class CreateRequest extends FormRequest
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

            'role_id' => [
                'bail',
                'required',
                Rule::in(DB::table('roles')->whereNull('deleted_at')->pluck('id')->toArray()),
            ],

            'email' => [
                'bail',
                'required',
                'max:200',
                'email:rfc,dns',
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
            '*.email'    => 'メールアドレスが正しくありません。',
            '*.in'       => '正しい値を入力または選択してください。',
            '*.max'      => ':max文字以内でご入力ください。',
            '*.required' => '必須項目です。',
        ];
    }
}
