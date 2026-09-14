<?php

namespace App\Providers;

use App\Http\Controllers\Billing\WebhookController;
use App\Http\Middleware\VerifyLemonSqueezySignature;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use LemonSqueezy\Laravel\LemonSqueezy;

/**
 * Takes over the two things the Lemon Squeezy package would otherwise own.
 *
 * Migrations: published into database/migrations so we control the schema — the
 * upstream orders migration is incompatible with MariaDB in strict mode.
 *
 * Routes: replaced with our own webhook controller, which deduplicates
 * redelivered events and handles guest donations that the package's handler
 * rejects outright.
 */
class LemonSqueezyServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        LemonSqueezy::ignoreMigrations();
        LemonSqueezy::ignoreRoutes();
    }

    public function boot(): void
    {
        // Same path the package would have used, so the URL registered in the
        // Lemon Squeezy dashboard is the conventional one.
        Route::post(config('lemon-squeezy.path', 'lemon-squeezy').'/webhook', WebhookController::class)
            // Signature verification is attached here rather than inside the
            // controller: calling the package's handler directly bypasses any
            // middleware it declares on itself, so it must live on the route.
            ->middleware(VerifyLemonSqueezySignature::class)
            ->name('lemon-squeezy.webhook');
    }
}
