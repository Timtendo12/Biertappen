<?php

use App\Models\Entitlement;
use App\Models\User;
use Illuminate\Support\Facades\Route;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;
use function Pest\Laravel\post;
use function Pest\Laravel\withHeaders;

/*
 * These lock down the three server-side gates the whole product rests on.
 * Everything else (deck ownership, admin panels, the premium creator) is built
 * on top of them, so they are tested directly rather than only through features.
 */

beforeEach(function () {
    Route::middleware(['web', 'admin'])->get('/_test/admin', fn () => 'ok');
    Route::middleware(['web', 'entitled:deck_creator'])->get('/_test/premium', fn () => 'ok');
});

describe('admin gate', function () {
    it('hides admin routes from guests', function () {
        get('/_test/admin')->assertNotFound();
    });

    it('hides admin routes from ordinary users', function () {
        actingAs(User::factory()->create())
            ->get('/_test/admin')
            ->assertNotFound();
    });

    it('allows administrators through', function () {
        actingAs(User::factory()->admin()->create())
            ->get('/_test/admin')
            ->assertOk();
    });
});

describe('premium gate', function () {
    it('rejects guests with 401', function () {
        get('/_test/premium')->assertUnauthorized();
    });

    it('rejects users without the entitlement', function () {
        actingAs(User::factory()->create())
            ->get('/_test/premium')
            ->assertForbidden();
    });

    it('allows users holding an active entitlement', function () {
        actingAs(User::factory()->withDeckCreator()->create())
            ->get('/_test/premium')
            ->assertOk();
    });

    it('rejects a revoked entitlement, so a refund takes effect immediately', function () {
        $user = User::factory()->withDeckCreator()->create();

        $user->entitlements()->update([
            'status' => Entitlement::STATUS_REVOKED,
            'revoked_at' => now(),
        ]);

        actingAs($user)->get('/_test/premium')->assertForbidden();
    });
});

describe('entitlement integrity', function () {
    it('cannot grant the same entitlement twice', function () {
        $user = User::factory()->withDeckCreator()->create();

        expect(fn () => $user->entitlements()->create([
            'type' => Entitlement::TYPE_DECK_CREATOR,
            'status' => Entitlement::STATUS_ACTIVE,
            'source' => Entitlement::SOURCE_MANUAL,
            'granted_at' => now(),
        ]))->toThrow(Illuminate\Database\UniqueConstraintViolationException::class);
    });
});

describe('locale', function () {
    it('falls back to Dutch when the browser states no preference', function () {
        withHeaders(['Accept-Language' => ''])
            ->get('/')
            ->assertInertia(fn ($page) => $page->where('locale', 'nl'));
    });

    it('falls back to Dutch when the browser prefers an unsupported language', function () {
        withHeaders(['Accept-Language' => 'de-DE,de;q=0.9'])
            ->get('/')
            ->assertInertia(fn ($page) => $page->where('locale', 'nl'));
    });

    it('honours a browser that explicitly prefers English', function () {
        withHeaders(['Accept-Language' => 'en-US,en;q=0.9'])
            ->get('/')
            ->assertInertia(fn ($page) => $page->where('locale', 'en'));
    });

    it('lets an explicit choice beat the browser preference', function () {
        withHeaders(['Accept-Language' => 'en-US,en;q=0.9'])
            ->post('/locale', ['locale' => 'nl'])
            ->assertRedirect();

        withHeaders(['Accept-Language' => 'en-US,en;q=0.9'])
            ->get('/')
            ->assertInertia(fn ($page) => $page->where('locale', 'nl'));
    });

    it('remembers a guest choice in the session', function () {
        withHeaders(['Accept-Language' => 'nl-NL,nl;q=0.9'])
            ->post('/locale', ['locale' => 'en'])
            ->assertRedirect();

        withHeaders(['Accept-Language' => 'nl-NL,nl;q=0.9'])
            ->get('/')
            ->assertInertia(fn ($page) => $page->where('locale', 'en'));
    });

    it('stores the choice on the account when signed in', function () {
        $user = User::factory()->create(['locale' => 'nl']);

        actingAs($user)->post('/locale', ['locale' => 'en'])->assertRedirect();

        expect($user->fresh()->locale)->toBe('en');
    });

    it('rejects an unsupported locale', function () {
        post('/locale', ['locale' => 'de'])->assertSessionHasErrors('locale');
    });
});
