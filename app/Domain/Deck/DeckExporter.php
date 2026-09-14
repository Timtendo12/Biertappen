<?php

namespace App\Domain\Deck;

use App\Models\Card;
use App\Models\Deck;

/**
 * Renders a deck as the public interchange format.
 *
 * Deliberately omits every internal identifier — database ids, owner, share
 * token, timestamps — so an exported file carries nothing an importer elsewhere
 * would have to ignore, and nothing the owner did not mean to publish.
 */
class DeckExporter
{
    /** @return array<string, mixed> */
    public function toArray(Deck $deck): array
    {
        $deck->loadMissing('cards');

        $ending = ['mode' => $deck->ending_mode];

        if ($deck->ending_mode === Deck::ENDING_CARDS) {
            $ending['count'] = (int) $deck->ending_count;
        }

        $payload = [
            'name' => $deck->name,
            'description' => (string) $deck->description,
            'locale' => $deck->locale,
        ];

        if (! empty($deck->tags)) {
            $payload['tags'] = array_values($deck->tags);
        }

        $payload['ending'] = $ending;
        $payload['cards'] = $deck->cards->map(fn (Card $card) => $this->card($card))->all();

        return [
            'format' => DeckValidator::FORMAT,
            'version' => DeckValidator::VERSION,
            'deck' => $payload,
        ];
    }

    public function toJson(Deck $deck): string
    {
        return json_encode(
            $this->toArray($deck),
            JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR,
        );
    }

    public function filename(Deck $deck): string
    {
        $slug = preg_replace('/[^a-z0-9]+/', '-', mb_strtolower($deck->name)) ?: 'deck';

        return trim($slug, '-').'.biertappen.json';
    }

    /** @return array<string, mixed> */
    private function card(Card $card): array
    {
        return [
            'type' => $card->type,
            'participants' => $card->targetsEveryone() ? 'all' : (int) $card->participants_count,
            'content' => $card->content,
        ];
    }
}
