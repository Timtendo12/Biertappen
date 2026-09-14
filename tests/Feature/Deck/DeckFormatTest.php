<?php

use App\Domain\Deck\DeckExporter;
use App\Domain\Deck\DeckImporter;
use App\Domain\Deck\DeckValidationException;
use App\Domain\Deck\DeckValidator;
use App\Models\Card;
use App\Models\Deck;
use App\Models\User;

/**
 * The deck JSON format is a public contract, so it is tested as one: valid
 * documents survive a full round trip, and invalid ones are rejected before a
 * single row is written.
 */
function deckDocument(array $overrides = []): array
{
    return array_replace_recursive([
        'format' => 'biertappen-deck',
        'version' => 1,
        'deck' => [
            'name' => 'Klassiek Biertappen',
            'description' => 'Een verzameling klassieke kaarten.',
            'locale' => 'nl',
            'tags' => ['klassiek'],
            'ending' => ['mode' => 'cards', 'count' => 30],
            'cards' => [
                [
                    'type' => 'drinking',
                    'participants' => 1,
                    'content' => [
                        ['type' => 'variable', 'value' => 'player1'],
                        ['type' => 'text', 'value' => ' neemt '],
                        ['type' => 'variable', 'value' => 'amount', 'options' => ['mode' => 'range', 'min' => 1, 'max' => 5]],
                        ['type' => 'text', 'value' => ' slokken.'],
                    ],
                ],
            ],
        ],
    ], $overrides);
}

function deckValidator(): DeckValidator
{
    return app(DeckValidator::class);
}

describe('accepting valid decks', function () {
    it('accepts the documented example', function () {
        expect(deckValidator()->validate(deckDocument()))->toBeArray();
    });

    it('accepts a card that targets everyone', function () {
        $doc = deckDocument();
        $doc['deck']['cards'] = [[
            'type' => 'drinking',
            'participants' => 'all',
            'content' => [
                ['type' => 'variable', 'value' => 'all_players'],
                ['type' => 'text', 'value' => ' nemen 2 slokken.'],
            ],
        ]];

        expect(deckValidator()->validate($doc))->toBeArray();
    });

    it('accepts a fixed amount', function () {
        $doc = deckDocument();
        $doc['deck']['cards'][0]['content'][2]['options'] = ['mode' => 'fixed', 'value' => 3];

        expect(deckValidator()->validate($doc))->toBeArray();
    });

    it('accepts an unknown card type without a schema change', function () {
        $doc = deckDocument();
        $doc['deck']['cards'][0]['type'] = 'karaoke';

        expect(deckValidator()->validate($doc))->toBeArray();
    });

    it('accepts all_cards_once without a count', function () {
        $doc = deckDocument();
        $doc['deck']['ending'] = ['mode' => 'all_cards_once'];

        expect(deckValidator()->validate($doc))->toBeArray();
    });
});

describe('rejecting invalid decks', function () {
    it('rejects a wrong format marker', function () {
        $doc = deckDocument();
        $doc['format'] = 'something-else';

        expect(fn () => deckValidator()->validate($doc))->toThrow(DeckValidationException::class);
    });

    it('rejects a deck with no cards', function () {
        $doc = deckDocument();
        $doc['deck']['cards'] = [];

        expect(fn () => deckValidator()->validate($doc))->toThrow(DeckValidationException::class);
    });

    it('rejects cards mode without a count', function () {
        $doc = deckDocument();
        $doc['deck']['ending'] = ['mode' => 'cards'];

        expect(fn () => deckValidator()->validate($doc))->toThrow(DeckValidationException::class);
    });

    it('rejects all_cards_once carrying a count', function () {
        $doc = deckDocument();
        $doc['deck']['ending'] = ['mode' => 'all_cards_once', 'count' => 10];

        expect(fn () => deckValidator()->validate($doc))->toThrow(DeckValidationException::class);
    });

    it('rejects an unknown variable', function () {
        $doc = deckDocument();
        $doc['deck']['cards'][0]['content'][0] = ['type' => 'variable', 'value' => 'player99'];

        expect(fn () => deckValidator()->validate($doc))->toThrow(DeckValidationException::class);
    });

    it('rejects a player variable beyond the declared participant count', function () {
        $doc = deckDocument();
        $doc['deck']['cards'][0]['participants'] = 2;
        $doc['deck']['cards'][0]['content'][0] = ['type' => 'variable', 'value' => 'player3'];

        expect(fn () => deckValidator()->validate($doc))
            ->toThrow(DeckValidationException::class);
    });

    it('rejects all_players on a card that does not target everyone', function () {
        $doc = deckDocument();
        $doc['deck']['cards'][0]['content'][0] = ['type' => 'variable', 'value' => 'all_players'];

        expect(fn () => deckValidator()->validate($doc))->toThrow(DeckValidationException::class);
    });

    it('rejects an individual player variable on an all-players card', function () {
        $doc = deckDocument();
        $doc['deck']['cards'][0]['participants'] = 'all';

        expect(fn () => deckValidator()->validate($doc))->toThrow(DeckValidationException::class);
    });

    it('rejects an inverted amount range', function () {
        $doc = deckDocument();
        $doc['deck']['cards'][0]['content'][2]['options'] = ['mode' => 'range', 'min' => 9, 'max' => 2];

        expect(fn () => deckValidator()->validate($doc))->toThrow(DeckValidationException::class);
    });

    it('rejects options on a player variable', function () {
        $doc = deckDocument();
        $doc['deck']['cards'][0]['content'][0]['options'] = ['mode' => 'fixed', 'value' => 2];

        expect(fn () => deckValidator()->validate($doc))->toThrow(DeckValidationException::class);
    });

    it('rejects unknown properties rather than silently dropping them', function () {
        $doc = deckDocument();
        $doc['deck']['is_admin_deck'] = true;

        expect(fn () => deckValidator()->validate($doc))->toThrow(DeckValidationException::class);
    });

    it('rejects malformed JSON', function () {
        expect(fn () => deckValidator()->validateJson('{"format":'))->toThrow(DeckValidationException::class);
    });

    it('rejects a payload larger than the cap without parsing it', function () {
        $huge = str_repeat('a', DeckValidator::MAX_BYTES + 1);

        expect(fn () => deckValidator()->validateJson($huge))->toThrow(DeckValidationException::class);
    });
});

describe('import', function () {
    it('stores cards as structured content', function () {
        $owner = User::factory()->create();

        $deck = app(DeckImporter::class)->import(deckDocument(), $owner);

        expect($deck->cards)->toHaveCount(1)
            ->and($deck->cards->first()->content[0]['value'])->toBe('player1')
            ->and($deck->cards->first()->participants_mode)->toBe(Card::PARTICIPANTS_COUNT)
            ->and($deck->cards->first()->participants_count)->toBe(1);
    });

    it('assigns ownership from the caller, never from the file', function () {
        $owner = User::factory()->create();

        $deck = app(DeckImporter::class)->import(deckDocument(), $owner);

        expect($deck->owner_id)->toBe($owner->id);
    });

    it('always lands private and unpublished', function () {
        $deck = app(DeckImporter::class)->import(deckDocument(), User::factory()->create());

        expect($deck->visibility)->toBe(Deck::VISIBILITY_PRIVATE)
            ->and($deck->status)->toBe(Deck::STATUS_DRAFT);
    });

    it('records an all-players card correctly', function () {
        $doc = deckDocument();
        $doc['deck']['cards'] = [[
            'type' => 'drinking',
            'participants' => 'all',
            'content' => [['type' => 'variable', 'value' => 'all_players']],
        ]];

        $deck = app(DeckImporter::class)->import($doc, User::factory()->create());

        expect($deck->cards->first()->participants_mode)->toBe(Card::PARTICIPANTS_ALL)
            ->and($deck->cards->first()->participants_count)->toBeNull();
    });
});

describe('export', function () {
    it('round-trips a deck without loss', function () {
        $original = app(DeckImporter::class)->import(deckDocument(), User::factory()->create());

        $exported = app(DeckExporter::class)->toArray($original);

        expect(deckValidator()->validate($exported))->toBeArray()
            ->and($exported['deck']['name'])->toBe('Klassiek Biertappen')
            ->and($exported['deck']['cards'])->toBe(deckDocument()['deck']['cards']);
    });

    it('survives a second round trip byte for byte', function () {
        $owner = User::factory()->create();
        $importer = app(DeckImporter::class);
        $exporter = app(DeckExporter::class);

        $first = $exporter->toJson($importer->import(deckDocument(), $owner));
        $second = $exporter->toJson($importer->importJson($first, $owner));

        expect($second)->toBe($first);
    });

    it('leaks no internal identifiers', function () {
        $deck = app(DeckImporter::class)->import(deckDocument(), User::factory()->create());

        $exported = app(DeckExporter::class)->toArray($deck);

        expect($exported['deck'])->not->toHaveKeys(['id', 'uuid', 'owner_id', 'share_token', 'created_at'])
            ->and($exported['deck']['cards'][0])->not->toHaveKeys(['id', 'deck_id', 'position']);
    });

    it('omits the count for all_cards_once decks', function () {
        $doc = deckDocument();
        $doc['deck']['ending'] = ['mode' => 'all_cards_once'];

        $deck = app(DeckImporter::class)->import($doc, User::factory()->create());

        expect(app(DeckExporter::class)->toArray($deck)['deck']['ending'])
            ->toBe(['mode' => 'all_cards_once']);
    });
});
