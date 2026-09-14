<?php

namespace App\Http\Requests;

use App\Http\Middleware\SetLocale;
use App\Models\Deck;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Deck metadata and ending configuration.
 *
 * Note what is absent: owner, status, featured and share token are never read
 * from the request. They are decided by the controller from the caller's
 * identity and by explicit actions, so a crafted payload cannot publish a deck
 * into the base game or reassign it.
 */
class DeckRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'min:1', 'max:120'],
            'description' => ['nullable', 'string', 'max:1000'],
            'locale' => ['nullable', 'string', Rule::in(SetLocale::SUPPORTED)],
            'ending_mode' => ['required', Rule::in([Deck::ENDING_CARDS, Deck::ENDING_ALL_CARDS_ONCE])],

            // Required for, and only for, the cards mode — mirroring the
            // conditional the JSON schema enforces on imports.
            'ending_count' => [
                Rule::requiredIf(fn () => $this->input('ending_mode') === Deck::ENDING_CARDS),
                'nullable',
                'integer',
                'min:1',
                'max:10000',
            ],

            'tags' => ['nullable', 'array', 'max:20'],
            'tags.*' => ['string', 'max:32', 'regex:/^[a-z0-9][a-z0-9 _-]*$/'],
        ];
    }

    public function deckAttributes(): array
    {
        $endingMode = (string) $this->input('ending_mode');

        return [
            'name' => (string) $this->input('name'),
            'description' => (string) $this->input('description', ''),
            'locale' => (string) $this->input('locale', app()->getLocale()),
            'ending_mode' => $endingMode,
            // Null it out rather than carrying a stale count into a mode that
            // must not have one; the exporter would otherwise emit invalid JSON.
            'ending_count' => $endingMode === Deck::ENDING_CARDS
                ? (int) $this->input('ending_count')
                : null,
            'tags' => array_values($this->input('tags', [])),
        ];
    }
}
