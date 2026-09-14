<?php

namespace App\Domain\Deck;

use Opis\JsonSchema\Errors\ErrorFormatter;
use Opis\JsonSchema\Validator;
use RuntimeException;

/**
 * Validates an external deck document in two passes.
 *
 * 1. Structure, against schemas/biertappen-deck-v1.schema.json — the published
 *    contract, used verbatim so the documentation and the check can never drift.
 * 2. Semantics that JSON Schema cannot express: a card must not reference a
 *    player it did not declare, all_players belongs to 'all' cards, and an
 *    amount range must not be inverted.
 *
 * Imported JSON is never trusted; nothing reaches the database before both
 * passes succeed.
 */
class DeckValidator
{
    public const FORMAT = 'biertappen-deck';

    public const VERSION = 1;

    /** Guards against a multi-megabyte upload being parsed at all. */
    public const MAX_BYTES = 2 * 1024 * 1024;

    public static function schemaPath(): string
    {
        return base_path('schemas/biertappen-deck-v1.schema.json');
    }

    /**
     * Decode a raw JSON string into a validated deck document.
     *
     * @return array<string, mixed>
     *
     * @throws DeckValidationException
     */
    public function validateJson(string $json): array
    {
        if (strlen($json) > self::MAX_BYTES) {
            throw new DeckValidationException([[
                'path' => '',
                'message' => __('deck.too_large', ['max' => self::MAX_BYTES / 1024 / 1024]),
            ]]);
        }

        try {
            $decoded = json_decode($json, true, depth: 64, flags: JSON_THROW_ON_ERROR);
        } catch (\JsonException $e) {
            throw new DeckValidationException([[
                'path' => '',
                'message' => __('deck.malformed_json', ['error' => $e->getMessage()]),
            ]]);
        }

        if (! is_array($decoded)) {
            throw new DeckValidationException([[
                'path' => '',
                'message' => __('deck.not_an_object'),
            ]]);
        }

        return $this->validate($decoded);
    }

    /**
     * @param  array<string, mixed>  $document
     * @return array<string, mixed>
     *
     * @throws DeckValidationException
     */
    public function validate(array $document): array
    {
        $this->validateAgainstSchema($document);
        $this->validateSemantics($document);

        return $document;
    }

    /**
     * Validate one card, as the deck creator submits it.
     *
     * Wraps the card in a throwaway deck document and runs the exact same two
     * passes an import gets. A card built in the editor and a card imported from
     * JSON therefore cannot be judged by different rules — there is only one
     * definition of a valid card, and it is the published schema.
     *
     * @param  array<string, mixed>  $card
     * @return array<string, mixed>
     *
     * @throws DeckValidationException
     */
    public function validateCard(array $card): array
    {
        try {
            $this->validate([
                'format' => self::FORMAT,
                'version' => self::VERSION,
                'deck' => [
                    'name' => 'card-validation',
                    'description' => '',
                    'ending' => ['mode' => 'all_cards_once'],
                    'cards' => [$card],
                ],
            ]);
        } catch (DeckValidationException $e) {
            // Re-point paths at the card itself: the wrapper deck is an internal
            // detail and must not leak into messages shown in the editor.
            throw new DeckValidationException(array_map(
                fn (array $error) => [
                    'path' => (string) preg_replace('#^deck/cards/0/?#', '', $error['path']),
                    'message' => $error['message'],
                ],
                $e->errors,
            ));
        }

        return $card;
    }

    /** @param array<string, mixed> $document */
    private function validateAgainstSchema(array $document): void
    {
        $schema = @file_get_contents(self::schemaPath());

        if ($schema === false) {
            throw new RuntimeException('Deck schema not found at '.self::schemaPath());
        }

        $validator = new Validator();
        $validator->resolver()?->registerRaw($schema, 'https://biertappen.app/schemas/biertappen-deck-v1.schema.json');

        // Opis validates against stdClass, not associative arrays.
        $result = $validator->validate(
            json_decode(json_encode($document, JSON_THROW_ON_ERROR), false),
            $schema,
        );

        if ($result->isValid()) {
            return;
        }

        $formatted = (new ErrorFormatter())->format($result->error(), false);

        $errors = [];
        foreach ($formatted as $path => $messages) {
            foreach ((array) $messages as $message) {
                $errors[] = ['path' => trim($path, '/') ?: '', 'message' => $message];
            }
        }

        throw new DeckValidationException($errors);
    }

    /**
     * Rules the schema cannot express.
     *
     * @param  array<string, mixed>  $document
     */
    private function validateSemantics(array $document): void
    {
        $errors = [];
        $cards = $document['deck']['cards'] ?? [];

        foreach ($cards as $index => $card) {
            $participants = $card['participants'] ?? null;
            $targetsEveryone = $participants === 'all';

            foreach ($card['content'] as $position => $segment) {
                if (($segment['type'] ?? null) !== 'variable') {
                    continue;
                }

                $variable = $segment['value'];
                $path = "deck/cards/{$index}/content/{$position}";

                if (! VariableRegistry::exists($variable)) {
                    $errors[] = ['path' => $path, 'message' => __('deck.unknown_variable', ['variable' => $variable])];

                    continue;
                }

                $playerIndex = VariableRegistry::playerIndex($variable);

                if ($playerIndex !== null) {
                    if ($targetsEveryone) {
                        $errors[] = [
                            'path' => $path,
                            'message' => __('deck.player_variable_on_all_card', ['variable' => $variable]),
                        ];
                    } elseif ($playerIndex > (int) $participants) {
                        $errors[] = [
                            'path' => $path,
                            'message' => __('deck.player_variable_out_of_range', [
                                'variable' => $variable,
                                'participants' => $participants,
                            ]),
                        ];
                    }
                }

                if ($variable === 'all_players' && ! $targetsEveryone) {
                    $errors[] = ['path' => $path, 'message' => __('deck.all_players_requires_all')];
                }

                $options = $segment['options'] ?? null;
                if ($variable === 'amount' && ($options['mode'] ?? null) === 'range'
                    && $options['min'] > $options['max']) {
                    $errors[] = ['path' => $path, 'message' => __('deck.amount_range_inverted')];
                }
            }
        }

        if ($errors !== []) {
            throw new DeckValidationException($errors);
        }
    }
}
