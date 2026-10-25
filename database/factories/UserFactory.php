<?php

namespace Database\Factories;

use App\Enums\Role;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/** @extends Factory<\App\Models\User> */
class UserFactory extends Factory
{
    protected static ?string $password;

    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),
        ];
    }

    public function unverified(): static
    {
        return $this->state(fn () => ['email_verified_at' => null]);
    }

    public function role(Role $role): static
    {
        return $this->state(fn () => ['role' => $role, 'is_active' => true]);
    }

    public function admin(): static
    {
        return $this->role(Role::Admin);
    }

    public function editor(): static
    {
        return $this->role(Role::EditorInChief);
    }

    public function head(): static
    {
        return $this->role(Role::CategoryHead);
    }
}
