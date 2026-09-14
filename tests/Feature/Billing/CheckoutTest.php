<?php

use App\Models\Donation;
use App\Models\Entitlement;
use App\Models\User;
use Illuminate\Support\Facades\Http;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\postJson;

/*
 * Starting a checkout must never be worth anything on its own. These verify the
 * endpoints hand back a URL and create no access, and that the price is decided
 * on the server rather than accepted from the browser.
 */

beforeEach(function () {
    config([
        'lemon-squeezy.api_key' => 'test-api-key',
        'lemon-squeezy.store' => '12345',
        'billing.premium_variant_id' => '111111',
        'billing.donation_variant_id' => '222222',
    ]);

    /*
     * Resolved per request rather than registered as a fixed stub: Laravel
     * evaluates stubs in registration order, so a later Http::fake() in an
     * individual test would never take effect.
     */
    $this->checkoutResponse = Http::response([
        'data' => ['attributes' => ['url' => 'https://biertappen.lemonsqueezy.com/checkout/abc']],
    ]);

    Http::fake(fn () => $this->checkoutResponse);
});

/** The attributes the SDK sent to Lemon Squeezy for the last checkout. */
function lastCheckoutAttributes(): array
{
    $request = collect(Http::recorded())->last()[0];

    return $request->data()['data']['attributes'];
}

describe('premium checkout', function () {
    it('requires an account', function () {
        postJson('/billing/checkout')->assertUnauthorized();
    });

    it('returns a checkout url without granting anything', function () {
        $user = User::factory()->create();

        actingAs($user)
            ->postJson('/billing/checkout')
            ->assertOk()
            ->assertJsonPath('url', 'https://biertappen.lemonsqueezy.com/checkout/abc');

        // The whole point: paying is what grants access, not asking to pay.
        expect(Entitlement::count())->toBe(0);
    });

    it('tags the checkout so the webhook can tell purchases from donations', function () {
        actingAs(User::factory()->create())->postJson('/billing/checkout')->assertOk();

        $custom = lastCheckoutAttributes()['checkout_data']['custom'];

        expect($custom['kind'])->toBe('premium')
            ->and($custom)->toHaveKeys(['billable_id', 'billable_type']);
    });

    it('refuses to sell the deck creator twice', function () {
        actingAs(User::factory()->withDeckCreator()->create())
            ->postJson('/billing/checkout')
            ->assertStatus(409);
    });

    it('reports a failure upstream instead of pretending it worked', function () {
        $this->checkoutResponse = Http::response(
            ['errors' => [['detail' => 'Variant not found', 'status' => '422']]],
            422,
        );

        actingAs(User::factory()->create())
            ->postJson('/billing/checkout')
            ->assertStatus(502);
    });
});

describe('donation checkout', function () {
    it('lets a logged-out visitor donate', function () {
        postJson('/billing/donate', ['amount' => 5])
            ->assertOk()
            ->assertJsonPath('url', 'https://biertappen.lemonsqueezy.com/checkout/abc');
    });

    it('converts the amount to cents server-side', function () {
        postJson('/billing/donate', ['amount' => 5])->assertOk();

        expect(lastCheckoutAttributes()['custom_price'])->toBe(500);
    });

    it('rejects an amount below the minimum', function () {
        postJson('/billing/donate', ['amount' => 0])->assertStatus(422);
    });

    it('rejects a negative amount', function () {
        postJson('/billing/donate', ['amount' => -100])->assertStatus(422);
    });

    it('rejects an absurd amount', function () {
        postJson('/billing/donate', ['amount' => 999999])->assertStatus(422);
    });

    it('rejects a non-integer amount', function () {
        postJson('/billing/donate', ['amount' => 'gratis'])->assertStatus(422);
    });

    it('ignores any price the client tries to supply directly', function () {
        // custom_price is not an accepted input; only `amount` in whole euros is,
        // and the server multiplies it itself.
        postJson('/billing/donate', ['amount' => 3, 'custom_price' => 1])->assertOk();

        expect(lastCheckoutAttributes()['custom_price'])->toBe(300);
    });

    it('tags the checkout as a donation', function () {
        postJson('/billing/donate', ['amount' => 5])->assertOk();

        expect(lastCheckoutAttributes()['checkout_data']['custom']['kind'])->toBe('donation');
    });

    it('attaches the user id when someone signed in donates', function () {
        $user = User::factory()->create();

        actingAs($user)->postJson('/billing/donate', ['amount' => 5])->assertOk();

        expect(lastCheckoutAttributes()['checkout_data']['custom']['user_id'])->toBe((string) $user->id);
    });

    it('creates nothing locally — the webhook records the gift', function () {
        postJson('/billing/donate', ['amount' => 5])->assertOk();

        expect(Donation::count())->toBe(0)->and(Entitlement::count())->toBe(0);
    });
});

describe('the thank-you page', function () {
    it('never grants access by being visited', function () {
        $user = User::factory()->create();

        actingAs($user)->get('/billing/thanks?kind=premium')->assertOk();

        expect($user->fresh()->hasEntitlement(Entitlement::TYPE_DECK_CREATOR))->toBeFalse();
    });

    it('does not claim access the webhook has not granted yet', function () {
        actingAs(User::factory()->create())
            ->get('/billing/thanks?kind=premium')
            ->assertInertia(fn ($page) => $page->component('billing/Thanks')->where('hasAccess', false));
    });
});
