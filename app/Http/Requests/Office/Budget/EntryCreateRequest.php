<?php

namespace App\Http\Requests\Office\Budget;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class EntryCreateRequest extends FormRequest
{
    /**
     * 共有の入出金台帳レイアウト（admin id=2）専用の「区分」選択肢
     */
    public const TRANSFER_TYPE_LABELS = [
        'expense' => '支出',
        'charge' => 'チャージ',
        'settle' => '清算',
    ];

    /**
     * 共有の入出金台帳レイアウト（admin id=2）専用の「利用者」選択肢
     */
    public const MEMBER_LABELS = [
        'yuki' => 'ゆうき',
        'nao' => 'なお',
        'shared' => '共通',
    ];

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
        // admin id=2 は口座・科目を使う家計簿ではなく、共有の入出金台帳として別項目で入力する
        if ((int) Auth::id() === 2) {
            return [
                'occurred_on' => [
                    'bail',
                    'required',
                    'date_format:Ymd',
                ],

                'type' => [
                    'bail',
                    'required',
                    Rule::in(array_keys(self::TRANSFER_TYPE_LABELS)),
                ],

                'content' => [
                    'bail',
                    'required',
                    'max:255',
                ],

                'deposit_amount' => [
                    'bail',
                    'nullable',
                    'integer',
                ],

                'withdrawal_amount' => [
                    'bail',
                    'nullable',
                    'integer',
                ],

                'member' => [
                    'bail',
                    'required',
                    Rule::in(array_keys(self::MEMBER_LABELS)),
                ],

                'memo' => [
                    'bail',
                    'nullable',
                    'max:1000',
                ],
            ];
        }

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
            '*.in'           => '正しい値を選択してください。',
            '*.integer'      => '数値で入力してください。',
            '*.max'          => ':max文字以内でご入力ください。',
            '*.required'     => '必須項目です。',
        ];
    }
}
