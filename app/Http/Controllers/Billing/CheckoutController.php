<?php

namespace App\Http\Controllers\Billing;

use App\Domain\Billing\CheckoutService;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Starts Lemon Squeezy checkouts.
 *
 * Both endpoints return a URL and nothing else. No entitlement, no donation
 * record, no state of any kind is created here — a user can call these as often
 * as they like without it meaning they paid.
 */
class CheckoutController extends Controller
{
    public function __construct(private readonly CheckoutService $checkout) {}

    /** Buy the deck creator. */
    public function premium(Request $request): JsonResponse
    {
        $user = $request->user();

        // Already owned: sending them to pay twice would be a bug, not an upsell.
        if ($user->hasEntitlement(config('billing.premium_entitlement'))) {
            return response()->json(['message' => __('billing.already_owned')], 409);
        }

        try {
            return response()->json(['url' => $this->checkout->premium($user)]);
        } catch (\Throwable $e) {
            Log::error('Premium checkout failed', ['message' => $e->getMessage()]);

            return response()->json(['message' => __('billing.checkout_failed')], 502);
        }
    }

    /**
     * Donate. Open to guests — "Doneer een biertje" must not require an account.
     */
    public function donate(Request $request): JsonResponse
    {
        $config = config('billing.donation');

        /*
         * The client sends an amount in whole euros, never a price in cents, and
         * it is bounded here. Trusting a client-supplied price would let anyone
         * mint a €0.01 "donation" — or a €10,000 one on someone's saved card.
         */
        $validated = $request->validate([
            'amount' => [
                'required',
                'integer',
                'min:'.(int) ($config['min'] / 100),
                'max:'.(int) ($config['max'] / 100),
            ],
        ]);

        $amountCents = $validated['amount'] * 100;

        try {
            return response()->json([
                'url' => $this->checkout->donation($amountCents, $request->user()),
            ]);
        } catch (\Throwable $e) {
            Log::error('Donation checkout failed', ['message' => $e->getMessage()]);

            return response()->json(['message' => __('billing.checkout_failed')], 502);
        }
    }

    /**
     * Where Lemon Squeezy sends the browser after a purchase.
     *
     * Shows a thank-you and nothing more. Access is decided by the entitlement
     * the webhook wrote, which may not have arrived yet — so the page says the
     * purchase is being processed rather than promising access.
     */
    public function thanks(Request $request): \Inertia\Response
    {
        return \Inertia\Inertia::render('billing/Thanks', [
            'kind' => $request->query('kind') === CheckoutService::KIND_DONATION
                ? CheckoutService::KIND_DONATION
                : CheckoutService::KIND_PREMIUM,
            'hasAccess' => (bool) $request->user()?->hasEntitlement(config('billing.premium_entitlement')),
        ]);
    }
}
