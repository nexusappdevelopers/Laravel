<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use App\Models\Role;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $adminRole = Role::where('name', 'admin')->first();
        $managerRole = Role::where('name', 'manager')->first();
        $userRole = Role::where('name', 'user')->first();

        // Create admin user
        User::create([
            'first_name' => 'Super',
            'last_name' => 'Admin',
            'email' => 'admin@laravel-showcase.com',
            'password' => Hash::make('admin123'),
            'phone' => '+1 (555) 123-4567',
            'date_of_birth' => '1985-01-15',
            'gender' => 'other',
            'bio' => 'System administrator with full access to all features.',
            'is_active' => true,
            'email_verified_at' => now(),
        ])->assignRole($adminRole);

        // Create manager users
        for ($i = 1; $i <= 3; $i++) {
            User::create([
                'first_name' => fake()->firstName(),
                'last_name' => fake()->lastName(),
                'email' => "manager{$i}@laravel-showcase.com",
                'password' => Hash::make('manager123'),
                'phone' => fake()->phoneNumber(),
                'date_of_birth' => fake()->dateTimeBetween('-40 years', '-25 years')->format('Y-m-d'),
                'gender' => fake()->randomElement(['male', 'female']),
                'bio' => fake()->sentence(10),
                'is_active' => true,
                'email_verified_at' => now(),
            ])->assignRole($managerRole);
        }

        // Create regular users
        for ($i = 1; $i <= 20; $i++) {
            User::create([
                'first_name' => fake()->firstName(),
                'last_name' => fake()->lastName(),
                'email' => "user{$i}@laravel-showcase.com",
                'password' => Hash::make('password123'),
                'phone' => fake()->phoneNumber(),
                'date_of_birth' => fake()->dateTimeBetween('-50 years', '-18 years')->format('Y-m-d'),
                'gender' => fake()->randomElement(['male', 'female', 'other']),
                'bio' => fake()->sentence(8),
                'is_active' => true,
                'email_verified_at' => now(),
            ])->assignRole($userRole);
        }

        $this->command->info('Users created successfully.');
    }
}
