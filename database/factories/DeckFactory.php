<?php

namespace Database\Factories;

use App\Models\Deck;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Deck>
 */
class DeckFactory extends Factory
{
    public function definition(): array
    {
        return [
            'uuid' => (string) Str::uuid(),
            'owner_id' => User::factory(),
            'name' => fake()->words(3, true),
            'description' => fake()->sentence(),
            'locale' => 'nl',
            'visibility' => Deck::VISIBILITY_PRIVATE,
            'status' => Deck::STATUS_DRAFT,
            'featured' => false,
            'ending_mode' => Deck::ENDING_CARDS,
            'ending_count' => 30,
            'tags' => [],
            'schema_version' => 1,
        ];
    }

    /** A published base-game deck: no owner, visible to everyone. */
    public function baseGame(): static
    {
        return $this->state(fn () => [
            'owner_id' => null,
            'visibility' => Deck::VISIBILITY_PUBLIC,
            'status' => Deck::STATUS_PUBLISHED,
        ]);
    }

    public function shared(): static
    {
        return $this->state(fn () => [
            'visibility' => Deck::VISIBILITY_SHARED,
            'status' => Deck::STATUS_PUBLISHED,
            'share_token' => Str::random(32),
        ]);
    }

    public function playAllCardsOnce(): static
    {
        return $this->state(fn () => [
            'ending_mode' => Deck::ENDING_ALL_CARDS_ONCE,
            'ending_count' => null,
        ]);
    }
}
