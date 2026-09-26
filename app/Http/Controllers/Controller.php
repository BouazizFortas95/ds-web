<?php

namespace App\Http\Controllers;

use Filament\Facades\Filament;

abstract class Controller
{
    /**
     * Where a successful sign-in or sign-up should land.
     *
     * The dashboard lives inside the Filament panel, so this is the panel home.
     * No locale is appended: the language travels in the `ds_locale` cookie, and
     * the panel reads it through the same middleware as everything else.
     */
    protected function dashboardUrl(): string
    {
        return Filament::getUrl();
    }
}
