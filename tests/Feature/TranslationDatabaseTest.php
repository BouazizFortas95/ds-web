<?php

namespace Tests\Feature;

use App\Models\Language;
use App\Models\User;
use App\Support\Locales;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Facades\Schema;
use Livewire\Livewire;
use Tests\TestCase;
use TomatoPHP\FilamentTranslations\Filament\Resources\Translations\Pages\ManageTranslations;
use TomatoPHP\FilamentTranslations\Models\Translation;

/**
 * The lang/ files remain the source of truth in git, but the panel edits strings
 * in the database and the loader lets a row win over the file. These tests pin
 * that arrangement, because both halves of it are easy to break silently: drop
 * the loader and every edit quietly stops taking effect, and let the seeder
 * overwrite instead of fill and a translator's work is reverted the next time
 * anyone seeds.
 */
class TranslationDatabaseTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Language files written by a test, removed again whatever the outcome.
     *
     * @var array<int, string>
     */
    private array $temporaryLanguageFiles = [];

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();

        Filament::setCurrentPanel('auth');
    }

    protected function tearDown(): void
    {
        foreach ($this->temporaryLanguageFiles as $file) {
            if (is_file($file)) {
                unlink($file);
            }

            @rmdir(dirname($file));
        }

        $this->temporaryLanguageFiles = [];

        parent::tearDown();
    }

    private function seedTranslations(): void
    {
        Artisan::call('translations:seed-from-lang');
    }

    public function test_the_migration_creates_the_table_the_loader_reads(): void
    {
        $this->assertTrue(Schema::hasTable('language_lines'));

        $this->assertTrue(Schema::hasColumns('language_lines', [
            'group', 'key', 'text', 'namespace', 'deleted_at',
        ]));
    }

    public function test_the_loader_is_the_database_backed_one(): void
    {
        // The manager replaces the framework's file loader, which is what lets a
        // row take precedence over the lang/ files.
        $this->assertInstanceOf(
            \Spatie\TranslationLoader\TranslationLoaderManager::class,
            app('translation.loader'),
        );

        $this->assertSame(
            Translation::class,
            config('translation-loader.model'),
        );
    }

    public function test_it_seeds_a_row_for_every_key_in_the_lang_files(): void
    {
        $this->seedTranslations();

        $expected = [];

        foreach (config('localization.locales') as $locale) {
            foreach (glob(lang_path($locale) . '/*.php') as $file) {
                $expected[basename($file, '.php')] = true;
            }
        }

        $this->assertNotEmpty($expected);

        foreach (array_keys($expected) as $group) {
            $fileKeys = $this->leafKeys(require lang_path("en/{$group}.php"));

            $this->assertNotEmpty($fileKeys, "the {$group} group has no keys to check");

            $dbKeys = Translation::query()
                ->where('group', $group)
                ->pluck('key')
                ->all();

            foreach ($fileKeys as $key) {
                $this->assertContains($key, $dbKeys, "{$group}.{$key} is missing from the database");
            }
        }
    }

    public function test_seeding_is_idempotent(): void
    {
        $this->seedTranslations();
        $count = Translation::query()->count();

        $this->seedTranslations();

        $this->assertSame($count, Translation::query()->count());
    }

    public function test_seeding_stores_the_value_from_the_lang_files(): void
    {
        $this->seedTranslations();

        $row = Translation::query()
            ->where('group', 'site')
            ->where('key', 'common.brand_name')
            ->firstOrFail();

        foreach (config('localization.locales') as $locale) {
            $this->assertSame(
                Lang::get('site.common.brand_name', [], $locale),
                $row->text[$locale] ?? null,
            );
        }
    }

    public function test_a_row_overrides_the_lang_file(): void
    {
        $this->seedTranslations();

        $fromFile = Lang::get('site.common.brand_name', [], 'en');

        $row = Translation::query()
            ->where('group', 'site')
            ->where('key', 'common.brand_name')
            ->firstOrFail();

        $row->text = array_merge($row->text, ['en' => 'Overridden In The Database']);
        $row->save();

        $this->forgetLoadedTranslations();

        $this->assertSame('Overridden In The Database', __('site.common.brand_name', [], 'en'));
        $this->assertNotSame($fromFile, 'Overridden In The Database');
    }

    public function test_the_other_languages_survive_a_single_language_edit(): void
    {
        $this->seedTranslations();

        $row = Translation::query()
            ->where('group', 'site')
            ->where('key', 'common.brand_name')
            ->firstOrFail();

        $arabic = $row->text['ar'] ?? null;

        $row->text = array_merge($row->text, ['en' => 'English Override']);
        $row->save();

        $this->forgetLoadedTranslations();

        $this->assertSame('English Override', __('site.common.brand_name', [], 'en'));
        $this->assertSame($arabic, __('site.common.brand_name', [], 'ar'));
    }

    public function test_deleting_a_row_falls_back_to_the_lang_file(): void
    {
        $this->seedTranslations();

        $fromFile = Lang::get('site.common.brand_name', [], 'en');

        $row = Translation::query()
            ->where('group', 'site')
            ->where('key', 'common.brand_name')
            ->firstOrFail();

        $row->delete();

        $this->forgetLoadedTranslations();

        $this->assertSame($fromFile, __('site.common.brand_name', [], 'en'));
    }

    public function test_an_edit_is_visible_without_clearing_the_cache_by_hand(): void
    {
        $this->seedTranslations();

        // Read once so the loader has cached the group, which is what a warm
        // cache store in a running app looks like.
        $this->forgetLoadedTranslations();
        __('site.common.brand_name', [], 'en');

        $this->assertTrue(
            Cache::has(Translation::getCacheKey('site', 'en')),
            'the group was not cached, so this test cannot detect a stale read',
        );

        $row = Translation::query()
            ->where('group', 'site')
            ->where('key', 'common.brand_name')
            ->firstOrFail();

        $row->text = array_merge($row->text, ['en' => 'Freshly Edited']);
        $row->save();

        // Only the translator's in-process record of loaded groups is reset,
        // which a brand new request would not have. The shared cache is left
        // alone, so a passing test still proves the model event did the work.
        $this->forgetLoadedTranslations();

        $this->assertSame('Freshly Edited', __('site.common.brand_name', [], 'en'));
    }

    public function test_seeding_never_overwrites_a_value_that_was_edited_in_the_panel(): void
    {
        $this->seedTranslations();

        $row = Translation::query()
            ->where('group', 'site')
            ->where('key', 'common.brand_name')
            ->firstOrFail();

        $row->text = array_merge($row->text, ['en' => 'A Translator Said This']);
        $row->save();

        Artisan::call('translations:seed-from-lang');

        $this->assertSame(
            'A Translator Said This',
            Translation::query()
                ->where('group', 'site')
                ->where('key', 'common.brand_name')
                ->firstOrFail()
                ->text['en'] ?? null,
        );
    }

    public function test_seeding_fills_in_a_language_that_is_missing_from_a_row(): void
    {
        $this->seedTranslations();

        $row = Translation::query()
            ->where('group', 'site')
            ->where('key', 'common.brand_name')
            ->firstOrFail();

        $french = $row->text['fr'] ?? null;

        // The cast hands back a copy, so removing a language means writing the
        // whole map back rather than unsetting an offset in place.
        $withoutFrench = $row->text;
        unset($withoutFrench['fr']);

        $row->text = $withoutFrench;
        $row->save();

        $this->assertArrayNotHasKey('fr', $row->fresh()->text);

        Artisan::call('translations:seed-from-lang');

        $this->assertSame(
            $french,
            Translation::query()
                ->where('group', 'site')
                ->where('key', 'common.brand_name')
                ->firstOrFail()
                ->text['fr'] ?? null,
        );
    }

    /**
     * A language added later has to be read out of the file, not out of the
     * database, and the two are not the same thing.
     *
     * Once the other languages are seeded, every row exists without a cell for
     * the new one. The package's model answers a locale it has no cell for with
     * an empty string rather than null, and the loader merges that over the
     * files, so `Lang::get()` on the new language returns '' for every key. A
     * seeder that asked the translator for the value would store that blank and
     * the translation would never appear.
     */
    public function test_a_new_language_is_seeded_from_the_file_and_not_from_the_loader(): void
    {
        // The rows have to exist first, which is the real sequence: the app is
        // already translated when someone adds a language to it.
        $this->seedTranslations();

        $this->writeLanguageFile('xx', [
            'dashboard' => [
                'threat_detection' => 'XX TRANSLATION',
            ],
        ]);

        $this->addLanguage('xx');

        $this->seedTranslations();

        $row = Translation::query()
            ->where('group', 'site')
            ->where('key', 'dashboard.threat_detection')
            ->firstOrFail();

        $this->assertSame('XX TRANSLATION', $row->text['xx'] ?? null);

        $this->forgetLoadedTranslations();

        $this->assertSame('XX TRANSLATION', __('site.dashboard.threat_detection', [], 'xx'));
    }

    /**
     * A key the new language has not translated yet comes out blank rather than
     * English.
     *
     * This is the package's behaviour, not a choice made here: the model returns
     * '' for a missing locale and the loader treats '' as a real value, so it
     * replaces the file. The test exists so the limitation is a known quantity --
     * if a future version of the package starts falling back, this fails and
     * tells us the better behaviour has landed.
     */
    public function test_a_key_missing_from_a_new_language_renders_blank_rather_than_english(): void
    {
        $this->seedTranslations();

        $this->writeLanguageFile('xx', [
            'dashboard' => [
                'threat_detection' => 'XX TRANSLATION',
            ],
        ]);

        $this->addLanguage('xx');

        $this->seedTranslations();
        $this->forgetLoadedTranslations();

        $this->assertSame(
            '',
            __('site.common.brand_name', [], 'xx'),
            'the loader is expected to hand back an empty string here',
        );

        $this->assertNotSame(
            '',
            __('site.common.brand_name', [], 'en'),
            'the existing languages must be unaffected',
        );
    }

    public function test_the_panel_lists_the_seeded_keys(): void
    {        $this->seedTranslations();

        $row = Translation::query()
            ->where('group', 'site')
            ->where('key', 'common.brand_name')
            ->firstOrFail();

        // The resource mounts its table lazily, so the rows are not in the first
        // render. A browser gets them from the same call the asset makes.
        Livewire::actingAs(User::factory()->create())
            ->test(ManageTranslations::class)
            ->assertSuccessful()
            ->call('loadTable')
            ->assertCanSeeTableRecords([$row]);
    }

    public function test_the_panel_page_is_reachable_for_a_signed_in_operator(): void
    {
        $this->seedTranslations();

        $this->actingAs(User::factory()->create())
            ->get(route('filament.auth.resources.translations.index'))
            ->assertOk();
    }

    public function test_a_guest_cannot_reach_the_translations_screen(): void
    {
        $this->get(route('filament.auth.resources.translations.index'))
            ->assertRedirect();
    }

    /**
     * The translator records which groups it has already loaded for the life of
     * the process, so a single test that reads, edits and reads again would keep
     * serving the first result. Each request in a real app starts clean, so clear
     * it between reads without touching the shared cache.
     */
    private function forgetLoadedTranslations(): void
    {
        app('translator')->setLoaded([]);
    }

    /**
     * Register an extra language the way the panel's Languages screen does: a row
     * in the `languages` table, appended after the ones already there.
     */
    private function addLanguage(string $code, string $name = 'XX', string $direction = 'ltr'): Language
    {
        $language = Language::query()->create([
            'code' => $code,
            'name' => $name,
            'direction' => $direction,
            'is_active' => true,
            'position' => (int) Language::query()->max('position') + 1,
        ]);

        Locales::flush();
        $this->forgetLoadedTranslations();

        return $language;
    }

    /**
     * @param  array<string, mixed>  $group
     */
    private function writeLanguageFile(string $locale, array $group): void
    {
        $directory = lang_path($locale);

        if (! is_dir($directory)) {
            mkdir($directory, 0777, true);
        }

        $body = "<?php\n\nreturn " . var_export($group, true) . ";\n";

        file_put_contents($directory . '/site.php', $body);

        $this->temporaryLanguageFiles[] = $directory . '/site.php';

        // The translator and the loader both memoise, so anything already read
        // for this locale has to be dropped before the file can be seen.
        $this->forgetLoadedTranslations();

        foreach (['site', 'validation'] as $group_) {
            Cache::forget(Translation::getCacheKey($group_, $locale));
        }
    }

    /**
     * @param  array<string, mixed>  $array
     * @return array<int, string>
     */
    private function leafKeys(array $array, string $prefix = ''): array
    {
        $keys = [];

        foreach ($array as $key => $value) {
            $composite = $prefix === '' ? (string) $key : $prefix . '.' . $key;

            if (is_array($value)) {
                $keys = array_merge($keys, $this->leafKeys($value, $composite));

                continue;
            }

            $keys[] = $composite;
        }

        return $keys;
    }
}
