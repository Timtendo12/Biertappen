<?php

namespace Database\Factories;

use App\Models\Card;
use App\Models\Deck;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Card>
 */
class CardFactory extends Factory
{
    public function definition(): array
    {
        return [
            'deck_id' => Deck::factory(),
            'type' => 'drinking',
            'participants_mode' => Card::PARTICIPANTS_COUNT,
            'participants_count' => 1,
            'content' => [
                ['type' => 'variable', 'value' => 'player1'],
                ['type' => 'text', 'value' => ' neemt '],
                ['type' => 'variable', 'value' => 'amount', 'options' => ['mode' => 'range', 'min' => 1, 'max' => 5]],
                ['type' => 'text', 'value' => ' slokken.'],
            ],
            'position' => 0,
        ];
    }

    /** A card needing two distinct players. */
    public function twoPlayers(): static
    {
        return $this->state(fn () => [
            'type' => 'challenge',
            'participants_mode' => Card::PARTICIPANTS_COUNT,
            'participants_count' => 2,
            'content' => [
                ['type' => 'variable', 'value' => 'player1'],
                ['type' => 'text', 'value' => ' kiest '],
                ['type' => 'variable', 'value' => 'player2'],
                ['type' => 'text', 'value' => ' om te drinken.'],
            ],
        ]);
    }

    public function everyone(): static
    {
        return $this->state(fn () => [
            'type' => 'drinking',
            'participants_mode' => Card::PARTICIPANTS_ALL,
            'participants_count' => null,
            'content' => [
                ['type' => 'variable', 'value' => 'all_players'],
                ['type' => 'text', 'value' => ' nemen 2 slokken.'],
            ],
        ]);
    }
}
