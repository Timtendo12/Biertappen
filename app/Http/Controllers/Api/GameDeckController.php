<?php

namespace App\Http\Controllers\Api;

use App\Domain\Deck\DeckPlayPayload;
use App\Http\Controllers\Controller;
use App\Models\Card;
use App\Models\Deck;
use Illuminate\Contracts\Auth\Access\Gate;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * The two endpoints gameplay depends on.
 *
 * Deliberately the *only* network traffic a running game generates: the deck is
 * fetched once, and every card after that is produced locally by the engine.
 */
class GameDeckController extends Controller
{
    public function __construct(
        private readonly DeckPlayPayload $payload,
        private readonly Gate $gate,
    ) {}

    /** Decks this visitor may choose from. */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        $decks = Deck::query()
            ->withCount('cards')
            // Correlated subquery rather than a per-deck lookup: browsing decks
            // must stay one query no matter how many there are.
            ->addSelect(['min_players' => Card::minimumPlayersSubquery()
                ->whereColumn('cards.deck_id', 'decks.id')
                ->limit(1)])
            ->having('cards_count', '>', 0)
            ->where(function ($query) use ($user) {
                // Base game: published, owner-less, available to everyone.
                $query->where(fn ($q) => $q->whereNull('owner_id')->where('status', Deck::STATUS_PUBLISHED));

                if ($user) {
                    $query->orWhere('owner_id', $user->id);
                }
            })
            ->orderByDesc('featured')
            ->orderBy('name')
            ->get();

        return response()->json([
            'decks' => $decks->map(fn (Deck $deck) => $this->payload->forList($deck))->values(),
        ]);
    }

    /**
     * Full card payload for one game.
     *
     * The gate is asked on behalf of the current user *or* a guest, so a private
     * deck stays unreachable by uuid alone even though the uuid is the URL key.
     * A denied deck 404s rather than 403s: whether it exists is not public either.
     */
    public function play(Request $request, Deck $deck): JsonResponse
    {
        abort_unless($this->gate->forUser($request->user())->allows('play', $deck), 404, __('deck.not_found'));

        return response()->json(['deck' => $this->payload->forGame($deck)]);
    }
}
