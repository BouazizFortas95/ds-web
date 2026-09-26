<?php

namespace App\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class SessionController extends Controller
{
    /**
     * Show the branded sign-in form.
     */
    public function create(): View
    {
        return view('auth.login');
    }

    /**
     * Sign a visitor in against the `web` guard.
     *
     * This is the same guard Filament authenticates against, so a successful
     * sign-in here drops the visitor straight onto the panel dashboard. The
     * session id is regenerated on the way in to prevent session fixation, and
     * the password is never echoed back to the form.
     */
    public function store(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
        ], [], $this->attributeNames());

        if (! Auth::guard('web')->attempt($credentials, $request->boolean('remember'))) {
            // Deliberately vague: naming the offending field would confirm
            // whether an address is registered.
            throw ValidationException::withMessages([
                'email' => __('site.auth.invalid_credentials'),
            ]);
        }

        $request->session()->regenerate();

        // Drop any intended URL stashed by the auth middleware. The dashboard is
        // the only landing target, so a stale `url.intended` must not resurrect
        // a redirect that leaks internal paths back into the address bar.
        $request->session()->forget('url.intended');

        return redirect()
            ->to($this->dashboardUrl())
            ->with('status', __('site.auth.signed_in'));
    }

    /**
     * Destroy the current session.
     *
     * The `web` guard is the only guard configured, and it is the same guard
     * Filament authenticates against, so ending it here also signs the user out
     * of the Filament panel. The call is safe to repeat: logging out an already
     * anonymous visitor simply regenerates a fresh session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()
            ->route('home')
            ->with('status', __('site.auth.signed_out'));
    }

    /**
     * Validation field labels, so errors read in the active language.
     *
     * @return array<string, string>
     */
    private function attributeNames(): array
    {
        return [
            'email' => __('site.auth.email'),
            'password' => __('site.auth.password'),
            'remember' => __('site.auth.remember_me'),
        ];
    }
}
