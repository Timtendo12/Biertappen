<?php

use App\Models\Donation;
use App\Models\Entitlement;
use App\Models\User;
use App\Models\WebhookEvent;
use Illuminate\Support\Facades\Route;
use LemonSqueezy\Laravel\Order;

use function Pest\Laravel\actingAs;

/*
 * The webhook is the only thing in the product that grants paid access, and
 * Lemon Squeezy retries and resends. So these tests are less about the happy
 * path than about everything that arrives twice, arrives forged, or arrives
 * without a user attached.
 */

const PREMIUM_VARIANT = '111111';
const DONATION_VARIANT = '222222';
const SIGNING_SECRET = 'test-signing-secret';

beforeEach(function () {
    config([
        'lemon-squeezy.signing_secret' => SIGNING_SECRET,
        'lemon-squeezy.store' => '12345',
        'billing.premium_variant_id' => PREMIUM_VARIANT,
        'billing.donation_variant_id' => DONATION_VARIANT,
    ]);

    Route::middleware(['web', 'entitled:deck_creator'])->get('/_test/creator', fn () => 'ok');
});

/** Builds an order webhook in the shape the package's handler reads. */
function orderPayload(array $overrides = []): array
{
    return array_replace_recursive([
        'meta' => [
            'event_name' => 'order_created',
            'custom_data' => [],
        ],
        'data' => [
            'type' => 'orders',
            'id' => '900001',
            'attributes' => [
                'store_id' => 12345,
                'customer_id' => 55555,
                'identifier' => '6f1e7a1e-0000-4000-8000-000000000001',
                'order_number' => 1001,
                'user_name' => 'Tim Slager',
                'user_email' => 'tim@example.test',
                'currency' => 'EUR',
                'subtotal' => 999,
                'discount_total' => 0,
                'tax' => 0,
                'total' => 999,
                'tax_name' => null,
                'status' => 'paid',
                'refunded' => false,
                'refunded_at' => null,
                'created_at' => '2026-09-14T12:00:00.000000Z',
                'first_order_item' => [
                    'product_id' => 7777,
                    'variant_id' => (int) PREMIUM_VARIANT,
                ],
                'urls' => ['receipt' => 'https://app.lemonsqueezy.com/my-orders/abc'],
            ],
        ],
    ], $overrides);
}

function premiumPayload(User $user, array $overrides = []): array
{
    return orderPayload(array_replace_recursive([
        'meta' => [
            'custom_data' => [
                'billable_id' => (string) $user->id,
                'billable_type' => $user->getMorphClass(),
                'kind' => 'premium',
            ],
        ],
    ], $overrides));
}

/** Posts the payload with a genuine signature over the raw body. */
function sendWebhook(array $payload, ?string $signature = null): Illuminate\Testing\TestResponse
{
    $body = json_encode($payload, JSON_THROW_ON_ERROR);

    return test()->call(
        'POST',
        '/lemon-squeezy/webhook',
        [],
        [],
        [],
        [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_ACCEPT' => 'application/json',
            'HTTP_X_SIGNATURE' => $signature ?? hash_hmac('sha256', $body, SIGNING_SECRET),
        ],
        $body,
    );
}

describe('signature verification', function () {
    it('refuses a forged signature and writes nothing', function () {
        $user = User::factory()->create();

        sendWebhook(premiumPayload($user), signature: str_repeat('a', 64))
            ->assertForbidden();

        expect(Entitlement::count())->toBe(0)
            ->and(Order::count())->toBe(0)
            ->and(WebhookEvent::count())->toBe(0);
    });

    it('refuses a request carrying no signature at all', function () {
        $user = User::factory()->create();
        $body = json_encode(premiumPayload($user), JSON_THROW_ON_ERROR);

        test()->call('POST', '/lemon-squeezy/webhook', [], [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_ACCEPT' => 'application/json',
        ], $body)->assertForbidden();

        expect(Entitlement::count())->toBe(0);
    });

    it('accepts a correctly signed request', function () {
        sendWebhook(premiumPayload(User::factory()->create()))->assertOk();
    });
});

describe('granting the deck creator', function () {
    it('grants on a paid order for the premium variant', function () {
        $user = User::factory()->create();

        sendWebhook(premiumPayload($user))->assertOk();

        expect($user->fresh()->hasEntitlement(Entitlement::TYPE_DECK_CREATOR))->toBeTrue();

        actingAs($user->fresh())->get('/_test/creator')->assertOk();
    });

    it('records which order granted it', function () {
        $user = User::factory()->create();

        sendWebhook(premiumPayload($user))->assertOk();

        expect($user->entitlements()->first()->lemon_squeezy_order_id)->toBe('900001');
    });

    it('grants nothing for an order that is still pending', function () {
        $user = User::factory()->create();

        sendWebhook(premiumPayload($user, ['data' => ['attributes' => ['status' => 'pending']]]))
            ->assertOk();

        expect(Entitlement::count())->toBe(0);
    });

    it('grants nothing for a failed order', function () {
        $user = User::factory()->create();

        sendWebhook(premiumPayload($user, ['data' => ['attributes' => ['status' => 'failed']]]))
            ->assertOk();

        expect(Entitlement::count())->toBe(0);
    });

    it('grants nothing for some other product variant', function () {
        $user = User::factory()->create();

        sendWebhook(premiumPayload($user, [
            'data' => ['attributes' => ['first_order_item' => ['variant_id' => 999999]]],
        ]))->assertOk();

        expect(Entitlement::count())->toBe(0);
    });
});

describe('idempotency', function () {
    /*
     * The package inserts orders with a plain create() against a uniquely
     * indexed lemon_squeezy_id, so an unguarded redelivery would 500 forever.
     * Our ledger stops the second delivery before it ever reaches that code.
     */
    it('handles the same delivery twice without duplicating anything', function () {
        $user = User::factory()->create();
        $payload = premiumPayload($user);

        sendWebhook($payload)->assertOk();
        sendWebhook($payload)->assertOk();

        expect(Entitlement::count())->toBe(1)
            ->and(Order::count())->toBe(1)
            ->and(WebhookEvent::count())->toBe(1);
    });

    it('survives many redeliveries of the same event', function () {
        $user = User::factory()->create();
        $payload = premiumPayload($user);

        foreach (range(1, 5) as $ignored) {
            sendWebhook($payload)->assertOk();
        }

        expect(Entitlement::count())->toBe(1)->and(Order::count())->toBe(1);
    });

    it('still processes a genuinely different order', function () {
        $user = User::factory()->create();

        sendWebhook(premiumPayload($user))->assertOk();
        sendWebhook(premiumPayload($user, ['data' => [
            'id' => '900002',
            'attributes' => [
                'order_number' => 1002,
                'identifier' => '6f1e7a1e-0000-4000-8000-000000000002',
            ],
        ]]))->assertOk();

        // Two orders, but still exactly one entitlement: buying twice does not
        // create a second grant.
        expect(Order::count())->toBe(2)->and(Entitlement::count())->toBe(1);
    });
});

describe('refunds', function () {
    it('revokes access when the order is refunded', function () {
        $user = User::factory()->create();

        sendWebhook(premiumPayload($user))->assertOk();
        expect($user->fresh()->hasEntitlement(Entitlement::TYPE_DECK_CREATOR))->toBeTrue();

        sendWebhook(premiumPayload($user, [
            'meta' => ['event_name' => 'order_refunded'],
            'data' => ['attributes' => [
                'status' => 'refunded',
                'refunded' => true,
                'refunded_at' => '2026-09-15T12:00:00.000000Z',
            ]],
        ]))->assertOk();

        expect($user->fresh()->hasEntitlement(Entitlement::TYPE_DECK_CREATOR))->toBeFalse();

        // And the gate closes on the very next request, not at some later sync.
        actingAs($user->fresh())->get('/_test/creator')->assertForbidden();
    });

    it('leaves a manual admin grant alone when an unrelated order is refunded', function () {
        $user = User::factory()->withDeckCreator()->create();

        sendWebhook(premiumPayload($user, [
            'meta' => ['event_name' => 'order_refunded'],
            'data' => ['attributes' => ['status' => 'refunded', 'refunded' => true]],
        ]))->assertOk();

        // The manual grant carries no order id, so revoking by order must not touch it.
        expect($user->fresh()->hasEntitlement(Entitlement::TYPE_DECK_CREATOR))->toBeTrue();
    });
});

describe('donations', function () {
    /*
     * The package cannot handle these at all: resolveBillable() throws when
     * custom_data has no billable_id, and lemon_squeezy_orders.billable_id is
     * NOT NULL. They are intercepted before delegation.
     */
    function donationPayload(array $overrides = []): array
    {
        return orderPayload(array_replace_recursive([
            'meta' => ['custom_data' => ['kind' => 'donation']],
            'data' => [
                'id' => '800001',
                'attributes' => [
                    'total' => 500,
                    'first_order_item' => ['variant_id' => (int) DONATION_VARIANT],
                ],
            ],
        ], $overrides));
    }

    it('records a donation from a logged-out visitor', function () {
        sendWebhook(donationPayload())->assertOk();

        $donation = Donation::sole();

        expect($donation->user_id)->toBeNull()
            ->and($donation->amount_cents)->toBe(500)
            ->and($donation->status)->toBe(Donation::STATUS_PAID);
    });

    it('grants nothing — a donation is not a purchase', function () {
        sendWebhook(donationPayload())->assertOk();

        expect(Entitlement::count())->toBe(0);
    });

    it('never reaches the package, so no order row is written', function () {
        sendWebhook(donationPayload())->assertOk();

        expect(Order::count())->toBe(0);
    });

    it('attributes a donation to the signed-in user who made it', function () {
        $user = User::factory()->create();

        sendWebhook(donationPayload([
            'meta' => ['custom_data' => ['user_id' => (string) $user->id]],
        ]))->assertOk();

        expect(Donation::sole()->user_id)->toBe($user->id)
            ->and(Entitlement::count())->toBe(0);
    });

    it('does not double-record a redelivered donation', function () {
        sendWebhook(donationPayload())->assertOk();
        sendWebhook(donationPayload())->assertOk();

        expect(Donation::count())->toBe(1);
    });

    it('marks a refunded donation without touching entitlements', function () {
        sendWebhook(donationPayload())->assertOk();

        sendWebhook(donationPayload([
            'meta' => ['event_name' => 'order_refunded'],
        ]))->assertOk();

        expect(Donation::sole()->status)->toBe(Donation::STATUS_REFUNDED)
            ->and(Entitlement::count())->toBe(0);
    });

    it('takes the amount from Lemon Squeezy, never from the browser', function () {
        // Whatever the client once asked for, the recorded figure is the one
        // Lemon Squeezy actually charged.
        sendWebhook(donationPayload([
            'data' => ['attributes' => ['total' => 1000]],
        ]))->assertOk();

        expect(Donation::sole()->amount_cents)->toBe(1000);
    });
});

describe('unknown events', function () {
    it('acknowledges an event it has no handler for, so retries stop', function () {
        $user = User::factory()->create();

        // order_updated is a real Lemon Squeezy event that the package has no
        // handler for. Answering 200 is what stops it being retried forever.
        sendWebhook(premiumPayload($user, [
            'meta' => ['event_name' => 'order_updated'],
        ]))->assertOk();

        expect(Entitlement::count())->toBe(0);
    });

    it('acknowledges a payload with no identifiable event', function () {
        sendWebhook(['meta' => [], 'data' => []])->assertOk();

        expect(WebhookEvent::count())->toBe(0);
    });
});
