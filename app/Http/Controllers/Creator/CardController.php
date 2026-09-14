<?php

namespace App\Http\Controllers\Creator;

use App\Http\Controllers\Controller;
use App\Http\Requests\CardRequest;
use App\Models\Card;
use App\Models\Deck;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Cards within a deck.
 *
 * Every action authorises against the *deck*, not the card: permission to touch
 * a card is permission to edit the deck it belongs to. Cards are also
 * re-resolved through the deck relation so a card id from another deck cannot be
 * operated on by passing a deck the caller happens to own.
 */
class CardController extends Controller
{
    public function store(CardRequest $request, Deck $deck): RedirectResponse
    {
        $this->authorize('update', $deck);

        $deck->cards()->create([
            ...$request->cardAttributes(),
            'position' => (int) $deck->cards()->max('position') + 1,
        ]);

        $deck->touch();

        return back()->with('success', __('deck.card_added'));
    }

    public function update(CardRequest $request, Deck $deck, Card $card): RedirectResponse
    {
        $this->authorize('update', $deck);

        $this->assertBelongsTo($deck, $card);

        $card->update($request->cardAttributes());
        $deck->touch();

        return back()->with('success', __('deck.card_saved'));
    }

    public function destroy(Request $request, Deck $deck, Card $card): RedirectResponse
    {
        $this->authorize('update', $deck);

        $this->assertBelongsTo($deck, $card);

        $card->delete();
        $deck->touch();

        return back()->with('success', __('deck.card_deleted'));
    }

    public function duplicate(Request $request, Deck $deck, Card $card): RedirectResponse
    {
        $this->authorize('update', $deck);

        $this->assertBelongsTo($deck, $card);

        $copy = $card->replicate(['created_at', 'updated_at']);
        $copy->position = (int) $deck->cards()->max('position') + 1;
        $copy->save();

        $deck->touch();

        return back()->with('success', __('deck.card_duplicated'));
    }

    /**
     * Persist a new card order.
     *
     * Positions are rewritten from the submitted sequence rather than trusting
     * per-card indexes, so a partial or duplicated list cannot leave the deck
     * with colliding positions.
     */
    public function reorder(Request $request, Deck $deck): RedirectResponse
    {
        $this->authorize('update', $deck);

        $validated = $request->validate([
            'ids' => ['required', 'array'],
            'ids.*' => ['integer'],
        ]);

        $owned = $deck->cards()->pluck('id')->all();
        $submitted = array_values(array_unique($validated['ids']));

        // Reordering must be a permutation of this deck's cards, nothing else.
        if (array_diff($submitted, $owned) !== [] || count($submitted) !== count($owned)) {
            throw ValidationException::withMessages(['ids' => __('deck.reorder_mismatch')]);
        }

        DB::transaction(function () use ($submitted, $deck) {
            foreach ($submitted as $position => $id) {
                Card::query()->where('id', $id)->update(['position' => $position]);
            }

            $deck->touch();
        });

        return back();
    }

    /** A card id is only meaningful inside the deck that owns it. */
    private function assertBelongsTo(Deck $deck, Card $card): void
    {
        abort_unless($card->deck_id === $deck->id, 404);
    }
}
