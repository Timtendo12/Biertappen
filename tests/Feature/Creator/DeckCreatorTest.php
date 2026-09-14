<?php

use App\Models\Card;
use App\Models\Deck;
use App\Models\User;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\delete;
use function Pest\Laravel\get;
use function Pest\Laravel\post;
use function Pest\Laravel\put;

/*
 * The deck creator is the one place where users write data other users might
 * reach. These cover the two things that would actually hurt: someone reaching
 * a deck that is not theirs, and someone creating one without paying.
 */

function creator(): User
{
    return User::factory()->withDeckCreator()->create();
}

function deckFor(User $user, array $attributes = []): Deck
{
    return Deck::factory()->for($user, 'owner')->create($attributes);
}

function validCard(array $overrides = []): array
{
    return array_replace([
        'type' => 'drinking',
        'participants' => 1,
        'content' => [
            ['type' => 'variable', 'value' => 'player1'],
            ['type' => 'text', 'value' => ' neemt 3 slokken.'],
        ],
    ], $overrides);
}

describe('the premium gate', function () {
    it('turns guests away', function () {
        get('/creator')->assertRedirect('/login');
    });

    it('refuses a signed-in user without the entitlement', function () {
        actingAs(User::factory()->create())->get('/creator')->assertForbidden();
    });

    it('refuses deck creation without the entitlement', function () {
        actingAs(User::factory()->create())
            ->post('/creator/decks', ['name' => 'Smokkel', 'ending_mode' => 'all_cards_once'])
            ->assertForbidden();

        expect(Deck::count())->toBe(0);
    });

    it('lets an entitled user in', function () {
        actingAs(creator())->get('/creator')->assertOk();
    });

    it('lets an administrator in without a purchase', function () {
        actingAs(User::factory()->admin()->create())->get('/creator')->assertOk();
    });
});

describe('creating decks', function () {
    it('assigns ownership from the session, never the payload', function () {
        $user = creator();
        $someoneElse = User::factory()->create();

        actingAs($user)->post('/creator/decks', [
            'name' => 'Mijn deck',
            'ending_mode' => 'cards',
            'ending_count' => 20,
            // Both ignored: neither is a writable field.
            'owner_id' => $someoneElse->id,
            'status' => Deck::STATUS_PUBLISHED,
        ])->assertRedirect();

        $deck = Deck::sole();

        expect($deck->owner_id)->toBe($user->id)
            ->and($deck->status)->toBe(Deck::STATUS_DRAFT)
            ->and($deck->visibility)->toBe(Deck::VISIBILITY_PRIVATE);
    });

    it('cannot create a base-game deck', function () {
        actingAs(creator())->post('/creator/decks', [
            'name' => 'Nep basisspel',
            'ending_mode' => 'all_cards_once',
            'owner_id' => null,
        ])->assertRedirect();

        // A base deck is one with no owner. Ours always has one.
        expect(Deck::whereNull('owner_id')->count())->toBe(0);
    });

    it('requires a card count in cards mode', function () {
        actingAs(creator())
            ->post('/creator/decks', ['name' => 'X', 'ending_mode' => 'cards'])
            ->assertSessionHasErrors('ending_count');
    });

    it('clears a stale count when switching to all_cards_once', function () {
        $user = creator();
        $deck = deckFor($user, ['ending_mode' => 'cards', 'ending_count' => 30]);

        actingAs($user)->put("/creator/decks/{$deck->uuid}", [
            'name' => $deck->name,
            'ending_mode' => 'all_cards_once',
            'ending_count' => 30,
        ])->assertRedirect();

        // Carrying it over would export JSON the schema rejects.
        expect($deck->fresh()->ending_count)->toBeNull();
    });
});

describe('deck ownership', function () {
    it('does not let one user open another private deck', function () {
        $deck = deckFor(User::factory()->create());

        actingAs(creator())->get("/creator/decks/{$deck->uuid}")->assertForbidden();
    });

    it('does not let one user edit another deck', function () {
        $deck = deckFor(User::factory()->create(), ['name' => 'Origineel']);

        actingAs(creator())
            ->put("/creator/decks/{$deck->uuid}", ['name' => 'Gekaapt', 'ending_mode' => 'all_cards_once'])
            ->assertForbidden();

        expect($deck->fresh()->name)->toBe('Origineel');
    });

    it('does not let one user delete another deck', function () {
        $deck = deckFor(User::factory()->create());

        actingAs(creator())->delete("/creator/decks/{$deck->uuid}")->assertForbidden();

        expect(Deck::withTrashed()->whereNull('deleted_at')->count())->toBe(1);
    });

    it('does not let a user edit a base-game deck', function () {
        $deck = Deck::factory()->baseGame()->create(['name' => 'Klassiek']);

        actingAs(creator())
            ->put("/creator/decks/{$deck->uuid}", ['name' => 'Gekaapt', 'ending_mode' => 'all_cards_once'])
            ->assertForbidden();

        expect($deck->fresh()->name)->toBe('Klassiek');
    });

    it('lets administrators edit base-game decks', function () {
        $deck = Deck::factory()->baseGame()->create();

        actingAs(User::factory()->admin()->create())
            ->put("/creator/decks/{$deck->uuid}", ['name' => 'Bijgewerkt', 'ending_mode' => 'all_cards_once'])
            ->assertRedirect();

        expect($deck->fresh()->name)->toBe('Bijgewerkt');
    });
});

describe('cards', function () {
    it('adds a card and appends it to the end', function () {
        $user = creator();
        $deck = deckFor($user);
        Card::factory()->for($deck)->create(['position' => 0]);

        actingAs($user)->post("/creator/decks/{$deck->uuid}/cards", validCard())->assertRedirect();

        expect($deck->cards()->count())->toBe(2)
            ->and($deck->cards()->reorder('position', 'desc')->first()->position)->toBe(1);
    });

    it('rejects a card referencing a player it did not declare', function () {
        $user = creator();
        $deck = deckFor($user);

        actingAs($user)->post("/creator/decks/{$deck->uuid}/cards", validCard([
            'participants' => 2,
            'content' => [['type' => 'variable', 'value' => 'player3']],
        ]))->assertSessionHasErrors('content');

        expect($deck->cards()->count())->toBe(0);
    });

    it('rejects all_players on a card that does not target everyone', function () {
        $user = creator();
        $deck = deckFor($user);

        actingAs($user)->post("/creator/decks/{$deck->uuid}/cards", validCard([
            'participants' => 2,
            'content' => [['type' => 'variable', 'value' => 'all_players']],
        ]))->assertSessionHasErrors('content');
    });

    it('rejects an unknown variable', function () {
        $user = creator();
        $deck = deckFor($user);

        actingAs($user)->post("/creator/decks/{$deck->uuid}/cards", validCard([
            'content' => [['type' => 'variable', 'value' => 'player99']],
        ]))->assertSessionHasErrors();
    });

    it('accepts a card that targets everyone', function () {
        $user = creator();
        $deck = deckFor($user);

        actingAs($user)->post("/creator/decks/{$deck->uuid}/cards", validCard([
            'participants' => 'all',
            'content' => [['type' => 'variable', 'value' => 'all_players'], ['type' => 'text', 'value' => ' drinken.']],
        ]))->assertRedirect();

        expect($deck->cards()->sole()->participants_mode)->toBe(Card::PARTICIPANTS_ALL);
    });

    it('does not let a user add cards to another deck', function () {
        $deck = deckFor(User::factory()->create());

        actingAs(creator())
            ->post("/creator/decks/{$deck->uuid}/cards", validCard())
            ->assertForbidden();
    });

    it('refuses to touch a card belonging to a different deck', function () {
        $user = creator();
        $mine = deckFor($user);
        $theirs = deckFor(User::factory()->create());
        $theirCard = Card::factory()->for($theirs)->create();

        // Own deck in the URL, someone else's card id — must not resolve.
        actingAs($user)
            ->delete("/creator/decks/{$mine->uuid}/cards/{$theirCard->id}")
            ->assertNotFound();

        expect(Card::find($theirCard->id))->not->toBeNull();
    });

    it('reorders cards', function () {
        $user = creator();
        $deck = deckFor($user);
        $a = Card::factory()->for($deck)->create(['position' => 0]);
        $b = Card::factory()->for($deck)->create(['position' => 1]);
        $c = Card::factory()->for($deck)->create(['position' => 2]);

        actingAs($user)->post("/creator/decks/{$deck->uuid}/cards/reorder", [
            'ids' => [$c->id, $a->id, $b->id],
        ])->assertRedirect();

        expect($deck->cards()->pluck('id')->all())->toBe([$c->id, $a->id, $b->id]);
    });

    it('rejects a reorder containing a card from another deck', function () {
        $user = creator();
        $deck = deckFor($user);
        $mine = Card::factory()->for($deck)->create();
        $theirs = Card::factory()->for(deckFor(User::factory()->create()))->create();

        actingAs($user)->post("/creator/decks/{$deck->uuid}/cards/reorder", [
            'ids' => [$mine->id, $theirs->id],
        ])->assertSessionHasErrors('ids');
    });

    it('rejects a partial reorder', function () {
        $user = creator();
        $deck = deckFor($user);
        $a = Card::factory()->for($deck)->create();
        Card::factory()->for($deck)->create();

        actingAs($user)->post("/creator/decks/{$deck->uuid}/cards/reorder", ['ids' => [$a->id]])
            ->assertSessionHasErrors('ids');
    });
});

describe('duplicating', function () {
    it('copies a deck under the current user and leaves the original alone', function () {
        $owner = User::factory()->create();
        $original = deckFor($owner, ['name' => 'Origineel']);
        Card::factory()->for($original)->create();
        $original->update(['visibility' => Deck::VISIBILITY_SHARED, 'status' => Deck::STATUS_PUBLISHED, 'share_token' => 'tok'.str_repeat('x', 29)]);

        $me = creator();
        actingAs($me)->post("/creator/decks/{$original->uuid}/duplicate")->assertRedirect();

        $copy = Deck::where('owner_id', $me->id)->sole();

        expect($copy->id)->not->toBe($original->id)
            ->and($copy->cards()->count())->toBe(1)
            ->and($copy->uuid)->not->toBe($original->uuid)
            // A copy is private and unshared regardless of the original.
            ->and($copy->share_token)->toBeNull()
            ->and($copy->visibility)->toBe(Deck::VISIBILITY_PRIVATE);

        // The original is untouched, ownership included.
        expect($original->fresh()->owner_id)->toBe($owner->id)
            ->and($original->fresh()->name)->toBe('Origineel');
    });

    it('makes an independent copy — editing it does not change the original', function () {
        $original = deckFor(User::factory()->create());
        $card = Card::factory()->for($original)->create(['type' => 'truth']);
        $original->update(['visibility' => Deck::VISIBILITY_SHARED, 'status' => Deck::STATUS_PUBLISHED, 'share_token' => str_repeat('y', 32)]);

        $me = creator();
        actingAs($me)->post("/creator/decks/{$original->uuid}/duplicate");

        $copy = Deck::where('owner_id', $me->id)->sole();
        $copy->cards()->update(['type' => 'dare']);

        expect($card->fresh()->type)->toBe('truth');
    });

    it('will not duplicate a deck the user cannot see', function () {
        $private = deckFor(User::factory()->create());

        actingAs(creator())->post("/creator/decks/{$private->uuid}/duplicate")->assertForbidden();
    });
});

describe('sharing', function () {
    it('issues a link and makes the deck reachable by it', function () {
        $user = creator();
        $deck = deckFor($user);
        Card::factory()->for($deck)->create();

        actingAs($user)->post("/creator/decks/{$deck->uuid}/share")->assertRedirect();

        $token = $deck->fresh()->share_token;

        expect($token)->toHaveLength(32);

        get("/d/{$token}")->assertOk();
    });

    it('revoking the link takes effect immediately', function () {
        $user = creator();
        $deck = deckFor($user);
        Card::factory()->for($deck)->create();

        actingAs($user)->post("/creator/decks/{$deck->uuid}/share");
        $token = $deck->fresh()->share_token;

        actingAs($user)->delete("/creator/decks/{$deck->uuid}/share")->assertRedirect();

        get("/d/{$token}")->assertNotFound();
    });

    it('rotating the link invalidates the previous one', function () {
        $user = creator();
        $deck = deckFor($user);
        Card::factory()->for($deck)->create();

        actingAs($user)->post("/creator/decks/{$deck->uuid}/share");
        $first = $deck->fresh()->share_token;

        actingAs($user)->post("/creator/decks/{$deck->uuid}/share");

        get("/d/{$first}")->assertNotFound();
        get('/d/'.$deck->fresh()->share_token)->assertOk();
    });

    it('404s on an unknown token rather than hinting at what exists', function () {
        get('/d/'.str_repeat('z', 32))->assertNotFound();
    });

    it('does not let a user share someone else deck', function () {
        $deck = deckFor(User::factory()->create());

        actingAs(creator())->post("/creator/decks/{$deck->uuid}/share")->assertForbidden();
    });
});

describe('export', function () {
    it('downloads a deck as importable JSON', function () {
        $user = creator();
        $deck = deckFor($user, ['name' => 'Export test']);
        Card::factory()->for($deck)->create();

        $response = actingAs($user)->get("/creator/decks/{$deck->uuid}/export")->assertOk();

        $json = json_decode($response->streamedContent(), true);

        expect($json['format'])->toBe('biertappen-deck')
            ->and($json['deck']['name'])->toBe('Export test')
            ->and($json['deck'])->not->toHaveKey('owner_id');
    });

    it('does not export a deck the user cannot see', function () {
        $deck = deckFor(User::factory()->create());

        actingAs(creator())->get("/creator/decks/{$deck->uuid}/export")->assertForbidden();
    });
});
