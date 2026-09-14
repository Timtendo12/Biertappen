<?php

namespace Database\Factories;

use App\Models\Entitlement;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
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
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'role' => User::ROLE_USER,
            'locale' => 'nl',
            'remember_token' => Str::random(10),
        ];
    }

    public function admin(): static
    {
        return $this->state(fn () => ['role' => User::ROLE_ADMIN]);
    }

    /** A Google-only account: no password was ever set. */
    public function oauthOnly(): static
    {
        return $this->state(fn () => ['password' => null]);
    }

    /** Gives the user active deck-creator access. */
    public function withDeckCreator(): static
    {
        return $this->afterCreating(function (User $user) {
            $user->entitlements()->create([
                'type' => Entitlement::TYPE_DECK_CREATOR,
                'status' => Entitlement::STATUS_ACTIVE,
                'source' => Entitlement::SOURCE_MANUAL,
                'granted_at' => now(),
            ]);
        });
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }
}
