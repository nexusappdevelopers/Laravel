<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\User>
 */
class UserFactory extends Factory
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
            'first_name' => fake()->firstName(),
            'last_name' => fake()->lastName(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'phone' => fake()->phoneNumber(),
            'date_of_birth' => fake()->dateTimeBetween('-50 years', '-18 years')->format('Y-m-d'),
            'gender' => fake()->randomElement(['male', 'female', 'other']),
            'bio' => fake()->sentence(10),
            'is_active' => true,
            'remember_token' => Str::random(10),
        ];
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => array_merge($attributes, [
            'email_verified_at' => null,
        ]));
    }

    /**
     * Indicate that the user is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => array_merge($attributes, [
            'is_active' => false,
        ]));
    }

    /**
     * Indicate that the user is an admin.
     */
    public function admin(): static
    {
        return $this->state(fn (array $attributes) => array_merge($attributes, [
            'first_name' => 'Admin',
            'last_name' => 'User',
            'email' => 'admin@laravel-showcase.com',
        ]));
    }

    /**
     * Indicate that the user is a project manager.
     */
    public function projectManager(): static
    {
        return $this->state(fn (array $attributes) => array_merge($attributes, [
            'first_name' => 'Project',
            'last_name' => 'Manager',
            'email' => 'manager@laravel-showcase.com',
        ]));
    }

    /**
     * Indicate that the user is a client.
     */
    public function client(): static
    {
        return $this->state(fn (array $attributes) => array_merge($attributes, [
            'first_name' => 'Client',
            'last_name' => 'User',
            'email' => 'client@laravel-showcase.com',
        ]));
    }

    /**
     * Create a user with a specific password.
     */
    public function withPassword(string $password): static
    {
        static::$password = $password;
        
        return $this->state();
    }
}
