<?php

namespace App\Providers;

use App\Support\Locales;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\ServiceProvider;
use TomatoPHP\FilamentTranslations\Models\Translation;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->invalidateTranslationCacheOnWrite();
        $this->publishDatabaseLanguagesToTheEditor();
    }

    /**
     * Let the translations editor show a column for every language in the table.
     *
     * Runs here, in `boot`, because that is the only point at which every
     * provider has registered but nothing has rendered yet -- the config key the
     * editor reads has to be correct before the first field is built.
     */
    private function publishDatabaseLanguagesToTheEditor(): void
    {
        Locales::publishToEditor();
    }

    /**
     * Drop the loader's cache whenever a translation row changes.
     *
     * `Spatie\TranslationLoader\LanguageLine::getTranslationsForGroup()` reads
     * every group through `Cache::rememberForever()`, keyed per group and
     * locale. Nothing in the package invalidates those keys, so without this an
     * edit saved in the panel would keep serving the old string and the screen
     * would look like it had silently thrown the change away.
     *
     * Only the affected group and locale are forgotten, so editing one French
     * string does not throw away the whole catalogue.
     */
    private function invalidateTranslationCacheOnWrite(): void
    {
        $forget = static function (Translation $translation): void {
            $locales = array_keys($translation->text ?? []);

            if ($locales === []) {
                // A row with no text cannot narrow it down, and a language can
                // also be removed from a row, so the full set is the safe floor.
                $locales = Locales::codes();
            }

            foreach ($locales as $locale) {
                Cache::forget(Translation::getCacheKey($translation->group, $locale));
            }
        };

        Translation::saved($forget);
        Translation::deleted($forget);
        Translation::restored($forget);
    }
}
