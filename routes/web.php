<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\LocaleController;
use App\Http\Controllers\RegisterController;
use App\Http\Controllers\SessionController;
use App\Http\Controllers\WelcomeController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web routes
|--------------------------------------------------------------------------
|
| No URL carries a locale. A page is reachable at exactly one address
| (`/login`, never `/ar/login` or `/login?locale=ar`) and the language follows
| the visitor through the `ds_locale` cookie and the session. That keeps one
| canonical URL per page, which is what search engines and shared links want.
|
| The single exception is `locale.set` below. Changing language now has to be a
| request, because only the server can write the cookie -- so that one route
| takes the locale as a path parameter and immediately redirects onward.
|
*/

/*
 * Sign-in is rate limited because it is the one endpoint here that takes an
 * attacker-supplied secret. Ten attempts a minute is generous for a human and
 * still useless for credential stuffing.
 */
$loginThrottle = 'throttle:10,1';

Route::middleware('locale')->group(function () use ($loginThrottle): void {
    Route::get('/', [WelcomeController::class, 'index'])->name('home');
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::get('/login', [SessionController::class, 'create'])->name('login');
    Route::post('/login', [SessionController::class, 'store'])->middleware($loginThrottle)->name('login.store');

    Route::get('/register', [RegisterController::class, 'create'])->name('register');
    Route::post('/register', [RegisterController::class, 'store'])->name('register.store');

    Route::post('/logout', [SessionController::class, 'destroy'])->name('logout');

    // The `locale` path parameter is resolved by the SetLocale middleware, which
    // writes the session and the cookie before this action runs.
    Route::get('/locale/{locale}', LocaleController::class)
        ->whereIn('locale', config('localization.locales', ['en', 'ar', 'fr']))
        ->name('locale.set');
});
