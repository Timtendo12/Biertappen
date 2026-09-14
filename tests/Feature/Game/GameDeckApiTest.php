<?php

use App\Models\Card;
use App\Models\Deck;
use App\Models\User;
use Illuminate\Support\Facades\DB;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\getJson;

/*
 * The deck list and play payload are the only endpoints a running game touches,
 * so their visibility rules are where an ownership mistake would leak private
 * decks to every player.
 */

function publishedBaseDeck(): Deck
{
    $deck = Deck::factory()->baseGame()->create(['name' => 'Klassiek']);
    Card::factory()->for($deck)->create();

    return $deck;
}

describe('deck listing', function () {
    it('offers base-game decks to guests', function () {
        publishedBaseDeck();

        getJson('/api/decks')
            ->assertOk()
            ->assertJsonCount(1, 'decks')
            ->assertJsonPath('decks.0.is_base_game', true);
    });

    it('hides unpublished base decks', function () {
        $deck = Deck::factory()->baseGame()->create(['status' => Deck::STATUS_DRAFT]);
        Card::factory()->for($deck)->create();

        getJson('/api/decks')->assertOk()->assertJsonCount(0, 'decks');
    });

    it('hides empty decks, which would start an unplayable game', function () {
        Deck::factory()->baseGame()->create();

        getJson('/api/decks')->assertOk()->assertJsonCount(0, 'decks');
    });

    it('never shows one user the private decks of another', function () {
        $owner = User::factory()->create();
        $deck = Deck::factory()->for($owner, 'owner')->create();
        Card::factory()->for($deck)->create();

        actingAs(User::factory()->create())
            ->getJson('/api/decks')
            ->assertOk()
            ->assertJsonCount(0, 'decks');
    });

    it('shows a user their own private decks', function () {
        $owner = User::factory()->create();
        $deck = Deck::factory()->for($owner, 'owner')->create();
        Card::factory()->for($deck)->create();

        actingAs($owner)
            ->getJson('/api/decks')
            ->assertOk()
            ->assertJsonCount(1, 'decks')
            ->assertJsonPath('decks.0.is_base_game', false);
    });

    it('reports the smallest roster the deck can be played with', function () {
        $deck = Deck::factory()->baseGame()->create();
        Card::factory()->for($deck)->twoPlayers()->create();
        Card::factory()->for($deck)->create(['participants_count' => 4]);

        getJson('/api/decks')->assertOk()->assertJsonPath('decks.0.min_players', 2);
    });

    it('treats an all-players card as needing one person', function () {
        $deck = Deck::factory()->baseGame()->create();
        Card::factory()->for($deck)->everyone()->create();

        getJson('/api/decks')->assertOk()->assertJsonPath('decks.0.min_players', 1);
    });

    it('lists decks in a single query regardless of how many there are', function () {
        foreach (range(1, 5) as $i) {
            $deck = Deck::factory()->baseGame()->create(['name' => "Deck {$i}"]);
            Card::factory()->for($deck)->create();
        }

        $queries = 0;
        DB::listen(function () use (&$queries) {
            $queries++;
        });

        getJson('/api/decks')->assertOk()->assertJsonCount(5, 'decks');

        // One query for the decks (counts and min-players come along as
        // subqueries); anything more means an N+1 crept back in.
        expect($queries)->toBeLessThanOrEqual(2);
    });
});

describe('play payload', function () {
    it('returns the full deck in the shape the engine expects', function () {
        $deck = publishedBaseDeck();

        getJson("/api/decks/{$deck->uuid}/play")
            ->assertOk()
            ->assertJsonStructure([
                'deck' => [
                    'uuid', 'name', 'description', 'locale',
                    'ending' => ['mode', 'count'],
                    'cards' => [['id', 'type', 'participants', 'content']],
                ],
            ]);
    });

    it('omits the count for all_cards_once decks', function () {
        $deck = Deck::factory()->baseGame()->playAllCardsOnce()->create();
        Card::factory()->for($deck)->create();

        getJson("/api/decks/{$deck->uuid}/play")
            ->assertOk()
            ->assertJsonPath('deck.ending.mode', 'all_cards_once')
            ->assertJsonMissingPath('deck.ending.count');
    });

    it('serialises an all-players card as the literal "all"', function () {
        $deck = Deck::factory()->baseGame()->create();
        Card::factory()->for($deck)->everyone()->create();

        getJson("/api/decks/{$deck->uuid}/play")
            ->assertOk()
            ->assertJsonPath('deck.cards.0.participants', 'all');
    });

    it('404s on another user private deck rather than revealing it exists', function () {
        $deck = Deck::factory()->for(User::factory()->create(), 'owner')->create();
        Card::factory()->for($deck)->create();

        actingAs(User::factory()->create())
            ->getJson("/api/decks/{$deck->uuid}/play")
            ->assertNotFound();
    });

    it('404s for a guest on a private deck', function () {
        $deck = Deck::factory()->for(User::factory()->create(), 'owner')->create();
        Card::factory()->for($deck)->create();

        getJson("/api/decks/{$deck->uuid}/play")->assertNotFound();
    });

    it('lets the owner play their own private deck', function () {
        $owner = User::factory()->create();
        $deck = Deck::factory()->for($owner, 'owner')->create();
        Card::factory()->for($deck)->create();

        actingAs($owner)->getJson("/api/decks/{$deck->uuid}/play")->assertOk();
    });

    it('404s on an unknown uuid', function () {
        getJson('/api/decks/00000000-0000-0000-0000-000000000000/play')->assertNotFound();
    });
});
