<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Verifies the X-Signature header on Lemon Squeezy webhooks.
 *
 * The package ships an equivalent, but its comparison method takes a non-nullable
 * string, so a request arriving with no signature header at all raises a
 * TypeError and returns 500. A forged request should be refused, plainly, with a
 * 403 — not crash the app — so this is our own.
 *
 * Verification runs against the RAW body: re-encoding a decoded payload would
 * change byte-for-byte content and break the HMAC.
 */
class VerifyLemonSqueezySignature
{
    public function handle(Request $request, Closure $next): Response
    {
        $secret = config('lemon-squeezy.signing_secret');

        // Refusing to run unsigned is deliberate: a missing secret in production
        // would otherwise mean every forged payload is trusted.
        abort_if(blank($secret), 500, 'Lemon Squeezy signing secret is not configured.');

        $signature = $request->header('x-signature');

        abort_if(! is_string($signature) || $signature === '', 403, 'Missing webhook signature.');

        $expected = hash_hmac('sha256', $request->getContent(), $secret);

        abort_unless(hash_equals($expected, $signature), 403, 'Invalid webhook signature.');

        return $next($request);
    }
}
