<?php

namespace App\Http\Controllers;

use App\Domain\Deck\DeckPlayPayload;
use App\Models\Deck;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Landing page for a shared deck link.
 *
 * The 32-character token is the credential, so it is looked up directly and a
 * miss is a plain 404 — no listing, no enumeration, and no hint that a deck with
 * that name exists. Revoking the link revokes access on the next request.
 */
class SharedDeckController extends Controller
{
    public function __construct(private readonly DeckPlayPayload $payload) {}

    public function show(string $token): Response
    {
        $deck = Deck::query()
            ->where('share_token', $token)
            ->where('visibility', Deck::VISIBILITY_SHARED)
            ->where('status', Deck::STATUS_PUBLISHED)
            ->withCount('cards')
            ->firstOrFail();

        return Inertia::render('SharedDeck', [
            'deck' => $this->payload->forList($deck),
        ]);
    }
}
