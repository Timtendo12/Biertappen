<?php

namespace App\Http\Controllers;

use App\Http\Middleware\SetLocale;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class LocaleController extends Controller
{
    /**
     * Persist the player's language choice.
     *
     * Guests keep it in the session; signed-in users keep it on the account so it
     * follows them to another device. Either way the server stays the source of
     * truth for the <html lang> attribute and for server-rendered messages.
     */
    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'locale' => ['required', 'string', Rule::in(SetLocale::SUPPORTED)],
        ]);

        $request->session()->put('locale', $validated['locale']);

        $request->user()?->forceFill(['locale' => $validated['locale']])->save();

        return back();
    }
}
