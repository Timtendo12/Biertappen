<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

#[Fillable([
    'name', 'description', 'locale', 'visibility', 'status', 'featured',
    'ending_mode', 'ending_count', 'tags',
])]
class Deck extends Model
{
    use HasFactory, SoftDeletes;

    public const VISIBILITY_PRIVATE = 'private';

    public const VISIBILITY_SHARED = 'shared';

    public const VISIBILITY_PUBLIC = 'public';

    public const STATUS_DRAFT = 'draft';

    public const STATUS_PUBLISHED = 'published';

    public const STATUS_HIDDEN = 'hidden';

    public const ENDING_CARDS = 'cards';

    public const ENDING_ALL_CARDS_ONCE = 'all_cards_once';

    protected function casts(): array
    {
        return [
            'featured' => 'boolean',
            'tags' => 'array',
            'ending_count' => 'integer',
            'schema_version' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Deck $deck) {
            $deck->uuid ??= (string) Str::uuid();
        });
    }

    public function getRouteKeyName(): string
    {
        // Never expose sequential database ids in URLs.
        return 'uuid';
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function cards(): HasMany
    {
        return $this->hasMany(Card::class)->orderBy('position');
    }

    public function reports(): HasMany
    {
        return $this->hasMany(Report::class);
    }

    /** A deck with no owner is part of the base game. */
    public function isSystemDeck(): bool
    {
        return $this->owner_id === null;
    }

    public function isPlayablePublicly(): bool
    {
        return $this->status === self::STATUS_PUBLISHED
            && in_array($this->visibility, [self::VISIBILITY_PUBLIC, self::VISIBILITY_SHARED], true);
    }

    /** Base-game decks offered to every player, including guests. */
    public function scopeBaseGame(Builder $query): Builder
    {
        return $query->whereNull('owner_id')
            ->where('status', self::STATUS_PUBLISHED);
    }

    public function scopeOwnedBy(Builder $query, ?int $userId): Builder
    {
        return $query->where('owner_id', $userId);
    }
}
