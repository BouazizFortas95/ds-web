<?php

namespace App\Support;

use App\Models\Language;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
use Throwable;

/**
 * The one place that answers "which languages does this app offer".
 *
 * The list lives in the `languages` table so it can be changed from the panel,
 * and is read here rather than from `config/localization.php` directly. The
 * config is still the seed and the fallback: while the table is empty or does
 * not exist yet -- a fresh install part-way through migrating, or a console
 * command running before the migration -- the configured languages are used, so
 * this class never turns a working app into one with no switcher.
 *
 * Results are cached, because the locale middleware, both switchers and the
 * seeder all ask on every request. `flush()` is called from the model, so a
 * change made in the panel is visible on the next request.
 */
final class Locales
{
    private const CACHE_KEY = 'ds.locales.v1';

    /**
     * Per-request memo for the translation rows, which the languages table reads
     * once per language. Not cached beyond the request, because a translation
     * edited in the panel has to show up immediately.
     *
     * @var Collection<int, array<string, string>>|null
     */
    private static ?Collection $translationRows = null;

    /**
     * Every active language, in switcher order.
     *
     * @return Collection<int, array{code: string, name: string, direction: string, is_default: bool}>
     */
    public static function all(): Collection
    {
        $cached = Cache::get(self::CACHE_KEY);

        if (is_array($cached)) {
            return new Collection($cached);
        }

        $resolved = self::resolve();

        Cache::forever(self::CACHE_KEY, $resolved);

        return new Collection($resolved);
    }

    /**
     * @return array<int, string>
     */
    public static function codes(): array
    {
        return self::all()->pluck('code')->all();
    }

    /**
     * The language's own name, keyed by code, for the switcher labels.
     *
     * @return array<string, string>
     */
    public static function names(): array
    {
        return self::all()->pluck('name', 'code')->all();
    }

    /**
     * @return array<string, string>
     */
    public static function directions(): array
    {
        return self::all()->pluck('direction', 'code')->all();
    }

    public static function direction(string $code): string
    {
        return self::directions()[$code] ?? 'ltr';
    }

    /**
     * The language to use when nothing else applies.
     *
     * `localization.default` (and the `APP_LOCALE` behind it) stays the
     * authority, because that is a deployment decision and an env var cannot be
     * changed from a browser. The database is only consulted when the configured
     * value is not one of the active languages -- a default that was deactivated
     * or deleted in the panel, which would otherwise leave the app falling back
     * to a language nobody can select any more.
     */
    public static function default(): string
    {
        $configured = (string) config('localization.default', '');

        if ($configured !== '' && self::isSupported($configured)) {
            return $configured;
        }

        $flagged = self::all()->firstWhere('is_default', true)['code'] ?? null;

        if ($flagged !== null) {
            return $flagged;
        }

        return self::codes()[0] ?? 'en';
    }

    public static function isSupported(string $code): bool
    {
        return in_array($code, self::codes(), true);
    }

    /**
     * The language map the translations editor builds its columns from.
     *
     * The configured map is kept as the starting point so a language that already
     * has an entry keeps the flag it was given, and only the labels come from
     * the database. A language added through the panel has no configured entry,
     * so its flag falls back to its own code uppercased -- cosmetic only, the
     * package reads the label and ignores the flag.
     *
     * @return array<string, array{label: string, flag: string}>
     */
    public static function forEditor(): array
    {
        $map = (array) config('filament-translations.locals', []);

        foreach (self::all() as $language) {
            $map[$language['code']] = [
                'label' => $language['name'],
                'flag' => $map[$language['code']]['flag'] ?? strtoupper(strtok($language['code'], '_')),
            ];
        }

        return $map;
    }

    /**
     * Give the translations editor a column for every language the app offers.
     *
     * The editor reads `filament-translations.locals` for its per-language
     * fields, and the package's own provider copies that into
     * `filament-translation-component.languages` while registering. Both are
     * plain config reads, so overwriting them here -- after every provider has
     * registered, before anything renders -- is enough for a language added from
     * the Languages screen to get a column without editing a config file.
     *
     * Without this the switcher would offer a language that no screen anywhere
     * would let anyone fill in, which is the worst outcome available: the app
     * offers something it cannot deliver.
     */
    public static function publishToEditor(): void
    {
        $languages = self::forEditor();

        if ($languages === []) {
            return;
        }

        config()->set('filament-translations.locals', $languages);
        config()->set('filament-translation-component.languages', $languages);
    }

    /**
     * The `text` column of every translation row, for counting how much of a
     * language has been filled in.
     *
     * Held for the rest of the request, because the languages table asks for
     * this once per row and re-reading every translation each time would make
     * the count quadratic in the number of languages.
     *
     * @return Collection<int, array<string, string>>
     */
    public static function translationRows(): Collection
    {
        if (self::$translationRows !== null) {
            return self::$translationRows;
        }

        $model = config('translation-loader.model');

        if (! is_string($model) || ! class_exists($model)) {
            return self::$translationRows = new Collection;
        }

        return self::$translationRows = $model::query()->pluck('text');
    }

    /**
     * How many distinct keys the catalogue holds, across every group.
     */
    public static function translatableKeyCount(): int
    {
        return self::translationRows()->count();
    }

    /**
     * Forget the cached list. Called whenever a language row changes.
     */
    public static function flush(): void
    {
        Cache::forget(self::CACHE_KEY);
    }

    /**
     * @return array<int, array{code: string, name: string, direction: string, is_default: bool}>
     */
    private static function resolve(): array
    {
        $fromDatabase = self::fromDatabase();

        return $fromDatabase ?? self::fromConfig();
    }

    /**
     * Null when the table cannot be read, so the caller can fall back.
     *
     * @return array<int, array{code: string, name: string, direction: string, is_default: bool}>|null
     */
    private static function fromDatabase(): ?array
    {
        try {
            if (! Schema::hasTable('languages')) {
                return null;
            }

            $rows = Language::query()
                ->where('is_active', true)
                ->orderBy('position')
                ->orderBy('code')
                ->get(['code', 'name', 'direction', 'is_default']);

            if ($rows->isEmpty()) {
                return null;
            }

            return $rows->map(fn (Language $language) => [
                'code' => (string) $language->code,
                'name' => (string) $language->name,
                'direction' => $language->direction === 'rtl' ? 'rtl' : 'ltr',
                'is_default' => (bool) $language->is_default,
            ])->all();
        } catch (Throwable) {
            // A console command running before the migration, or a test that has
            // not migrated yet. The configured languages are a better answer than
            // an exception.
            return null;
        }
    }

    /**
     * @return array<int, array{code: string, name: string, direction: string, is_default: bool}>
     */
    private static function fromConfig(): array
    {
        $locales = array_values((array) config('localization.locales', []));
        $names = (array) config('localization.names', []);
        $directions = (array) config('localization.directions', []);
        $default = (string) config('localization.default', 'en');

        $languages = [];

        foreach ($locales as $code) {
            $code = (string) $code;

            $languages[] = [
                'code' => $code,
                'name' => (string) ($names[$code] ?? strtoupper($code)),
                'direction' => ($directions[$code] ?? 'ltr') === 'rtl' ? 'rtl' : 'ltr',
                'is_default' => $code === $default,
            ];
        }

        return $languages;
    }
}
