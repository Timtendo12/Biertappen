<?php

namespace App\Domain\Deck;

use App\Models\Card;
use App\Models\Deck;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Turns a validated external deck document into database rows.
 *
 * Ownership, visibility and status are decided here from the caller's identity —
 * never read from the file. A crafted import can therefore not publish itself
 * into the base game or assign itself to another user.
 */
class DeckImporter
{
    public function __construct(private readonly DeckValidator $validator) {}

    /**
     * @throws DeckValidationException
     */
    public function importJson(string $json, ?User $owner): Deck
    {
        return $this->import($this->validator->validateJson($json), $owner);
    }

    /**
     * @param  array<string, mixed>  $document  Already validated.
     * @param  ?User  $owner  Null creates a base-game deck; only admin callers may pass null.
     */
    public function import(array $document, ?User $owner): Deck
    {
        $payload = $document['deck'];

        return DB::transaction(function () use ($payload, $owner) {
            $deck = Deck::create([
                'name' => $payload['name'],
                'description' => $payload['description'] ?? '',
                'locale' => $payload['locale'] ?? 'nl',
                'tags' => $payload['tags'] ?? [],
                'ending_mode' => $payload['ending']['mode'],
                'ending_count' => $payload['ending']['count'] ?? null,
                // An imported deck always lands private and unpublished. Making it
                // visible is a separate, deliberate action by its owner or an admin.
                'visibility' => Deck::VISIBILITY_PRIVATE,
                'status' => Deck::STATUS_DRAFT,
            ]);

            $deck->owner_id = $owner?->id;
            $deck->save();

            $this->replaceCards($deck, $payload['cards']);

            return $deck->fresh(['cards']);
        });
    }

    /**
     * @param  array<int, array<string, mixed>>  $cards
     */
    public function replaceCards(Deck $deck, array $cards): void
    {
        $deck->cards()->delete();

        $rows = [];
        foreach (array_values($cards) as $position => $card) {
            $targetsEveryone = $card['participants'] === 'all';

            $rows[] = [
                'deck_id' => $deck->id,
                'type' => $card['type'],
                'participants_mode' => $targetsEveryone ? Card::PARTICIPANTS_ALL : Card::PARTICIPANTS_COUNT,
                'participants_count' => $targetsEveryone ? null : (int) $card['participants'],
                'content' => json_encode($card['content'], JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE),
                'position' => $position,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        // One insert regardless of deck size: a 500-card import should not be 500 queries.
        foreach (array_chunk($rows, 200) as $chunk) {
            Card::insert($chunk);
        }
    }
}
