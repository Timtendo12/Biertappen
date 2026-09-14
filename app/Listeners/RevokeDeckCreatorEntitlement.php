<?php

namespace App\Listeners;

use App\Domain\Billing\EntitlementService;
use LemonSqueezy\Laravel\Events\OrderRefunded;
use LemonSqueezy\Laravel\Order;

/**
 * Withdraws the deck creator when its order is refunded.
 *
 * Revoking by order id rather than by user is what keeps this correct when an
 * account holds an entitlement from some other source — a manual admin grant
 * is not undone by refunding an unrelated purchase.
 */
class RevokeDeckCreatorEntitlement
{
    public function __construct(private readonly EntitlementService $entitlements) {}

    public function handle(OrderRefunded $event): void
    {
        $order = $event->order;

        if (! $order instanceof Order) {
            return;
        }

        if ((string) $order->variant_id !== (string) config('billing.premium_variant_id')) {
            return;
        }

        $this->entitlements->revokeByOrder((string) $order->lemon_squeezy_id);
    }
}
