<?php

namespace App\Http\Middleware;

use App\Support\Locales;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\View;
use Symfony\Component\HttpFoundation\Response;

/**
 * Resolves the active locale for every request and remembers the choice.
 *
 * No page URL carries a locale, so the language is resolved from, in order: the
 * `locale` route parameter (only the `locale.set` endpoint has one), the
 * session, the `ds_locale` cookie, the browser's `Accept-Language`, then the
 * configured default. Whatever wins is written back to the session and the
 * cookie so the next request resolves it without negotiation.
 */
class SetLocale
{
    public function handle(Request $request, Closure $next): Response
    {
        $supported = Locales::codes();

        // The route's own locale parameter is a request to switch to that
        // language, not a hint. Accepting it and then quietly serving something
        // else would leave `/locale/xx` answering 200 while setting nothing, so
        // a code the app does not offer is refused outright. The route constraint
        // is only a shape check -- it cannot enumerate the languages, because a
        // language added in the panel has to work without a deploy.
        $requested = $request->route('locale');

        if (is_string($requested) && ! in_array($requested, $supported, true)) {
            abort(404);
        }

        $routeLocale = $this->valid($requested, $supported);
        $sessionLocale = $request->hasSession()
            ? $this->valid($request->session()->get('locale'), $supported)
            : null;
        $cookieLocale = $this->valid($request->cookie('ds_locale'), $supported);
        $browserLocale = $this->preferredLocale($request, $supported);

        // `Locales::default()` already resolves the database against the
        // configured fallback, so it only has to be checked against the supported
        // list like any other candidate.
        $defaultLocale = $this->valid(Locales::default(), $supported)
            ?? ($supported[0] ?? 'en');

        $locale = $routeLocale
            ?? $sessionLocale
            ?? $cookieLocale
            ?? $browserLocale
            ?? $defaultLocale;

        app()->setLocale($locale);

        if ($request->hasSession()) {
            $request->session()->put('locale', $locale);
        }

        Cookie::queue(
            'ds_locale',
            $locale,
            60 * 24 * 365,
            '/',
            null,
            $request->isSecure(),
            true,
            false,
            'lax',
        );

        View::share([
            'currentLocale' => $locale,
            'textDirection' => Locales::direction($locale),
            'supportedLocales' => $supported,
            'localeNames' => Locales::names(),
        ]);

        $response = $next($request);

        $response->headers->set('Content-Language', $locale);
        $response->setVary('Accept-Language', false);

        return $response;
    }

    private function preferredLocale(Request $request, array $supported): ?string
    {
        foreach ($request->getLanguages() as $language) {
            $language = strtolower(str_replace('-', '_', $language));

            if ($language === '*') {
                continue;
            }

            foreach ($supported as $locale) {
                $locale = strtolower(str_replace('-', '_', $locale));

                if ($language === $locale || str_starts_with($language, $locale.'_') || str_starts_with($locale, $language.'_')) {
                    return $locale;
                }
            }
        }

        return null;
    }

    private function valid(mixed $locale, array $supported): ?string
    {
        return is_string($locale) && in_array($locale, $supported, true) ? $locale : null;
    }
}
