<?php

namespace App\Http\Requests\Office\Profile;

use App\Http\Requests\Rules\PasswordRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class UpdateRequest extends FormRequest
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

            'email' => [
                'bail',
                'required',
                'max:200',
                'email:rfc,dns',
                Rule::unique('admins', 'email')->ignore(Auth::id())->whereNull('deleted_at'),
            ],

            'new_password' => [
                'bail',
                'nullable',
                'max:50',
                new PasswordRule(),
            ],

            'service_account_json' => [
                'bail',
                'nullable',
                'file',
                'max:100',
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
            '*.email'            => 'メールアドレスが正しくありません。',
            '*.file'             => '正しいファイルを選択してください。',
            '*.max'              => ':max文字（KB）以内でご入力・アップロードください。',
            '*.required'         => '必須項目です。',
            '*.unique'           => '既に同じメールアドレスが登録済みです。',
        ];
    }
}
