<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\OauthAccount;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\InvalidStateException;
use Symfony\Component\HttpFoundation\RedirectResponse as SymfonyRedirect;

class GoogleController extends Controller
{
    public function redirect(): SymfonyRedirect
    {
        return Socialite::driver('google')->redirect();
    }

    public function callback(): RedirectResponse
    {
        try {
            $googleUser = Socialite::driver('google')->user();
        } catch (InvalidStateException) {
            // Stale or tampered OAuth state: start over rather than guess.
            return redirect('/login')->with('error', __('auth.oauth_failed'));
        }

        $providerId = $googleUser->getId();
        $email = $googleUser->getEmail();

        if (! $providerId || ! $email) {
            return redirect('/login')->with('error', __('auth.oauth_failed'));
        }

        /*
         * Linking by email is only safe when Google asserts the address is verified.
         * Without that check, anyone who can register an unverified Google account
         * for someone else's address could take over their Biertappen account.
         */
        $emailVerified = (bool) ($googleUser->user['email_verified'] ?? false);

        $user = DB::transaction(function () use ($providerId, $email, $googleUser, $emailVerified) {
            $link = OauthAccount::query()
                ->where('provider', 'google')
                ->where('provider_user_id', $providerId)
                ->first();

            if ($link) {
                return $link->user;
            }

            $existing = User::query()->where('email', $email)->first();

            if ($existing && ! $emailVerified) {
                return null;
            }

            $user = $existing ?? User::create([
                'name' => $googleUser->getName() ?: Str::before($email, '@'),
                'email' => $email,
                'password' => null,
                'locale' => app()->getLocale(),
            ]);

            // Signing in through a verified Google address is itself proof of ownership.
            if ($emailVerified && ! $user->hasVerifiedEmail()) {
                $user->forceFill(['email_verified_at' => now()])->save();
            }

            $user->oauthAccounts()->create([
                'provider' => 'google',
                'provider_user_id' => $providerId,
                'avatar_url' => $googleUser->getAvatar(),
            ]);

            return $user;
        });

        if (! $user) {
            return redirect('/login')->with('error', __('auth.oauth_email_taken'));
        }

        Auth::login($user, remember: true);
        request()->session()->regenerate();

        return redirect()->intended('/');
    }
}
