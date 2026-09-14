<?php

namespace Database\Seeders;

use App\Domain\Deck\DeckImporter;
use App\Models\Deck;
use Illuminate\Database\Seeder;

/**
 * Seeds the base game from the same JSON files an administrator would import.
 *
 * Going through DeckImporter rather than writing rows directly means the seed
 * data is validated against the public schema on every run — if the contract and
 * the shipped decks ever disagree, seeding fails loudly instead of quietly
 * producing decks no export could reproduce.
 */
class BaseDeckSeeder extends Seeder
{
    public function run(): void
    {
        $importer = app(DeckImporter::class);

        foreach (glob(__DIR__.'/decks/*.json') ?: [] as $path) {
            $json = file_get_contents($path);

            if ($json === false) {
                continue;
            }

            $name = json_decode($json, true)['deck']['name'] ?? null;

            // Idempotent: re-running the seeder refreshes rather than duplicates.
            Deck::withTrashed()->baseGame()->where('name', $name)->forceDelete();

            $deck = $importer->importJson($json, owner: null);

            $deck->update([
                'visibility' => Deck::VISIBILITY_PUBLIC,
                'status' => Deck::STATUS_PUBLISHED,
                'featured' => true,
            ]);

            $this->command?->info("Seeded base deck: {$deck->name} ({$deck->cards()->count()} cards)");
        }
    }
}
