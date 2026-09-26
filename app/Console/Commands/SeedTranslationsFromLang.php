<?php

namespace App\Console\Commands;

use App\Support\Locales;
use Illuminate\Console\Command;
use TomatoPHP\FilamentTranslations\Models\Translation;

/**
 * Copies the `lang/` files into the `language_lines` table so every string the
 * app can display is editable in the Translations screen of the panel.
 *
 * The package's own `filament-translations:import` is not enough on its own. It
 * works by scanning source files for `__()` and `trans()` call sites, which
 * misses two kinds of key this app has:
 *
 *   1. Keys held as data and translated later. The `'site.dashboard.log_*'`
 *      entries in DesertSentry::systemLog(), for instance, are plain strings in
 *      an array that get translated when rendered, so they never look like a
 *      call site to the scanner.
 *   2. Framework-resolved keys, such as the `validation.*` messages Laravel
 *      itself looks up after `$request->validate()`.
 *
 * Re-running is safe and non-destructive: an existing non-empty database value
 * wins and is never touched, which is what keeps an edit made in the panel from
 * being reverted by this command. Only missing languages are filled in from the
 * files, and a locale whose file lacks the key is left absent rather than
 * overwritten with an empty string.
 */
class SeedTranslationsFromLang extends Command
{
    protected $signature = 'translations:seed-from-lang
                            {--dry-run : Report what would change without writing}';

    protected $description = 'Seed the translations table from the lang/ files without overwriting existing overrides';

    public function handle(): int
    {
        $locales = $this->locales();

        if ($locales === []) {
            $this->components->error('No languages are configured and the languages table is empty');

            return self::FAILURE;
        }

        $dryRun = (bool) $this->option('dry-run');

        $created = 0;
        $filled = 0;
        $untouched = 0;

        foreach ($this->valuesInLangFiles($locales) as $group => $keys) {
            foreach ($keys as $key => $values) {
                $existing = Translation::withTrashed()
                    ->where('namespace', '*')
                    ->where('group', $group)
                    ->where('key', $key)
                    ->first();

                if ($existing === null) {
                    $created++;

                    if ($dryRun) {
                        continue;
                    }

                    Translation::create([
                        'namespace' => '*',
                        'group' => $group,
                        'key' => $key,
                        'text' => $values,
                    ]);

                    continue;
                }

                // A blank stored value counts as missing. A row can be left
                // holding one by an older version of this command, and treating
                // it as present would keep the blank forever.
                $missing = array_diff_key($values, array_filter(
                    $existing->text ?? [],
                    fn ($value) => trim((string) $value) !== '',
                ));

                if ($missing === []) {
                    $untouched++;

                    continue;
                }

                $filled += count($missing);

                if ($dryRun) {
                    continue;
                }

                if ($existing->trashed()) {
                    $existing->restore();
                }

                $existing->text = array_merge($existing->text ?? [], $missing);
                $existing->save();
            }
        }

        $this->components->info(sprintf(
            '%s %d new key(s), filled %d missing language(s), left %d override(s) alone',
            $dryRun ? 'Would create' : 'Created',
            $created,
            $filled,
            $untouched,
        ));

        return self::SUCCESS;
    }

    /**
     * The locales this app supports.
     *
     * Read through the `Locales` resolver, which answers from the `languages`
     * table so a language added from the panel is picked up here without a
     * deploy, and falls back to `config/localization.php` while that table is
     * empty. `filament-translations.locals` mirrors the same list, but it is a UI
     * label map owned by the package, and reading it here would tie a data
     * operation to a presentation concern.
     *
     * @return array<int, string>
     */
    private function locales(): array
    {
        return Locales::codes();
    }

    /**
     * Every value the language files actually define, as
     * `[$group][$key][$locale] => string`.
     *
     * The files are read with a plain `require` rather than through `Lang::get()`.
     * That matters when a language is added: the loader merges the database over
     * the files, and the package's model answers a locale it has no cell for with
     * an empty string instead of null, so `Lang::get()` on a freshly added
     * language returns '' for every key rather than the file's text. Reading the
     * files directly is the only way this command can be sure it is copying the
     * files and not a half-empty database.
     *
     * A locale with no entry for a key is left out of the map entirely, so the
     * row keeps whatever it already had and stays absent rather than being
     * pinned to a blank. An entry that is present but empty is treated the same
     * way, because a placeholder left in a new file should not become a value
     * the database then treats as authoritative.
     *
     * @param  array<int, string>  $locales
     * @return array<string, array<string, array<string, string>>>
     */
    private function valuesInLangFiles(array $locales): array
    {
        $groups = [];

        foreach ($locales as $locale) {
            foreach (glob(lang_path($locale) . '/*.php') ?: [] as $file) {
                $group = basename($file, '.php');
                $values = require $file;

                if (! is_array($values)) {
                    continue;
                }

                foreach ($this->flatten($values) as $key => $value) {
                    if (! is_scalar($value) || trim((string) $value) === '') {
                        continue;
                    }

                    $groups[$group][$key][$locale] = (string) $value;
                }
            }
        }

        ksort($groups);

        return $groups;
    }

    /**
     * Reduce a nested message array to `dot.key => value`, matching how Laravel
     * addresses nested groups with `__('validation.max.string')`.
     *
     * @param  array<string, mixed>  $array
     * @return array<string, mixed>
     */
    private function flatten(array $array, string $prefix = ''): array
    {
        $flat = [];

        foreach ($array as $key => $value) {
            $composite = $prefix === '' ? (string) $key : $prefix . '.' . $key;

            if (is_array($value)) {
                $flat += $this->flatten($value, $composite);

                continue;
            }

            $flat[$composite] = $value;
        }

        return $flat;
    }
}
