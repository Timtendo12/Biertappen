<?php

use App\Domain\Deck\DeckImporter;
use App\Domain\Deck\DeckValidator;
use App\Models\User;

/*
 * docs/deck-format.md is the contract an AI or a human is handed when asked to
 * write a deck. Documentation that drifts from the validator is worse than none:
 * it produces files that look right and get rejected. So the examples in it are
 * extracted and actually run.
 */

function documentationSource(): string
{
    $path = base_path('docs/deck-format.md');

    expect(file_exists($path))->toBeTrue("docs/deck-format.md is missing");

    return (string) file_get_contents($path);
}

/** @return array<int, array<string, mixed>> Every ```json block in the document. */
function documentedJsonBlocks(): array
{
    preg_match_all('/```json\n(.*?)```/s', documentationSource(), $matches);

    return array_values(array_filter(array_map(
        fn (string $block) => json_decode(trim($block), true),
        $matches[1],
    )));
}

it('contains only well-formed JSON examples', function () {
    preg_match_all('/```json\n(.*?)```/s', documentationSource(), $matches);

    expect($matches[1])->not->toBeEmpty();

    foreach ($matches[1] as $index => $block) {
        expect(json_decode(trim($block), true))
            ->not->toBeNull("JSON example #{$index} in docs/deck-format.md does not parse");
    }
});

it('documents a complete deck that actually imports', function () {
    $decks = array_values(array_filter(
        documentedJsonBlocks(),
        fn (array $block) => isset($block['format'], $block['deck']),
    ));

    expect($decks)->not->toBeEmpty('The document shows no complete deck example');

    foreach ($decks as $deck) {
        $imported = app(DeckImporter::class)->import(
            app(DeckValidator::class)->validate($deck),
            User::factory()->create(),
        );

        expect($imported->cards()->count())->toBeGreaterThan(0);
    }
});

it('documents card examples that all validate', function () {
    // Standalone card snippets — the ones an author is most likely to copy.
    $cards = array_values(array_filter(
        documentedJsonBlocks(),
        fn (array $block) => isset($block['type'], $block['participants'], $block['content']),
    ));

    expect($cards)->toHaveCount(4, 'Expected the four worked card examples');

    foreach ($cards as $card) {
        expect(app(DeckValidator::class)->validateCard($card))->toBeArray();
    }
});

it('documents amount option snippets that validate on a real card', function () {
    $snippets = array_values(array_filter(
        documentedJsonBlocks(),
        fn (array $block) => ($block['value'] ?? null) === 'amount' && isset($block['options']),
    ));

    expect($snippets)->toHaveCount(2, 'Expected the fixed and range amount examples');

    foreach ($snippets as $snippet) {
        app(DeckValidator::class)->validateCard([
            'type' => 'drinking',
            'participants' => 1,
            'content' => [['type' => 'text', 'value' => 'Drink '], $snippet],
        ]);
    }
})->throwsNoExceptions();

it('documents both ending shapes correctly', function () {
    $endings = array_values(array_filter(
        documentedJsonBlocks(),
        fn (array $block) => isset($block['mode']) && ! isset($block['type']),
    ));

    expect($endings)->toHaveCount(2);

    foreach ($endings as $ending) {
        app(DeckValidator::class)->validate([
            'format' => 'biertappen-deck',
            'version' => 1,
            'deck' => [
                'name' => 'Ending check',
                'description' => '',
                'ending' => $ending,
                'cards' => [[
                    'type' => 'custom',
                    'participants' => 1,
                    'content' => [['type' => 'text', 'value' => 'Proost.']],
                ]],
            ],
        ]);
    }
})->throwsNoExceptions();

it('states the real variable list, with nothing missing or invented', function () {
    $documented = [];
    preg_match_all('/`(player\d+|all_players|amount)`/', documentationSource(), $matches);
    $documented = array_unique($matches[1]);

    $registry = json_decode((string) file_get_contents(resource_path('deck/variables.json')), true);
    $actual = array_column($registry['variables'], 'id');

    // The doc uses a "player1 … player10" range rather than listing all ten.
    foreach (['all_players', 'amount', 'player1', 'player2', 'player10'] as $expected) {
        expect($documented)->toContain($expected);
    }

    // Nothing documented may be absent from the registry.
    expect(array_diff($documented, $actual))->toBe([]);
});
