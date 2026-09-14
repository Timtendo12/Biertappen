<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A "Doneer een biertje" gift.
 *
 * Deliberately has no relationship to entitlements: donating supports the maker
 * and unlocks nothing. `user_id` is nullable because guests can donate.
 */
#[Fillable([
    'lemon_squeezy_order_id', 'user_id', 'amount_cents', 'currency',
    'status', 'email', 'order_number', 'ordered_at',
])]
class Donation extends Model
{
    use HasFactory;

    public const STATUS_PAID = 'paid';

    public const STATUS_REFUNDED = 'refunded';

    protected function casts(): array
    {
        return [
            'amount_cents' => 'integer',
            'ordered_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
