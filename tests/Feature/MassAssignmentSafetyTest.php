<?php

use App\Models\Card;
use App\Models\Deck;
use App\Models\Donation;
use App\Models\Entitlement;
use App\Models\OauthAccount;
use App\Models\Report;
use App\Models\User;
use App\Models\WebhookEvent;

/*
 * Guards a failure mode this codebase has hit repeatedly.
 *
 * Privilege-bearing columns (users.role, decks.share_token) are deliberately
 * left out of their model's fillable list so no request payload can ever set
 * them. The trap is that Eloquent then drops them from ->update([...]) *without
 * error* — the controller runs, flashes success, and changes nothing. That is
 * invisible until someone notices the feature silently does not work.
 *
 * This asserts every mass-assigned key in app/ is actually fillable somewhere,
 * so the next occurrence fails here instead of in production.
 */

it('never mass-assigns a field that is not fillable', function () {
    $fillable = collect([
        Deck::class, Card::class, User::class, Entitlement::class,
        Donation::class, Report::class, WebhookEvent::class, OauthAccount::class,
    ])
        ->flatMap(fn (string $model) => (new $model)->getFillable())
        ->unique()
        ->all();

    expect($fillable)->not->toBeEmpty();

    $offenders = [];
    $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator(base_path('app')));

    foreach ($files as $file) {
        if ($file->getExtension() !== 'php') {
            continue;
        }

        $source = (string) file_get_contents($file->getPathname());

        if (! preg_match_all('/->(?:update|fill)\(\[(.*?)\]\)/s', $source, $calls)) {
            continue;
        }

        foreach ($calls[1] as $body) {
            preg_match_all('/[\'"]([a-z_]+)[\'"]\s*=>/', $body, $keys);

            foreach ($keys[1] as $key) {
                if (! in_array($key, $fillable, true)) {
                    $offenders[] = "{$file->getFilename()}: '{$key}'";
                }
            }
        }
    }

    expect($offenders)->toBe([], implode(
        ' | ',
        ['Mass-assigned but not fillable — the write will be silently dropped: '.implode(', ', $offenders)],
    ));
});

it('keeps privilege-bearing columns out of mass assignment', function () {
    // These are the columns that decide who can do what. If any becomes
    // fillable, a crafted request payload could set it.
    expect((new User)->getFillable())->not->toContain('role')
        ->and((new Deck)->getFillable())->not->toContain('share_token')
        ->and((new Deck)->getFillable())->not->toContain('owner_id')
        ->and((new Entitlement)->getFillable())->not->toContain('id');
});
