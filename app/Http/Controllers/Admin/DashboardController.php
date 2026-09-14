<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Card;
use App\Models\Deck;
use App\Models\Donation;
use App\Models\Entitlement;
use App\Models\Report;
use App\Models\User;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Platform overview.
 *
 * Deliberately a handful of counts rather than a charting dashboard: these are
 * the numbers that tell the operator whether anything needs attention today.
 */
class DashboardController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('admin/Dashboard', [
            'stats' => [
                'users' => User::count(),
                'admins' => User::where('role', User::ROLE_ADMIN)->count(),
                'base_decks' => Deck::whereNull('owner_id')->count(),
                'published_base_decks' => Deck::baseGame()->count(),
                'user_decks' => Deck::whereNotNull('owner_id')->count(),
                'shared_decks' => Deck::whereNotNull('share_token')->count(),
                'cards' => Card::count(),
                'active_entitlements' => Entitlement::where('status', Entitlement::STATUS_ACTIVE)->count(),
                'open_reports' => Report::where('status', Report::STATUS_OPEN)->count(),
                'donations' => Donation::where('status', Donation::STATUS_PAID)->count(),
                'donated_cents' => (int) Donation::where('status', Donation::STATUS_PAID)->sum('amount_cents'),
            ],
        ]);
    }
}
