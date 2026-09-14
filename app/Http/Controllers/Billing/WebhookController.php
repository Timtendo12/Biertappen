<?php

namespace App\Http\Controllers\Billing;

use App\Domain\Billing\CheckoutService;
use App\Http\Controllers\Controller;
use App\Models\Donation;
use App\Models\WebhookEvent;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use LemonSqueezy\Laravel\Http\Controllers\WebhookController as LemonSqueezyWebhookController;
use Symfony\Component\HttpFoundation\Response;

/**
 * Lemon Squeezy webhook entry point.
 *
 * Wraps the package's handler rather than extending it — that class is `final`
 * and marked `@internal`, and its own docblock points users at events instead.
 * So this composes: it owns the parts the package gets wrong for us, and
 * delegates the rest.
 *
 * Two things it adds:
 *
 * 1. Exactly-once handling. Lemon Squeezy retries any non-200 (5s/25s/125s) and
 *    the dashboard can resend, but sends no event id — so the ledger keys on
 *    event name plus resource id. This also fixes a package bug by avoidance:
 *    handleOrderCreated() does a plain insert against a uniquely-indexed
 *    lemon_squeezy_id, so a redelivery would otherwise 500 forever.
 *
 * 2. Guest donations. resolveBillable() throws when custom_data has no
 *    billable_id, and lemon_squeezy_orders.billable_id is NOT NULL — so a
 *    donation from a logged-out visitor cannot pass through the package at all.
 *    Those are intercepted here and never delegated.
 */
class WebhookController extends Controller
{
    public function __construct(private readonly LemonSqueezyWebhookController $package) {}

    public function __invoke(Request $request): Response
    {
        $payload = $request->all();

        $eventName = data_get($payload, 'meta.event_name');
        $resourceId = data_get($payload, 'data.id');

        // Nothing identifiable to act on. 200 so Lemon Squeezy stops retrying
        // something no retry will fix.
        if (! is_string($eventName) || ! is_scalar($resourceId)) {
            return new Response('Webhook received but could not be identified.', 200);
        }

        $event = $this->claim($eventName, (string) $resourceId, $payload);

        if (! $event) {
            return new Response('Webhook already handled.', 200);
        }

        try {
            $response = $this->dispatch($request, $payload, $eventName);
        } catch (\Throwable $e) {
            // Release the claim so a retry can genuinely retry. Without this a
            // transient failure would look permanently "handled".
            $event->delete();

            Log::error('Lemon Squeezy webhook failed', [
                'event' => $eventName,
                'resource' => $resourceId,
                'message' => $e->getMessage(),
            ]);

            return new Response('Webhook handling failed.', 500);
        }

        if ($response->getStatusCode() >= 400) {
            $event->delete();

            return $response;
        }

        $event->update(['processed_at' => now()]);

        return $response;
    }

    /**
     * Claim this event, or report that someone already has.
     *
     * The unique index does the work: two concurrent deliveries race to insert
     * and exactly one wins.
     */
    private function claim(string $eventName, string $resourceId, array $payload): ?WebhookEvent
    {
        try {
            return WebhookEvent::create([
                'provider' => WebhookEvent::PROVIDER_LEMON_SQUEEZY,
                'event_name' => $eventName,
                'resource_id' => $resourceId,
                'payload' => $payload,
            ]);
        } catch (UniqueConstraintViolationException) {
            return null;
        }
    }

    private function dispatch(Request $request, array $payload, string $eventName): Response
    {
        if (data_get($payload, 'meta.custom_data.kind') === CheckoutService::KIND_DONATION) {
            return $this->handleDonation($payload, $eventName);
        }

        return $this->package->__invoke($request);
    }

    /**
     * Donations are recorded here and never reach the package.
     *
     * A donation grants nothing — no entitlement is touched anywhere in this
     * method, and a test asserts that stays true.
     */
    private function handleDonation(array $payload, string $eventName): Response
    {
        $orderId = (string) data_get($payload, 'data.id');
        $attributes = data_get($payload, 'data.attributes', []);

        if ($eventName === 'order_refunded') {
            Donation::query()
                ->where('lemon_squeezy_order_id', $orderId)
                ->update(['status' => Donation::STATUS_REFUNDED]);

            return new Response('Donation refund recorded.', 200);
        }

        if ($eventName !== 'order_created') {
            return new Response('Donation event ignored.', 200);
        }

        // The amount is taken from Lemon Squeezy's payload, never from anything
        // the browser sent — the client only ever asked for a checkout.
        Donation::updateOrCreate(
            ['lemon_squeezy_order_id' => $orderId],
            [
                'user_id' => data_get($payload, 'meta.custom_data.user_id'),
                'amount_cents' => (int) data_get($attributes, 'total', 0),
                'currency' => (string) data_get($attributes, 'currency', 'EUR'),
                'status' => data_get($attributes, 'status') === 'refunded'
                    ? Donation::STATUS_REFUNDED
                    : Donation::STATUS_PAID,
                'email' => data_get($attributes, 'user_email'),
                'order_number' => data_get($attributes, 'order_number'),
                'ordered_at' => data_get($attributes, 'created_at'),
            ],
        );

        return new Response('Donation recorded.', 200);
    }
}
