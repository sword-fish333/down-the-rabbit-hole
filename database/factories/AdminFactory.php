<?php

namespace Database\Factories;

use App\Models\Admin;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<Admin>
 */
class AdminFactory extends Factory
{
    protected $model = Admin::class;

    protected static ?string $password;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $firstName = fake()->firstName();
        $lastName = fake()->lastName();

        return [
            'name' => $firstName.' '.$lastName,
            'first_name' => $firstName,
            'last_name' => $lastName,
            'email' => fake()->unique()->safeEmail(),
            'phone' => fake()->optional()->phoneNumber(),
            'password' => static::$password ??= Hash::make('password'),
            'role' => fake()->randomElement(Admin::ROLES),
            'login_method' => Admin::CREDENTIALS_LOGIN_METHOD,
            'enabled' => true,
            'remember_token' => Str::random(10),
        ];
    }

    public function disabled(): static
    {
        return $this->state(fn () => ['enabled' => false]);
    }
}
