<?php

namespace App\Policies;

use App\Models\Deck;
use App\Models\Entitlement;
use App\Models\User;

/**
 * Every deck authorisation decision lives here.
 *
 * Controllers ask this; they never re-derive ownership rules of their own. The
 * admin short-circuit is deliberate and explicit rather than a `before` hook, so
 * each method still reads as its own complete rule.
 */
class DeckPolicy
{
    /** Playing a deck: guests included, hence the nullable user. */
    public function play(?User $user, Deck $deck): bool
    {
        if ($deck->isSystemDeck()) {
            return $deck->status === Deck::STATUS_PUBLISHED;
        }

        if ($user && ($deck->owner_id === $user->id || $user->isAdmin())) {
            return true;
        }

        // Shared decks are reachable by anyone holding the link; the token is
        // checked when resolving the deck, not here.
        return $deck->isPlayablePublicly();
    }

    public function view(?User $user, Deck $deck): bool
    {
        return $this->play($user, $deck);
    }

    /** Creating a deck is the premium feature; administrators are exempt. */
    public function create(User $user): bool
    {
        return $user->isAdmin() || $user->hasEntitlement(Entitlement::TYPE_DECK_CREATOR);
    }

    public function update(User $user, Deck $deck): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        // A base-game deck has no owner, so an ordinary user can never edit one
        // even if they somehow reach the route.
        return ! $deck->isSystemDeck() && $deck->owner_id === $user->id;
    }

    public function delete(User $user, Deck $deck): bool
    {
        return $this->update($user, $deck);
    }

    /**
     * Duplicating someone else's shared deck is allowed — it creates an
     * independent copy — but it still requires deck-creator access to own one.
     */
    public function duplicate(User $user, Deck $deck): bool
    {
        return $this->create($user) && $this->view($user, $deck);
    }

    public function export(User $user, Deck $deck): bool
    {
        return $this->view($user, $deck);
    }

    /** Only the owner decides whether a deck is shared, and admins for base decks. */
    public function share(User $user, Deck $deck): bool
    {
        return $this->update($user, $deck);
    }

    /** Publishing into the base game is an administrator action, always. */
    public function publish(User $user, Deck $deck): bool
    {
        return $user->isAdmin();
    }

    public function report(?User $user, Deck $deck): bool
    {
        // Nothing to report about your own private deck.
        return ! $deck->isSystemDeck() && $deck->owner_id !== $user?->id;
    }
}
