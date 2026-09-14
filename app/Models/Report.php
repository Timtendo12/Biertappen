<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['deck_id', 'reporter_id', 'reason', 'description', 'status', 'resolved_by', 'resolved_at'])]
class Report extends Model
{
    use HasFactory;

    public const STATUS_OPEN = 'open';

    public const STATUS_REVIEWED = 'reviewed';

    public const STATUS_ACTIONED = 'actioned';

    public const STATUS_DISMISSED = 'dismissed';

    public const REASONS = ['offensive', 'illegal', 'spam', 'other'];

    protected function casts(): array
    {
        return ['resolved_at' => 'datetime'];
    }

    public function deck(): BelongsTo
    {
        return $this->belongsTo(Deck::class);
    }

    public function reporter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reporter_id');
    }

    public function resolver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'resolved_by');
    }
}
