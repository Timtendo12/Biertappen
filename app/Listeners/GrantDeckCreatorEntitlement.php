<?php

namespace App\Listeners;

use App\Domain\Billing\EntitlementService;
use App\Models\Entitlement;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use LemonSqueezy\Laravel\Events\OrderCreated;
use LemonSqueezy\Laravel\Order;

/**
 * Grants the deck creator when a matching order is actually paid.
 *
 * This is the one and only path to premium access. The checkout call grants
 * nothing, and the success redirect the browser lands on grants nothing — both
 * are things a user can trigger without having paid.
 */
class GrantDeckCreatorEntitlement
{
    public function __construct(private readonly EntitlementService $entitlements) {}

    public function handle(OrderCreated $event): void
    {
        $order = $event->order;

        if (! $order instanceof Order) {
            return;
        }

        // Only the configured product grants anything. Any other variant —
        // including a donation that somehow reached the package — is recorded
        // by the package and otherwise ignored here.
        if ((string) $order->variant_id !== (string) config('billing.premium_variant_id')) {
            return;
        }

        /*
         * `paid` is required. Lemon Squeezy also emits orders as `pending` and
         * `failed`; granting on the event alone would hand out the deck creator
         * for an order that never settles.
         */
        if ($order->status !== Order::STATUS_PAID) {
            return;
        }

        // findOrCreateCustomer() already resolves the morph, so this is the User
        // itself rather than the Lemon Squeezy customer record.
        $user = $event->billable;

        if (! $user instanceof User) {
            Log::warning('Paid premium order could not be matched to a user', [
                'order' => $order->lemon_squeezy_id,
            ]);

            return;
        }

        $this->entitlements->grant(
            $user,
            config('billing.premium_entitlement', Entitlement::TYPE_DECK_CREATOR),
            Entitlement::SOURCE_PURCHASE,
            $order->lemon_squeezy_id,
        );
    }
}
