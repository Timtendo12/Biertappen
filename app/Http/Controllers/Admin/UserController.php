<?php

namespace App\Http\Controllers\Admin;

use App\Domain\Billing\EntitlementService;
use App\Http\Controllers\Controller;
use App\Models\Entitlement;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

/**
 * User and entitlement administration.
 *
 * Manual grants are why the entitlements table exists as its own concept rather
 * than being derived from purchases: support needs to be able to hand someone
 * the creator after a failed payment, and take it back after an abuse report,
 * with neither action involving Lemon Squeezy.
 */
class UserController extends Controller
{
    public function __construct(private readonly EntitlementService $entitlements) {}

    public function index(Request $request): Response
    {
        $search = trim((string) $request->query('search', ''));

        $users = User::query()
            ->when($search !== '', fn ($query) => $query->where(
                fn ($q) => $q->where('name', 'like', "%{$search}%")->orWhere('email', 'like', "%{$search}%"),
            ))
            ->withCount('decks')
            ->with(['entitlements' => fn ($q) => $q->where('status', Entitlement::STATUS_ACTIVE)])
            ->orderByDesc('created_at')
            ->paginate(25)
            ->withQueryString();

        return Inertia::render('admin/Users', [
            'search' => $search,
            'users' => $users->through(fn (User $user) => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
                'verified' => $user->hasVerifiedEmail(),
                'deck_count' => $user->decks_count,
                'has_deck_creator' => $user->entitlements->isNotEmpty(),
                'created_at' => $user->created_at?->toDateString(),
            ]),
        ]);
    }

    public function updateRole(Request $request, User $user): RedirectResponse
    {
        $validated = $request->validate([
            'role' => ['required', Rule::in([User::ROLE_USER, User::ROLE_ADMIN])],
        ]);

        /*
         * An administrator may not demote themselves. Without this, the last
         * admin can lock everyone out of the panel with one tap and no way back
         * in short of editing the database by hand.
         */
        if ($user->is($request->user()) && $validated['role'] !== User::ROLE_ADMIN) {
            return back()->with('error', __('admin.cannot_demote_self'));
        }

        // Assigned directly, not mass-assigned: `role` is deliberately absent
        // from the model's fillable list so no request payload anywhere in the
        // app can escalate a privilege. update() here would silently do nothing.
        $user->role = $validated['role'];
        $user->save();

        return back()->with('success', __('admin.role_updated'));
    }

    public function grantEntitlement(Request $request, User $user): RedirectResponse
    {
        $this->entitlements->grant(
            $user,
            config('billing.premium_entitlement', Entitlement::TYPE_DECK_CREATOR),
            Entitlement::SOURCE_MANUAL,
        );

        return back()->with('success', __('admin.entitlement_granted'));
    }

    public function revokeEntitlement(Request $request, User $user): RedirectResponse
    {
        $this->entitlements->revoke(
            $user,
            config('billing.premium_entitlement', Entitlement::TYPE_DECK_CREATOR),
        );

        return back()->with('success', __('admin.entitlement_revoked'));
    }
}
