<?php

namespace App\Domain\Deck;

use RuntimeException;

class DeckValidationException extends RuntimeException
{
    /**
     * @param  array<int, array{path: string, message: string}>  $errors
     */
    public function __construct(public readonly array $errors)
    {
        parent::__construct('The deck file is not valid.');
    }

    /**
     * Shaped for Laravel's validator bag so controllers can hand these straight
     * back to the form that submitted the file.
     *
     * @return array<string, array<int, string>>
     */
    public function toValidationErrors(string $field = 'deck'): array
    {
        $messages = array_map(
            fn (array $error) => $error['path'] === ''
                ? $error['message']
                : "{$error['path']}: {$error['message']}",
            $this->errors,
        );

        return [$field => array_values(array_unique($messages))];
    }
}
