<?php

namespace App\Providers\Filament;

use App\Filament\Pages\DesertSentry;
use App\Http\Middleware\SetLocale;
use Filament\Enums\ThemeMode;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\View\PanelsRenderHook;
use Filament\Widgets\AccountWidget;
use Filament\Widgets\FilamentInfoWidget;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use TomatoPHP\FilamentTranslations\FilamentTranslationsPlugin;

class AuthPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('auth')
            ->path('auth')
            ->viteTheme('resources/css/filament/auth/theme.css')
            ->brandName(__('site.common.brand_name'))
            ->login()
            ->colors([
                'primary' => Color::Cyan,
            ])
            // The console is dark by design; a light variant would only dilute
            // the Desert Sentry palette the whole site uses.
            ->darkMode(true)
            ->defaultThemeMode(ThemeMode::Dark)
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\Filament\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\Filament\Pages')
            ->pages([
                DesertSentry::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\Filament\Widgets')
            ->widgets([
                AccountWidget::class,
                FilamentInfoWidget::class,
            ])
            // Adds the Translations resource, where any `lang/` key can be
            // overridden from the database and edited in place. A row wins over
            // the file; a key with no row still resolves from `lang/`.
            ->plugin(
                FilamentTranslationsPlugin::make()
                    // "Create" would invent keys no call site ever reads, and
                    // "Clear" would drop every override and hand the whole app
                    // back to the files. Both are one mis-click from losing work
                    // that only exists in the database, so neither is offered.
                    ->allowCreate(false)
                    ->allowClearTranslations(false),
            )
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                SetLocale::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->persistentMiddleware([
                SetLocale::class,
            ])
            ->renderHook(PanelsRenderHook::TOPBAR_END, fn (): string => view('filament.locale-switcher')->render())
            ->renderHook(PanelsRenderHook::SIMPLE_LAYOUT_START, fn (): string => view('filament.locale-switcher')->render())
            ->authMiddleware([
                Authenticate::class,
            ]);
    }
}
