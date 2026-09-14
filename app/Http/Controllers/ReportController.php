<?php

namespace App\Http\Controllers;

use App\Models\Deck;
use App\Models\Report;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Reporting a shared deck.
 *
 * Because anyone with the creator can write arbitrary card text and share it,
 * there has to be a way to flag it. Deliberately open to guests — a share link
 * reaches people who have no account, and they are exactly the people most
 * likely to encounter something they want to report.
 */
class ReportController extends Controller
{
    public function store(Request $request, Deck $deck): RedirectResponse
    {
        // Nothing to report about the base game or your own deck.
        abort_unless($request->user()?->can('report', $deck) ?? ! $deck->isSystemDeck(), 403);

        $validated = $request->validate([
            'reason' => ['required', Rule::in(Report::REASONS)],
            'description' => ['nullable', 'string', 'max:2000'],
        ]);

        /*
         * One open report per deck per reporter. Without this, a single
         * disgruntled visitor could bury the moderation queue by tapping the
         * button repeatedly.
         */
        $existing = Report::query()
            ->where('deck_id', $deck->id)
            ->where('status', Report::STATUS_OPEN)
            ->when(
                $request->user(),
                fn ($query) => $query->where('reporter_id', $request->user()->id),
                fn ($query) => $query->whereNull('reporter_id'),
            )
            ->exists();

        if (! $existing) {
            Report::create([
                'deck_id' => $deck->id,
                'reporter_id' => $request->user()?->id,
                'reason' => $validated['reason'],
                'description' => $validated['description'] ?? null,
                'status' => Report::STATUS_OPEN,
            ]);
        }

        // The same message either way: whether a previous report exists is not
        // something a reporter needs — or should be able — to probe for.
        return back()->with('success', __('report.submitted'));
    }
}
