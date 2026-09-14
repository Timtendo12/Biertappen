<?php

namespace Database\Factories;

use App\Models\Deck;
use App\Models\Report;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Report>
 */
class ReportFactory extends Factory
{
    public function definition(): array
    {
        return [
            'deck_id' => Deck::factory()->for(User::factory(), 'owner'),
            'reporter_id' => User::factory(),
            'reason' => fake()->randomElement(Report::REASONS),
            'description' => fake()->sentence(),
            'status' => Report::STATUS_OPEN,
        ];
    }

    /** An anonymous report, which is the common case behind a share link. */
    public function fromGuest(): static
    {
        return $this->state(fn () => ['reporter_id' => null]);
    }
}
