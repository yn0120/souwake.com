<?php

namespace App\Http\Requests\Rules;

use Illuminate\Contracts\Validation\Rule;

class PasswordRule implements Rule
{
    /**
     * Create a new rule instance.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Determine if the validation rule passes.
     *
     * @param  string $attribute
     * @param  string $value
     * @return bool
     */
    public function passes($attribute, $value): bool
    {
        $allowedSymbols = '!#$%&\-^@;:,.\[\]()+=~';

        $pattern = '/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[' . preg_quote($allowedSymbols, '/') . ']).{8,}$/';

        $invalidPattern = '/[^a-zA-Z\d' . preg_quote($allowedSymbols, '/') . ']/';

        return preg_match($pattern, $value) && !preg_match($invalidPattern, $value);
    }


    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message(): string
    {
        return 'パスワードは半角英数大文字小文字記号をそれぞれ組み合わせて8文字以上で入力してください。';
    }
}
