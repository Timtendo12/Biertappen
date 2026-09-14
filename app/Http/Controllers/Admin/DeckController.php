<?php

namespace App\Http\Controllers\Admin;

use App\Domain\Deck\DeckImporter;
use App\Domain\Deck\DeckValidationException;
use App\Domain\Deck\DeckValidator;
use App\Http\Controllers\Controller;
use App\Http\Requests\DeckRequest;
use App\Models\Deck;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Base-game deck curation.
 *
 * Creates and publishes decks with no owner — that absent owner is what makes a
 * deck part of the base game. Editing happens in the ordinary creator, which
 * DeckPolicy already opens to administrators, so there is one deck editor in
 * the product rather than two that drift apart.
 */
class DeckController extends Controller
{
    public function __construct(private readonly DeckImporter $importer) {}

    public function index(Request $request): Response
    {
        $decks = Deck::query()
            ->withCount('cards')
            ->with('owner:id,name')
            ->withCount(['reports' => fn ($q) => $q->where('status', 'open')])
            ->orderByDesc('featured')
            ->orderByRaw('owner_id IS NOT NULL')
            ->orderBy('name')
            ->get();

        return Inertia::render('admin/Decks', [
            'decks' => $decks->map(fn (Deck $deck) => [
                'uuid' => $deck->uuid,
                'name' => $deck->name,
                'description' => (string) $deck->description,
                'card_count' => $deck->cards_count,
                'open_reports' => $deck->reports_count,
                'status' => $deck->status,
                'visibility' => $deck->visibility,
                'featured' => (bool) $deck->featured,
                'is_base_game' => $deck->isSystemDeck(),
                'owner' => $deck->owner?->name,
            ])->values(),
        ]);
    }

    /** Create an ownerless deck — that is what makes it part of the base game. */
    public function store(DeckRequest $request): RedirectResponse
    {
        $deck = new Deck($request->deckAttributes());

        $deck->owner_id = null;
        $deck->visibility = Deck::VISIBILITY_PUBLIC;
        $deck->status = Deck::STATUS_DRAFT;
        $deck->save();

        // Straight into the shared editor: admins author base decks with the
        // same tool users author theirs with.
        return redirect()->route('creator.decks.edit', $deck);
    }

    public function import(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'file' => ['required', 'file', 'max:'.(DeckValidator::MAX_BYTES / 1024)],
        ]);

        $json = file_get_contents($validated['file']->getRealPath());

        if ($json === false) {
            throw ValidationException::withMessages(['file' => __('deck.unreadable')]);
        }

        try {
            // Null owner: an admin import lands in the base game, not on their account.
            $deck = $this->importer->importJson($json, null);
        } catch (DeckValidationException $e) {
            throw ValidationException::withMessages($e->toValidationErrors('file'));
        }

        return redirect()
            ->route('creator.decks.edit', $deck)
            ->with('success', __('deck.import_success'));
    }

    /**
     * Publishing makes a base deck appear in deck selection for every player,
     * so it is gated on the deck actually having cards.
     */
    public function publish(Deck $deck): RedirectResponse
    {
        if ($deck->cards()->count() === 0) {
            return back()->with('error', __('deck.cannot_publish_empty'));
        }

        $deck->update([
            'status' => Deck::STATUS_PUBLISHED,
            'visibility' => $deck->isSystemDeck() ? Deck::VISIBILITY_PUBLIC : $deck->visibility,
        ]);

        return back()->with('success', __('deck.published'));
    }

    public function unpublish(Deck $deck): RedirectResponse
    {
        $deck->update(['status' => Deck::STATUS_DRAFT]);

        return back()->with('success', __('deck.unpublished'));
    }

    /** Takes a deck out of circulation without deleting it — the moderation lever. */
    public function hide(Deck $deck): RedirectResponse
    {
        $deck->update(['status' => Deck::STATUS_HIDDEN, 'featured' => false]);

        return back()->with('success', __('deck.hidden'));
    }

    public function feature(Request $request, Deck $deck): RedirectResponse
    {
        $deck->update(['featured' => ! $deck->featured]);

        return back()->with('success', __('deck.saved'));
    }

    public function destroy(Deck $deck): RedirectResponse
    {
        $deck->delete();

        return redirect()->route('admin.decks.index')->with('success', __('deck.deleted'));
    }
}
