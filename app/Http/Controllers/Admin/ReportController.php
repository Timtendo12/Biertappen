<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Report;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

/**
 * The moderation queue.
 *
 * Intentionally minimal: a list, the reported card text, and three outcomes.
 * Version one needs a way to act on a report, not a moderation platform.
 */
class ReportController extends Controller
{
    public function index(Request $request): Response
    {
        $status = (string) $request->query('status', Report::STATUS_OPEN);

        $reports = Report::query()
            ->when(
                in_array($status, [Report::STATUS_OPEN, Report::STATUS_REVIEWED, Report::STATUS_ACTIONED, Report::STATUS_DISMISSED], true),
                fn ($query) => $query->where('status', $status),
            )
            ->with(['deck:id,uuid,name,status,owner_id', 'deck.cards:id,deck_id,content', 'reporter:id,name'])
            ->orderByDesc('created_at')
            ->paginate(25)
            ->withQueryString();

        return Inertia::render('admin/Reports', [
            'status' => $status,
            'reports' => $reports->through(fn (Report $report) => [
                'id' => $report->id,
                'reason' => $report->reason,
                'description' => $report->description,
                'status' => $report->status,
                'created_at' => $report->created_at?->toDateTimeString(),
                'reporter' => $report->reporter?->name,
                'deck' => $report->deck ? [
                    'uuid' => $report->deck->uuid,
                    'name' => $report->deck->name,
                    'status' => $report->deck->status,
                    'card_count' => $report->deck->cards->count(),
                ] : null,
            ]),
        ]);
    }

    public function update(Request $request, Report $report): RedirectResponse
    {
        $validated = $request->validate([
            'status' => ['required', Rule::in([
                Report::STATUS_REVIEWED,
                Report::STATUS_ACTIONED,
                Report::STATUS_DISMISSED,
            ])],
        ]);

        $report->update([
            'status' => $validated['status'],
            'resolved_at' => now(),
            // Recorded so a decision can always be traced to the person who made it.
            'resolved_by' => $request->user()->id,
        ]);

        return back()->with('success', __('report.resolved'));
    }
}
