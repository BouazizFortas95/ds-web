<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Applies a language choice, then puts the visitor back where they were.
 *
 * Page URLs no longer carry a locale, so this endpoint is the only way to change
 * language: a `<select>` or an anchor cannot write a cookie on its own. The
 * `SetLocale` middleware has already validated the `{locale}` segment and
 * persisted it to the session and the `ds_locale` cookie by the time this runs,
 * so the only work left is choosing where to send the visitor next.
 */
class LocaleController extends Controller
{
    public function __invoke(Request $request): RedirectResponse
    {
        return redirect()->to($this->returnUrl($request));
    }

    /**
     * Where to send the visitor after switching language.
     *
     * The referer is the page the switcher was clicked on, so honouring it keeps
     * the visitor in place -- inside the Filament panel, on the sign-in form,
     * wherever they were. It is attacker-controlled, so it is only used when it
     * resolves to this exact origin; anything else falls back to the landing
     * page rather than becoming an open redirect.
     */
    private function returnUrl(Request $request): string
    {
        $referer = $request->headers->get('referer');

        return $this->isSameOrigin($request, $referer) ? $referer : route('home');
    }

    private function isSameOrigin(Request $request, ?string $url): bool
    {
        if (blank($url)) {
            return false;
        }

        $candidate = parse_url($url);

        if (! is_array($candidate) || ! isset($candidate['host'])) {
            return false;
        }

        return $this->normalise($candidate) === $this->normalise($request);
    }

    /**
     * Reduce a request or a parsed URL to the tuple that defines its origin.
     *
     * The port is normalised against the scheme's default so that an explicit
     * `:443` on an https URL still counts as the same origin as one without it.
     *
     * @param  Request|array<string, int|string>  $target
     */
    private function normalise(Request|array $target): string
    {
        $parts = $target instanceof Request
            ? [
                'scheme' => $target->getScheme(),
                'host' => $target->getHost(),
                'port' => $target->getPort(),
            ]
            : $target;

        $scheme = strtolower((string) ($parts['scheme'] ?? ''));
        $port = (int) ($parts['port'] ?? ($scheme === 'https' ? 443 : 80));

        return strtolower((string) ($parts['host'] ?? '')) . '|' . $scheme . '|' . $port;
    }
}
