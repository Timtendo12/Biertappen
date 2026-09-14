<?php

namespace App\Domain\Billing;

use App\Models\User;
use LemonSqueezy\Laravel\Checkout;
use RuntimeException;

/**
 * Builds Lemon Squeezy checkouts.
 *
 * Both flows mark themselves with a `kind` in custom data, which is what the
 * webhook reads to decide whether an order grants the deck creator or is a
 * donation that grants nothing.
 */
class CheckoutService
{
    public const KIND_PREMIUM = 'premium';

    public const KIND_DONATION = 'donation';

    /**
     * One-time purchase of the deck creator.
     *
     * Billable::checkout() attaches billable_id/billable_type automatically, so
     * the webhook can map the order back to this user. Returns a URL only —
     * nothing is granted here, and the success redirect grants nothing either.
     */
    public function premium(User $user): string
    {
        return $user
            ->checkout($this->variant('premium_variant_id'), custom: ['kind' => self::KIND_PREMIUM])
            ->embed()
            ->url();
    }

    /**
     * A "Doneer een biertje" gift.
     *
     * Deliberately not built through Billable::checkout(): donations must work
     * for logged-out visitors, and a guest has no billable to attach. The user
     * id is passed as plain custom data when there is one, so a donation can
     * still be shown on an account without the package's customer machinery.
     *
     * @param  int  $amountCents  Already validated against config('billing.donation').
     */
    public function donation(int $amountCents, ?User $user = null): string
    {
        $custom = ['kind' => self::KIND_DONATION];

        if ($user) {
            $custom['user_id'] = (string) $user->id;
        }

        $checkout = Checkout::make($this->store(), $this->variant('donation_variant_id'))
            ->withCustomPrice($amountCents)
            ->withCustomData($custom)
            ->embed();

        if ($user) {
            $checkout = $checkout->withName($user->name)->withEmail($user->email);
        }

        return $checkout->url();
    }

    private function store(): string
    {
        $store = config('lemon-squeezy.store');

        if (blank($store)) {
            throw new RuntimeException('LEMON_SQUEEZY_STORE is not configured.');
        }

        return (string) $store;
    }

    private function variant(string $key): string
    {
        $variant = config("billing.{$key}");

        if (blank($variant)) {
            throw new RuntimeException("billing.{$key} is not configured.");
        }

        return (string) $variant;
    }
}
