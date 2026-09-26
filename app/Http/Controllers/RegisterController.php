<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class RegisterController extends Controller
{
    /**
     * Show the branded sign-up form.
     */
    public function create(): View
    {
        return view('auth.register');
    }

    /**
     * Create an account and sign the new operator straight in.
     *
     * Filament ships no registration, so this is the only way into the panel
     * for a new account. The user is logged in on the same guard, which means
     * they land on the dashboard rather than being bounced back to a login form
     * for an account they just made.
     */
    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ], [], $this->attributeNames());

        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => $data['password'],
        ]);

        event(new Registered($user));

        auth()->login($user);

        $request->session()->regenerate();
        $request->session()->forget('url.intended');

        return redirect()
            ->to($this->dashboardUrl())
            ->with('status', __('site.auth.account_created'));
    }

    /**
     * Validation field labels, so errors read in the active language.
     *
     * @return array<string, string>
     */
    private function attributeNames(): array
    {
        return [
            'name' => __('site.auth.full_name'),
            'email' => __('site.auth.email'),
            'password' => __('site.auth.password'),
            'password_confirmation' => __('site.auth.confirm_password'),
        ];
    }
}
