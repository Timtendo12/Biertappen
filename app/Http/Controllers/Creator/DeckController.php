<?php

namespace App\Http\Controllers\Creator;

use App\Domain\Deck\DeckExporter;
use App\Domain\Deck\DeckPlayPayload;
use App\Http\Controllers\Controller;
use App\Http\Requests\DeckRequest;
use App\Models\Card;
use App\Models\Deck;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * The premium deck creator.
 *
 * Ownership is assigned here from the authenticated user and never accepted
 * from input, and every deck-scoped action runs through DeckPolicy. The route
 * group also carries the entitlement middleware, so there are two independent
 * server-side gates on anything that creates a deck.
 */
class DeckController extends Controller
{
    public function __construct(
        private readonly DeckExporter $exporter,
        private readonly DeckPlayPayload $payload,
    ) {}

    public function index(Request $request): Response
    {
        $decks = Deck::query()
            ->where('owner_id', $request->user()->id)
            ->withCount('cards')
            ->orderByDesc('updated_at')
            ->get();

        return Inertia::render('creator/Index', [
            'decks' => $decks->map(fn (Deck $deck) => [
                ...$this->payload->forList($deck),
                'visibility' => $deck->visibility,
                'status' => $deck->status,
                'share_url' => $deck->share_token ? url("/d/{$deck->share_token}") : null,
                'updated_at' => $deck->updated_at?->toIso8601String(),
            ])->values(),
        ]);
    }

    public function store(DeckRequest $request): RedirectResponse
    {
        $deck = new Deck($request->deckAttributes());

        // Assigned server-side from the session, never from the payload.
        $deck->owner_id = $request->user()->id;
        $deck->visibility = Deck::VISIBILITY_PRIVATE;
        $deck->status = Deck::STATUS_DRAFT;
        $deck->save();

        return redirect()
            ->route('creator.decks.edit', $deck)
            ->with('success', __('deck.created'));
    }

    public function edit(Request $request, Deck $deck): Response
    {
        $this->authorize('update', $deck);

        $deck->load('cards');

        return Inertia::render('creator/DeckEditor', [
            'deck' => [
                'uuid' => $deck->uuid,
                'name' => $deck->name,
                'description' => (string) $deck->description,
                'locale' => $deck->locale,
                'ending_mode' => $deck->ending_mode,
                'ending_count' => $deck->ending_count,
                'tags' => $deck->tags ?? [],
                'visibility' => $deck->visibility,
                'share_url' => $deck->share_token ? url("/d/{$deck->share_token}") : null,
                'cards' => $deck->cards->map(fn (Card $card) => $this->cardPayload($card))->values(),
            ],
        ]);
    }

    public function update(DeckRequest $request, Deck $deck): RedirectResponse
    {
        $this->authorize('update', $deck);

        $deck->update($request->deckAttributes());

        return back()->with('success', __('deck.saved'));
    }

    public function destroy(Request $request, Deck $deck): RedirectResponse
    {
        $this->authorize('delete', $deck);

        $deck->delete();

        return redirect()->route('creator.index')->with('success', __('deck.deleted'));
    }

    /**
     * Copy a deck under the current user.
     *
     * Deep-copies the cards so the two decks are genuinely independent, and
     * never touches the original — duplicating someone else's shared deck must
     * leave their copy exactly as it was.
     */
    public function duplicate(Request $request, Deck $deck): RedirectResponse
    {
        $this->authorize('duplicate', $deck);

        $copy = DB::transaction(function () use ($deck, $request) {
            $copy = $deck->replicate([
                'uuid', 'owner_id', 'share_token', 'status', 'visibility',
                'featured', 'created_at', 'updated_at', 'deleted_at',
            ]);

            $copy->uuid = (string) Str::uuid();
            $copy->owner_id = $request->user()->id;
            $copy->name = Str::limit($deck->name.' '.__('deck.copy_suffix'), 120, '');
            $copy->visibility = Deck::VISIBILITY_PRIVATE;
            $copy->status = Deck::STATUS_DRAFT;
            $copy->featured = false;
            $copy->share_token = null;
            $copy->save();

            $now = now();
            $rows = $deck->cards()->get()->map(fn (Card $card) => [
                'deck_id' => $copy->id,
                'type' => $card->type,
                'participants_mode' => $card->participants_mode,
                'participants_count' => $card->participants_count,
                'content' => json_encode($card->content, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE),
                'position' => $card->position,
                'created_at' => $now,
                'updated_at' => $now,
            ])->all();

            foreach (array_chunk($rows, 200) as $chunk) {
                Card::insert($chunk);
            }

            return $copy;
        });

        return redirect()
            ->route('creator.decks.edit', $copy)
            ->with('success', __('deck.duplicated'));
    }

    /** Issue or rotate the share link. Rotating invalidates the previous one. */
    public function share(Request $request, Deck $deck): RedirectResponse
    {
        $this->authorize('share', $deck);

        // Assigned directly rather than mass-assigned: share_token is a
        // credential and is deliberately absent from the model's fillable list,
        // so no request payload can ever set or guess-overwrite it.
        $deck->share_token = Str::random(32);
        $deck->visibility = Deck::VISIBILITY_SHARED;
        $deck->status = Deck::STATUS_PUBLISHED;
        $deck->save();

        return back()->with('success', __('deck.shared'));
    }

    /** Withdraw the link; anyone holding it loses access immediately. */
    public function unshare(Request $request, Deck $deck): RedirectResponse
    {
        $this->authorize('share', $deck);

        $deck->share_token = null;
        $deck->visibility = Deck::VISIBILITY_PRIVATE;
        $deck->status = Deck::STATUS_DRAFT;
        $deck->save();

        return back()->with('success', __('deck.unshared'));
    }

    public function export(Request $request, Deck $deck): StreamedResponse
    {
        $this->authorize('export', $deck);

        $json = $this->exporter->toJson($deck);

        return response()->streamDownload(
            fn () => print($json),
            $this->exporter->filename($deck),
            ['Content-Type' => 'application/json'],
        );
    }

    private function cardPayload(Card $card): array
    {
        return [
            'id' => $card->id,
            'type' => $card->type,
            'participants' => $card->targetsEveryone() ? 'all' : (int) $card->participants_count,
            'content' => $card->content,
            'position' => $card->position,
        ];
    }
}
