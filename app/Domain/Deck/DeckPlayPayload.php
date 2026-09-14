<?php

namespace App\Domain\Deck;

use App\Models\Card;
use App\Models\Deck;

/**
 * The shape the TypeScript engine consumes.
 *
 * Distinct from the export format on purpose: this one carries card ids (the
 * engine needs stable identity to track what it has already dealt) and is
 * fetched exactly once per game, so every later card transition is local.
 */
class DeckPlayPayload
{
    /** @return array<string, mixed> */
    public function forGame(Deck $deck): array
    {
        $deck->loadMissing('cards');

        $ending = ['mode' => $deck->ending_mode];

        if ($deck->ending_mode === Deck::ENDING_CARDS) {
            $ending['count'] = (int) $deck->ending_count;
        }

        return [
            'uuid' => $deck->uuid,
            'name' => $deck->name,
            'description' => (string) $deck->description,
            'locale' => $deck->locale,
            'ending' => $ending,
            'cards' => $deck->cards->map(fn (Card $card) => [
                'id' => (string) $card->id,
                'type' => $card->type,
                'participants' => $card->targetsEveryone() ? 'all' : (int) $card->participants_count,
                'content' => $card->content,
            ])->all(),
        ];
    }

    /**
     * Summary for the deck-selection screen. Deliberately excludes card content:
     * browsing a list of decks should not download every card in all of them.
     *
     * @return array<string, mixed>
     */
    public function forList(Deck $deck): array
    {
        return [
            'uuid' => $deck->uuid,
            'name' => $deck->name,
            'description' => (string) $deck->description,
            'locale' => $deck->locale,
            'tags' => $deck->tags ?? [],
            'featured' => (bool) $deck->featured,
            'is_base_game' => $deck->isSystemDeck(),
            'card_count' => (int) ($deck->cards_count ?? $deck->cards()->count()),
            'min_players' => $this->minimumPlayers($deck),
            'ending' => [
                'mode' => $deck->ending_mode,
                'count' => $deck->ending_mode === Deck::ENDING_CARDS ? (int) $deck->ending_count : null,
            ],
        ];
    }

    /**
     * Smallest roster that can play anything in this deck, so the selection
     * screen can say "needs 4 players" before the group commits to it.
     *
     * Prefers the value the list query already selected (see
     * Deck::scopeWithMinimumPlayers) and only falls back to its own query when
     * the deck was loaded without it.
     */
    private function minimumPlayers(Deck $deck): int
    {
        if (isset($deck->min_players)) {
            return max(1, (int) $deck->min_players);
        }

        return max(1, (int) (Card::minimumPlayersSubquery()
            ->where('deck_id', $deck->id)
            ->value('min_players') ?? 1));
    }
}
