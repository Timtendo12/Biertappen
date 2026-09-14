<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureHasEntitlement
{
    /**
     * Server-side premium gate. The frontend hiding a button is presentation;
     * this is the thing that actually denies access.
     */
    public function handle(Request $request, Closure $next, string $type): Response
    {
        $user = $request->user();

        if (! $user) {
            abort(401);
        }

        /*
         * Administrators are exempt: they curate the base game through the same
         * creator, and buying your own product to do your job makes no sense.
         * This mirrors DeckPolicy, which already exempts them — without it the
         * middleware would reject admins before the policy was ever consulted.
         */
        if (! $user->isAdmin() && ! $user->hasEntitlement($type)) {
            abort(403, __('billing.entitlement_required'));
        }

        return $next($request);
    }
}
