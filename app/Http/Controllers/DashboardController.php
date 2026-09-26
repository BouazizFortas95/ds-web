<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;

class DashboardController extends Controller
{
    /**
     * The dashboard lives in the Filament panel as a Livewire page, so the public
     * `/dashboard` entry point is kept purely as a redirect into the panel home.
     *
     * Guests are not bounced to the sign-in form here: Filament's own
     * authentication middleware handles that, which keeps the decision in one
     * place and avoids this shim having to duplicate the guard logic.
     */
    public function index(): RedirectResponse
    {
        return redirect()->to($this->dashboardUrl());
    }
}
