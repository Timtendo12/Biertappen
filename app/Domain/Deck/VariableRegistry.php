<?php

namespace App\Domain\Deck;

use RuntimeException;

/**
 * Reads resources/deck/variables.json — the single definition of what a card
 * variable is. The TypeScript engine imports the same file, so PHP validation
 * and client-side resolution cannot drift apart.
 */
class VariableRegistry
{
    /** @var array<string, array<string, mixed>>|null */
    private static ?array $byId = null;

    private static ?int $maxPlayerVariables = null;

    /** @return array<string, array<string, mixed>> */
    public static function all(): array
    {
        if (self::$byId === null) {
            self::load();
        }

        return self::$byId;
    }

    public static function exists(string $id): bool
    {
        return isset(self::all()[$id]);
    }

    public static function kind(string $id): ?string
    {
        return self::all()[$id]['kind'] ?? null;
    }

    /** The N in playerN, or null for non-player variables. */
    public static function playerIndex(string $id): ?int
    {
        $variable = self::all()[$id] ?? null;

        return ($variable['kind'] ?? null) === 'player' ? (int) $variable['index'] : null;
    }

    public static function maxPlayerVariables(): int
    {
        if (self::$maxPlayerVariables === null) {
            self::load();
        }

        return self::$maxPlayerVariables;
    }

    /** @return array<string, int> */
    public static function defaultAmountRange(): array
    {
        $options = self::all()['amount']['defaultOptions'] ?? [];

        return [
            'min' => (int) ($options['min'] ?? 1),
            'max' => (int) ($options['max'] ?? 5),
        ];
    }

    public static function path(): string
    {
        return resource_path('deck/variables.json');
    }

    /** Test seam: forces a reload after the registry file is swapped. */
    public static function flush(): void
    {
        self::$byId = null;
        self::$maxPlayerVariables = null;
    }

    private static function load(): void
    {
        $raw = @file_get_contents(self::path());

        if ($raw === false) {
            throw new RuntimeException('Variable registry not found at '.self::path());
        }

        $decoded = json_decode($raw, true, flags: JSON_THROW_ON_ERROR);

        self::$maxPlayerVariables = (int) ($decoded['maxPlayerVariables'] ?? 10);
        self::$byId = [];

        foreach ($decoded['variables'] ?? [] as $variable) {
            self::$byId[$variable['id']] = $variable;
        }
    }
}
