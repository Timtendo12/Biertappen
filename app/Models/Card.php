<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['type', 'participants_mode', 'participants_count', 'content', 'position'])]
class Card extends Model
{
    use HasFactory;

    public const PARTICIPANTS_COUNT = 'count';

    public const PARTICIPANTS_ALL = 'all';

    protected function casts(): array
    {
        return [
            'content' => 'array',
            'participants_count' => 'integer',
            'position' => 'integer',
        ];
    }

    public function deck(): BelongsTo
    {
        return $this->belongsTo(Deck::class);
    }

    public function targetsEveryone(): bool
    {
        return $this->participants_mode === self::PARTICIPANTS_ALL;
    }

    /**
     * MIN(participants) over a set of cards, treating an all-players card as
     * needing one person.
     *
     * Returned unordered on purpose: MariaDB rejects an aggregate alongside an
     * ORDER BY on a non-grouped column, and the cards() relation sorts by
     * position by default.
     */
    public static function minimumPlayersSubquery(): \Illuminate\Database\Eloquent\Builder
    {
        return static::query()
            ->reorder()
            ->selectRaw(
                'MIN(CASE WHEN participants_mode = ? THEN 1 ELSE participants_count END) as min_players',
                [self::PARTICIPANTS_ALL],
            );
    }

    /**
     * Cards a roster of $playerCount can actually play. The engine filters again
     * client-side; this exists so large decks do not ship unplayable cards over the wire.
     */
    public function scopePlayableWith(Builder $query, int $playerCount): Builder
    {
        return $query->where(function (Builder $q) use ($playerCount) {
            $q->where('participants_mode', self::PARTICIPANTS_ALL)
                ->orWhere('participants_count', '<=', $playerCount);
        });
    }
}
