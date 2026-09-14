<?php

namespace App\Http\Requests;

use App\Domain\Deck\DeckValidationException;
use App\Domain\Deck\DeckValidator;
use App\Models\Card;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

/**
 * A card submitted from the deck creator.
 *
 * Shape is checked by Laravel; meaning is checked by DeckValidator, which is the
 * published JSON schema plus its semantic rules. Routing it through the same
 * validator an import uses is deliberate — a card the editor accepts must be a
 * card the format accepts, or exports would produce files that fail to import.
 */
class CardRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'type' => ['required', 'string', 'max:50', 'regex:/^[a-z0-9_-]+$/'],
            'participants' => ['required'],
            'content' => ['required', 'array', 'min:1', 'max:100'],
            'content.*.type' => ['required', 'string', 'in:text,variable'],
            'content.*.value' => ['required', 'string'],
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator) {
                // Only worth running once the shape is sound; otherwise the
                // schema errors would just restate what Laravel already said.
                if ($validator->errors()->isNotEmpty()) {
                    return;
                }

                try {
                    app(DeckValidator::class)->validateCard($this->cardPayload());
                } catch (DeckValidationException $e) {
                    foreach ($e->errors as $error) {
                        $validator->errors()->add(
                            $error['path'] === '' ? 'content' : 'content',
                            $error['message'],
                        );
                    }
                }
            },
        ];
    }

    /** The card in external format — the shape the validator and exporter speak. */
    public function cardPayload(): array
    {
        $participants = $this->input('participants');

        return [
            'type' => (string) $this->input('type'),
            'participants' => $participants === 'all' ? 'all' : (int) $participants,
            'content' => $this->input('content', []),
        ];
    }

    /** The same card as database columns. */
    public function cardAttributes(): array
    {
        $card = $this->cardPayload();
        $everyone = $card['participants'] === 'all';

        return [
            'type' => $card['type'],
            'participants_mode' => $everyone ? Card::PARTICIPANTS_ALL : Card::PARTICIPANTS_COUNT,
            'participants_count' => $everyone ? null : $card['participants'],
            'content' => $card['content'],
        ];
    }
}
