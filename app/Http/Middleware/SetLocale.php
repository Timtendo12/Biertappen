<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetLocale
{
    /** Dutch is primary; English is the secondary. Adding a locale is a one-line change here. */
    public const SUPPORTED = ['nl', 'en'];

    public const DEFAULT = 'nl';

    public function handle(Request $request, Closure $next): Response
    {
        app()->setLocale($this->resolve($request));

        return $next($request);
    }

    /**
     * Signed-in users carry their choice on the account; guests carry it in the
     * session. Accept-Language only breaks the tie for a first-time visitor.
     */
    private function resolve(Request $request): string
    {
        $candidates = [
            $request->user()?->locale,
            $request->session()->get('locale'),
            $request->getPreferredLanguage(self::SUPPORTED),
        ];

        foreach ($candidates as $candidate) {
            if (is_string($candidate) && in_array($candidate, self::SUPPORTED, true)) {
                return $candidate;
            }
        }

        return self::DEFAULT;
    }
}
