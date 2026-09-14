<?php

use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\DeckController as AdminDeckController;
use App\Http\Controllers\Admin\ReportController as AdminReportController;
use App\Http\Controllers\Admin\UserController as AdminUserController;
use App\Http\Controllers\Api\GameDeckController;
use App\Http\Controllers\Auth\GoogleController;
use App\Http\Controllers\Billing\CheckoutController;
use App\Http\Controllers\Creator\CardController;
use App\Http\Controllers\Creator\DeckController as CreatorDeckController;
use App\Http\Controllers\Creator\DeckImportController;
use App\Http\Controllers\SharedDeckController;
use App\Http\Controllers\LocaleController;
use App\Http\Controllers\ReportController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

/*
|--------------------------------------------------------------------------
| Public game routes
|--------------------------------------------------------------------------
|
| Everything a guest needs to play. No authentication anywhere in this group:
| entering names and playing a base deck must never require an account.
*/

Route::get('/', fn () => Inertia::render('Home'))->name('home');

Route::get('/settings', fn () => Inertia::render('Settings'))->name('settings');

Route::get('/account', fn () => Inertia::render('Account'))
    ->middleware('auth')
    ->name('account');

Route::get('/decks', fn () => Inertia::render('DeckSelect'))->name('decks');
Route::get('/play/{deck}', fn (App\Models\Deck $deck) => Inertia::render('Game', [
    'deckUuid' => $deck->uuid,
]))->name('play');

/*
 * The engine's data endpoints. Session-authenticated like the rest of the app;
 * a guest simply sees only base-game decks.
 */
Route::prefix('api')->group(function () {
    Route::get('/decks', [GameDeckController::class, 'index'])->name('api.decks.index');
    Route::get('/decks/{deck}/play', [GameDeckController::class, 'play'])->name('api.decks.play');
});

Route::post('/locale', [LocaleController::class, 'update'])
    ->middleware('throttle:30,1')
    ->name('locale.update');

// A shared deck link. The token is the credential; a miss is a plain 404.
Route::get('/d/{token}', [SharedDeckController::class, 'show'])
    ->middleware('throttle:60,1')
    ->name('decks.shared');

/*
|--------------------------------------------------------------------------
| Deck creator (premium)
|--------------------------------------------------------------------------
|
| Two independent server-side gates: the entitlement middleware on the group,
| and DeckPolicy on every deck-scoped action. Hiding the UI is presentation;
| these are what actually deny access.
*/

Route::middleware(['auth', 'verified', 'entitled:deck_creator'])
    ->prefix('creator')
    ->name('creator.')
    ->group(function () {
        Route::get('/', [CreatorDeckController::class, 'index'])->name('index');

        Route::post('/decks', [CreatorDeckController::class, 'store'])->name('decks.store');
        Route::post('/import', [DeckImportController::class, 'store'])->name('decks.import');

        Route::prefix('decks/{deck}')->name('decks.')->group(function () {
            Route::get('/', [CreatorDeckController::class, 'edit'])->name('edit');
            Route::put('/', [CreatorDeckController::class, 'update'])->name('update');
            Route::delete('/', [CreatorDeckController::class, 'destroy'])->name('destroy');
            Route::get('/export', [CreatorDeckController::class, 'export'])->name('export');
            Route::post('/duplicate', [CreatorDeckController::class, 'duplicate'])->name('duplicate');
            Route::post('/share', [CreatorDeckController::class, 'share'])->name('share');
            Route::delete('/share', [CreatorDeckController::class, 'unshare'])->name('unshare');

            Route::post('/cards', [CardController::class, 'store'])->name('cards.store');
            Route::post('/cards/reorder', [CardController::class, 'reorder'])->name('cards.reorder');
            Route::put('/cards/{card}', [CardController::class, 'update'])->name('cards.update');
            Route::delete('/cards/{card}', [CardController::class, 'destroy'])->name('cards.destroy');
            Route::post('/cards/{card}/duplicate', [CardController::class, 'duplicate'])->name('cards.duplicate');
        });
    });

// Reporting a shared deck. Open to guests: a share link reaches people with no
// account, and they are the ones most likely to encounter something worth flagging.
Route::post('/decks/{deck}/report', [ReportController::class, 'store'])
    ->middleware('throttle:10,1')
    ->name('decks.report');

/*
|--------------------------------------------------------------------------
| Administration
|--------------------------------------------------------------------------
|
| Enforced server-side by the admin middleware, which 404s rather than 403s so
| an unauthorised visitor learns nothing about which routes exist.
*/

Route::middleware(['auth', 'admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

    Route::get('/decks', [AdminDeckController::class, 'index'])->name('decks.index');
    Route::post('/decks', [AdminDeckController::class, 'store'])->name('decks.store');
    Route::post('/decks/import', [AdminDeckController::class, 'import'])->name('decks.import');
    Route::post('/decks/{deck}/publish', [AdminDeckController::class, 'publish'])->name('decks.publish');
    Route::post('/decks/{deck}/unpublish', [AdminDeckController::class, 'unpublish'])->name('decks.unpublish');
    Route::post('/decks/{deck}/hide', [AdminDeckController::class, 'hide'])->name('decks.hide');
    Route::post('/decks/{deck}/feature', [AdminDeckController::class, 'feature'])->name('decks.feature');
    Route::delete('/decks/{deck}', [AdminDeckController::class, 'destroy'])->name('decks.destroy');

    Route::get('/users', [AdminUserController::class, 'index'])->name('users.index');
    Route::put('/users/{user}/role', [AdminUserController::class, 'updateRole'])->name('users.role');
    Route::post('/users/{user}/entitlement', [AdminUserController::class, 'grantEntitlement'])->name('users.entitlement.grant');
    Route::delete('/users/{user}/entitlement', [AdminUserController::class, 'revokeEntitlement'])->name('users.entitlement.revoke');

    Route::get('/reports', [AdminReportController::class, 'index'])->name('reports.index');
    Route::put('/reports/{report}', [AdminReportController::class, 'update'])->name('reports.update');
});

/*
|--------------------------------------------------------------------------
| Billing — Lemon Squeezy
|--------------------------------------------------------------------------
|
| These only ever hand back a checkout URL. Nothing here grants access: the
| webhook (registered in LemonSqueezyServiceProvider, outside this group so it
| carries no session or CSRF) is the only writer of entitlements.
*/

Route::post('/billing/checkout', [CheckoutController::class, 'premium'])
    ->middleware(['auth', 'throttle:10,1'])
    ->name('billing.checkout');

// Guests included, by design: "Doneer een biertje" must not require an account.
Route::post('/billing/donate', [CheckoutController::class, 'donate'])
    ->middleware('throttle:10,1')
    ->name('billing.donate');

Route::get('/billing/thanks', [CheckoutController::class, 'thanks'])->name('billing.thanks');

/*
|--------------------------------------------------------------------------
| Google OAuth
|--------------------------------------------------------------------------
|
| Full page loads, not Inertia visits — the handshake leaves the origin.
*/

Route::middleware('guest')->group(function () {
    Route::get('/auth/google/redirect', [GoogleController::class, 'redirect'])
        ->name('auth.google.redirect');

    Route::get('/auth/google/callback', [GoogleController::class, 'callback'])
        ->middleware('throttle:10,1')
        ->name('auth.google.callback');
});
