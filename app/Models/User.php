<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use LemonSqueezy\Laravel\Billable;

#[Fillable(['name', 'email', 'password', 'locale'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable implements MustVerifyEmail
{
    /** @use HasFactory<UserFactory> */
    /*
     * Billable provides checkout() and the customer/order relations. It does
     * not grant anything on its own: entitlements authorise access, and only
     * the webhook writes those.
     */
    use Billable, HasFactory, Notifiable;

    public const ROLE_USER = 'user';

    public const ROLE_ADMIN = 'admin';

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function decks(): HasMany
    {
        return $this->hasMany(Deck::class, 'owner_id');
    }

    public function entitlements(): HasMany
    {
        return $this->hasMany(Entitlement::class);
    }

    public function donations(): HasMany
    {
        return $this->hasMany(Donation::class);
    }

    public function oauthAccounts(): HasMany
    {
        return $this->hasMany(OauthAccount::class);
    }

    public function isAdmin(): bool
    {
        return $this->role === self::ROLE_ADMIN;
    }

    /**
     * The authoritative premium check. Read from the database on every call so a
     * revoked or refunded entitlement takes effect on the very next request.
     */
    public function hasEntitlement(string $type): bool
    {
        return $this->entitlements()
            ->where('type', $type)
            ->where('status', Entitlement::STATUS_ACTIVE)
            ->exists();
    }

    /**
     * Google-only accounts have no password and must not be offered password login.
     */
    public function hasPassword(): bool
    {
        return filled($this->password);
    }
}
