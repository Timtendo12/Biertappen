<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['user_id', 'type', 'status', 'source', 'lemon_squeezy_order_id', 'granted_at', 'revoked_at'])]
class Entitlement extends Model
{
    use HasFactory;

    public const TYPE_DECK_CREATOR = 'deck_creator';

    public const STATUS_ACTIVE = 'active';

    public const STATUS_REVOKED = 'revoked';

    public const SOURCE_PURCHASE = 'purchase';

    public const SOURCE_MANUAL = 'manual';

    protected function casts(): array
    {
        return [
            'granted_at' => 'datetime',
            'revoked_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * The Lemon Squeezy order that granted this, if any.
     *
     * Not a relation: the order lives in a package-owned table, and a manual
     * admin grant has no order at all.
     */
    public function grantedByPurchase(): bool
    {
        return filled($this->lemon_squeezy_order_id);
    }

    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }
}
