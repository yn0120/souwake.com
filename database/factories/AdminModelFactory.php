<?php

namespace Database\Factories;

use App\Models\AdminModel;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;

/**
 * @extends Factory<AdminModel>
 */
class AdminModelFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'role_id' => fake()->numberBetween(1, 2),
            'login_id' => fake()->unique()->userName(),
            'password' => static::$password ??= Hash::make('password'),
        ];
    }
}
