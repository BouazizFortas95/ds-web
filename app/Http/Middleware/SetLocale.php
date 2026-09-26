<?php

namespace App\Http\Middleware;

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
        $supported = config('localization.locales', ['en', 'ar', 'fr']);
        $routeLocale = $this->valid($request->route('locale'), $supported);
        $sessionLocale = $request->hasSession()
            ? $this->valid($request->session()->get('locale'), $supported)
            : null;
        $cookieLocale = $this->valid($request->cookie('ds_locale'), $supported);
        $browserLocale = $this->preferredLocale($request, $supported);
        $defaultLocale = $this->valid(config('localization.default'), $supported)
            ?? $this->valid(config('app.locale'), $supported)
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
            'textDirection' => config("localization.directions.{$locale}", 'ltr'),
            'supportedLocales' => $supported,
            'localeNames' => config('localization.names', []),
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
