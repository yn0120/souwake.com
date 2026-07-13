<?php

namespace App\Http\Requests\Office\Admin;

use App\Http\Requests\Rules\PasswordRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

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

            'password' => [
                'bail',
                'nullable',
                'max:50',
                new PasswordRule(),
            ],

            'login_locked_at' => [
                'bail',
                'nullable',
            ],

            'terminated_at' => [
                'bail',
                'nullable',
                'date',
                'date_format:Y/m/d',
                'after_or_equal:2000-01-01',
                'before_or_equal:9999-12-31',
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
            'terminated_at.*' => '2000/01/01〜9999/12/31の日付を入力してください。',
            '*.email'         => 'メールアドレスが正しくありません。',
            '*.in'            => '正しい値を入力または選択してください。',
            '*.max'           => ':max文字以内でご入力ください。',
            '*.required'      => '必須項目です。',
        ];
    }
}
