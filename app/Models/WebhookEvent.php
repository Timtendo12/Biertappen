<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Prunable;

/**
 * One row per logical webhook, used to make handling exactly-once.
 *
 * Lemon Squeezy sends no event id, so identity is (provider, event_name,
 * resource_id) — stable across retries and dashboard resends of the same event.
 */
#[Fillable(['provider', 'event_name', 'resource_id', 'payload', 'processed_at', 'error'])]
class WebhookEvent extends Model
{
    use Prunable;

    /**
     * Rows exist to deduplicate retries, and Lemon Squeezy stops retrying
     * within minutes. A month is far beyond any redelivery window while
     * still leaving a usable audit trail; keeping them forever would grow a
     * table nothing reads.
     */
    public function prunable(): Builder
    {
        return static::query()->where('created_at', '<', now()->subDays(30));
    }

    public const PROVIDER_LEMON_SQUEEZY = 'lemon_squeezy';

    protected function casts(): array
    {
        return [
            'payload' => 'array',
            'processed_at' => 'datetime',
        ];
    }
}
