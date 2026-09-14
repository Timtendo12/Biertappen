<?php

namespace App\Domain\Billing;

use App\Models\Entitlement;
use App\Models\User;
use Illuminate\Database\UniqueConstraintViolationException;

/**
 * The only place entitlements are granted or revoked.
 *
 * Every method is safe to call twice with the same input — webhooks are retried
 * and resent, so "grant once" has to be a property of the operation rather than
 * a promise about how often it runs.
 */
class EntitlementService
{
    /**
     * Grant an entitlement, or reactivate one previously revoked.
     *
     * The unique index on (user_id, type) is what actually prevents a double
     * grant under concurrent deliveries; firstOrCreate alone would still race.
     */
    public function grant(
        User $user,
        string $type,
        string $source = Entitlement::SOURCE_PURCHASE,
        ?string $orderId = null,
    ): Entitlement {
        $attributes = [
            'status' => Entitlement::STATUS_ACTIVE,
            'source' => $source,
            'lemon_squeezy_order_id' => $orderId,
            'granted_at' => now(),
            'revoked_at' => null,
        ];

        try {
            return $user->entitlements()->create([
                'type' => $type,
                ...$attributes,
            ]);
        } catch (UniqueConstraintViolationException) {
            // Already held — either a redelivered webhook, or a repurchase after
            // a refund. Reactivating covers both without creating a second row.
            $entitlement = $user->entitlements()->where('type', $type)->firstOrFail();

            $entitlement->update($attributes);

            return $entitlement;
        }
    }

    /**
     * Revoke access. Takes effect on the very next request, because
     * User::hasEntitlement() reads the database rather than any cached claim.
     */
    public function revoke(User $user, string $type): void
    {
        $user->entitlements()
            ->where('type', $type)
            ->where('status', Entitlement::STATUS_ACTIVE)
            ->update([
                'status' => Entitlement::STATUS_REVOKED,
                'revoked_at' => now(),
            ]);
    }

    /** Revoke by the order that granted it — the shape a refund webhook has. */
    public function revokeByOrder(string $orderId): void
    {
        Entitlement::query()
            ->where('lemon_squeezy_order_id', $orderId)
            ->where('status', Entitlement::STATUS_ACTIVE)
            ->update([
                'status' => Entitlement::STATUS_REVOKED,
                'revoked_at' => now(),
            ]);
    }
}
