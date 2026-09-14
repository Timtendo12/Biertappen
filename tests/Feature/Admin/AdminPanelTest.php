<?php

use App\Models\Card;
use App\Models\Deck;
use App\Models\Entitlement;
use App\Models\Report;
use App\Models\User;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\delete;
use function Pest\Laravel\get;
use function Pest\Laravel\post;
use function Pest\Laravel\put;

/*
 * Admin routes can escalate privilege and publish content to every player, so
 * the access matrix is tested first and in full — hiding the UI proves nothing.
 */

function admin(): User
{
    return User::factory()->admin()->create();
}

describe('access control', function () {
    $routes = [
        ['get', '/admin'],
        ['get', '/admin/decks'],
        ['get', '/admin/users'],
        ['get', '/admin/reports'],
    ];

    it('sends a guest to log in rather than into the panel', function () use ($routes) {
        foreach ($routes as [$method, $uri]) {
            // The auth middleware runs first, so a logged-out admin gets a
            // usable login redirect. Concealment matters for users who ARE
            // signed in but not admins — that case is the next test.
            test()->{$method}($uri)->assertRedirect('/login');
        }
    });

    it('404s every admin route for an ordinary user', function () use ($routes) {
        $user = User::factory()->create();

        foreach ($routes as [$method, $uri]) {
            actingAs($user)->{$method}($uri)->assertNotFound();
        }
    });

    it('404s admin routes even for a paying user', function () use ($routes) {
        $user = User::factory()->withDeckCreator()->create();

        foreach ($routes as [$method, $uri]) {
            actingAs($user)->{$method}($uri)->assertNotFound();
        }
    });

    it('lets administrators in', function () use ($routes) {
        $user = admin();

        foreach ($routes as [$method, $uri]) {
            actingAs($user)->{$method}($uri)->assertOk();
        }
    });
});

describe('privilege escalation', function () {
    it('does not let a user grant themselves the deck creator', function () {
        $user = User::factory()->create();

        actingAs($user)->post("/admin/users/{$user->id}/entitlement")->assertNotFound();

        expect($user->fresh()->hasEntitlement(Entitlement::TYPE_DECK_CREATOR))->toBeFalse();
    });

    it('does not let a user make themselves an administrator', function () {
        $user = User::factory()->create();

        actingAs($user)->put("/admin/users/{$user->id}/role", ['role' => 'admin'])->assertNotFound();

        expect($user->fresh()->isAdmin())->toBeFalse();
    });

    it('does not let a user publish their own deck into the base game', function () {
        $user = User::factory()->withDeckCreator()->create();
        $deck = Deck::factory()->for($user, 'owner')->create();

        actingAs($user)->post("/admin/decks/{$deck->uuid}/publish")->assertNotFound();

        expect($deck->fresh()->status)->toBe(Deck::STATUS_DRAFT);
    });

    it('stops the last administrator locking everyone out', function () {
        $user = admin();

        actingAs($user)->put("/admin/users/{$user->id}/role", ['role' => 'user'])->assertRedirect();

        expect($user->fresh()->isAdmin())->toBeTrue();
    });

    it('lets an administrator demote a different administrator', function () {
        $other = admin();

        actingAs(admin())->put("/admin/users/{$other->id}/role", ['role' => 'user'])->assertRedirect();

        expect($other->fresh()->isAdmin())->toBeFalse();
    });
});

describe('entitlement management', function () {
    it('grants the deck creator manually, with no purchase', function () {
        $user = User::factory()->create();

        actingAs(admin())->post("/admin/users/{$user->id}/entitlement")->assertRedirect();

        $entitlement = $user->fresh()->entitlements()->sole();

        expect($entitlement->source)->toBe(Entitlement::SOURCE_MANUAL)
            // A manual grant has no order — which is exactly why entitlements
            // are their own table rather than derived from purchases.
            ->and($entitlement->lemon_squeezy_order_id)->toBeNull();

        actingAs($user->fresh())->get('/creator')->assertOk();
    });

    it('revokes access, closing the creator on the next request', function () {
        $user = User::factory()->withDeckCreator()->create();

        actingAs(admin())->delete("/admin/users/{$user->id}/entitlement")->assertRedirect();

        actingAs($user->fresh())->get('/creator')->assertForbidden();
    });

    it('granting twice does not create a second entitlement', function () {
        $user = User::factory()->create();
        $panel = admin();

        actingAs($panel)->post("/admin/users/{$user->id}/entitlement");
        actingAs($panel)->post("/admin/users/{$user->id}/entitlement");

        expect($user->entitlements()->count())->toBe(1);
    });

    it('re-granting after a revoke reactivates rather than duplicating', function () {
        $user = User::factory()->withDeckCreator()->create();
        $panel = admin();

        actingAs($panel)->delete("/admin/users/{$user->id}/entitlement");
        actingAs($panel)->post("/admin/users/{$user->id}/entitlement");

        expect($user->entitlements()->count())->toBe(1)
            ->and($user->fresh()->hasEntitlement(Entitlement::TYPE_DECK_CREATOR))->toBeTrue();
    });
});

describe('base-game decks', function () {
    it('creates an ownerless deck, which is what makes it base game', function () {
        actingAs(admin())->post('/admin/decks', [
            'name' => 'Nieuw basisdeck',
            'ending_mode' => 'cards',
            'ending_count' => 25,
        ])->assertRedirect();

        expect(Deck::sole()->owner_id)->toBeNull();
    });

    it('publishes a base deck into everyone deck selection', function () {
        $deck = Deck::factory()->baseGame()->create(['status' => Deck::STATUS_DRAFT]);
        Card::factory()->for($deck)->create();

        actingAs(admin())->post("/admin/decks/{$deck->uuid}/publish")->assertRedirect();

        // Visible to a guest, which is the whole point of publishing.
        test()->getJson('/api/decks')->assertJsonCount(1, 'decks');
    });

    it('refuses to publish a deck with no cards', function () {
        $deck = Deck::factory()->baseGame()->create(['status' => Deck::STATUS_DRAFT]);

        actingAs(admin())->post("/admin/decks/{$deck->uuid}/publish")->assertRedirect();

        expect($deck->fresh()->status)->toBe(Deck::STATUS_DRAFT);
    });

    it('unpublishing removes it from deck selection', function () {
        $deck = Deck::factory()->baseGame()->create();
        Card::factory()->for($deck)->create();

        actingAs(admin())->post("/admin/decks/{$deck->uuid}/unpublish")->assertRedirect();

        test()->getJson('/api/decks')->assertJsonCount(0, 'decks');
    });

    it('hiding a reported deck takes it out of circulation', function () {
        $deck = Deck::factory()->for(User::factory()->create(), 'owner')->shared()->create();
        Card::factory()->for($deck)->create();
        $token = $deck->share_token;

        actingAs(admin())->post("/admin/decks/{$deck->uuid}/hide")->assertRedirect();

        // The share link stops working immediately.
        get("/d/{$token}")->assertNotFound();
        expect($deck->fresh()->featured)->toBeFalse();
    });

    it('toggles featured', function () {
        $deck = Deck::factory()->baseGame()->create(['featured' => false]);

        actingAs(admin())->post("/admin/decks/{$deck->uuid}/feature")->assertRedirect();
        expect($deck->fresh()->featured)->toBeTrue();

        actingAs(admin())->post("/admin/decks/{$deck->uuid}/feature")->assertRedirect();
        expect($deck->fresh()->featured)->toBeFalse();
    });
});

describe('reports', function () {
    function sharedDeck(): Deck
    {
        $deck = Deck::factory()->for(User::factory()->create(), 'owner')->shared()->create();
        Card::factory()->for($deck)->create();

        return $deck;
    }

    it('lets a guest report a shared deck', function () {
        $deck = sharedDeck();

        post("/decks/{$deck->uuid}/report", ['reason' => 'offensive'])->assertRedirect();

        expect(Report::sole()->reporter_id)->toBeNull();
    });

    it('records the reporter when they are signed in', function () {
        $deck = sharedDeck();
        $user = User::factory()->create();

        actingAs($user)->post("/decks/{$deck->uuid}/report", ['reason' => 'spam'])->assertRedirect();

        expect(Report::sole()->reporter_id)->toBe($user->id);
    });

    it('rejects a reason outside the allowed list', function () {
        $deck = sharedDeck();

        post("/decks/{$deck->uuid}/report", ['reason' => 'because'])->assertSessionHasErrors('reason');
    });

    it('does not let someone report the base game', function () {
        $deck = Deck::factory()->baseGame()->create();

        post("/decks/{$deck->uuid}/report", ['reason' => 'spam'])->assertForbidden();
    });

    it('does not let an owner report their own deck', function () {
        $owner = User::factory()->create();
        $deck = Deck::factory()->for($owner, 'owner')->shared()->create();

        actingAs($owner)->post("/decks/{$deck->uuid}/report", ['reason' => 'spam'])->assertForbidden();
    });

    it('collapses repeat reports so one person cannot bury the queue', function () {
        $deck = sharedDeck();
        $user = User::factory()->create();

        foreach (range(1, 5) as $ignored) {
            actingAs($user)->post("/decks/{$deck->uuid}/report", ['reason' => 'spam']);
        }

        expect(Report::count())->toBe(1);
    });

    it('records who resolved a report and when', function () {
        $report = Report::factory()->create();
        $panel = admin();

        actingAs($panel)->put("/admin/reports/{$report->id}", ['status' => 'actioned'])->assertRedirect();

        $resolved = $report->fresh();

        expect($resolved->status)->toBe(Report::STATUS_ACTIONED)
            ->and($resolved->resolved_by)->toBe($panel->id)
            ->and($resolved->resolved_at)->not->toBeNull();
    });

    it('rejects an invalid resolution status', function () {
        $report = Report::factory()->create();

        actingAs(admin())
            ->put("/admin/reports/{$report->id}", ['status' => 'ignored'])
            ->assertSessionHasErrors('status');
    });
});
