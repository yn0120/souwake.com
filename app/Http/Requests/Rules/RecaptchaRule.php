<?php

namespace App\Http\Requests\Rules;

use GuzzleHttp\Client;
use Illuminate\Contracts\Validation\Rule;

class RecaptchaRule implements Rule
{
    protected $client;

    protected $response;

    /**
     * Create a new rule instance.
     *
     * @return void
     */
    public function __construct(Client $client)
    {
        $this->client = $client;
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
        $this->response = $this->client->post('https://www.google.com/recaptcha/api/siteverify', [
            'form_params' => [
                'secret'   => config('services.recaptcha.secret_key'),
                'response' => $value,
            ],
        ]);

        return json_decode((string) $this->response->getBody())->success;
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message(): string
    {
        return 'reCaptchaのtokenが認証できません。';
    }
}
