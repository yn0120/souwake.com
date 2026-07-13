<?php

namespace App\Http\Requests\Rules;

use Illuminate\Contracts\Validation\Rule;

class OddRule implements Rule
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
     * @param  mixed  $value
     * @return bool
     */
    public function passes($attribute, $value): bool
    {
        return ctype_digit($value) && $value % 2 === 1;
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message()
    {
        return '奇数でご入力ください。';
    }
}
