<?php

namespace App\Http\Requests\Rules;

use Illuminate\Contracts\Validation\Rule;

class StringRule implements Rule
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
        return preg_match('/(http|javascript|<|>)/', $value);
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message(): string
    {
        return '特殊文字は使用できません。';
    }
}
