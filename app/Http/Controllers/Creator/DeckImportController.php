<?php

namespace App\Http\Controllers\Creator;

use App\Domain\Deck\DeckImporter;
use App\Domain\Deck\DeckValidationException;
use App\Domain\Deck\DeckValidator;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

/**
 * Importing a deck from a JSON file.
 *
 * The uploaded bytes are never trusted: they are size-capped before parsing,
 * validated against the published schema, and the resulting deck is assigned to
 * the caller and forced private regardless of what the file claims.
 */
class DeckImportController extends Controller
{
    public function __construct(
        private readonly DeckImporter $importer,
    ) {}

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'file' => [
                'required',
                'file',
                'mimetypes:application/json,text/plain',
                'max:'.(DeckValidator::MAX_BYTES / 1024),
            ],
        ]);

        $json = file_get_contents($validated['file']->getRealPath());

        if ($json === false) {
            throw ValidationException::withMessages(['file' => __('deck.unreadable')]);
        }

        try {
            $deck = $this->importer->importJson($json, $request->user());
        } catch (DeckValidationException $e) {
            // Surfaced on the form that submitted the file, in the user's
            // language, rather than as a stack trace.
            throw ValidationException::withMessages($e->toValidationErrors('file'));
        }

        return redirect()
            ->route('creator.decks.edit', $deck)
            ->with('success', __('deck.import_success'));
    }
}
