<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Applies the learner's language to everything they see.
 *
 * Four sources, in order of how much they mean: an explicit choice held for the
 * visit (session), the choice saved on the account (so it travels to a new
 * phone), the same choice from a previous visit on this browser (cookie), and
 * finally the browser's own `Accept-Language` — so a Romanian visitor lands on
 * a Romanian page without ever finding the switcher, which is the whole point.
 *
 * The admin panel is deliberately excluded: it is an internal tool with one
 * audience, and translating it would double the copy for nobody's benefit.
 */
class SetLocale
{
    /**
     * Languages the frontend can render, from config/platform.php.
     *
     * @return array<string, array{native: string, prompt: string}>
     */
    public static function locales(): array
    {
        return config('platform.locales');
    }

    /** @return array<int, string> */
    public static function codes(): array
    {
        return array_keys(static::locales());
    }

    public static function isSupported(?string $locale): bool
    {
        return $locale !== null && array_key_exists($locale, static::locales());
    }

    public function handle(Request $request, Closure $next): Response
    {
        if ($request->is('admin*')) {
            return $next($request);
        }

        $chosen = $request->session()->get('locale')
            ?? $request->user()?->locale
            ?? $request->cookie('locale');

        // getPreferredLanguage() falls back to the first code it was given, so
        // an unmatched browser language lands on the default rather than null.
        app()->setLocale(static::isSupported($chosen)
            ? $chosen
            : $request->getPreferredLanguage(static::codes()));

        return $next($request);
    }
}
