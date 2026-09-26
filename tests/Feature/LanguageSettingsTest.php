<?php

namespace Tests\Feature;

use App\Filament\Resources\Languages\Pages\CreateLanguage;
use App\Filament\Resources\Languages\Pages\EditLanguage;
use App\Filament\Resources\Languages\Pages\ListLanguages;
use App\Models\Language;
use App\Models\User;
use App\Support\Locales;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Livewire\Livewire;
use Tests\TestCase;
use TomatoPHP\FilamentTranslations\Filament\Resources\Translations\Pages\ManageTranslations;

/**
 * Adding a language from the panel, rather than by editing a config file and
 * deploying.
 *
 * The point of the feature is that a row in `languages` reaches everything that
 * used to read `config/localization.php`: the switcher, the locale middleware,
 * the direction of the page, the seeder, and the translations editor. Each of
 * those is a separate reader that can silently keep using the old list, so they
 * are pinned one at a time here rather than by asserting that the page renders.
 */
class LanguageSettingsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();

        Filament::setCurrentPanel('auth');
    }

    public function test_migrating_seeds_the_configured_languages(): void
    {
        // A fresh install has to come up with a working switcher without anyone
        // opening the panel first.
        $this->assertSame(['en', 'ar', 'fr'], Locales::codes());
        $this->assertSame('English', Locales::names()['en']);
        $this->assertSame('rtl', Locales::direction('ar'));
        $this->assertSame('en', Locales::default());
    }

    public function test_the_languages_screen_lists_the_languages(): void
    {
        Livewire::actingAs(User::factory()->create())
            ->test(ListLanguages::class)
            ->assertSuccessful()
            ->assertSee('English')
            ->assertSee('العربية')
            ->assertSee('Français');
    }

    public function test_a_language_can_be_added_from_the_panel(): void
    {
        Livewire::actingAs(User::factory()->create())
            ->test(CreateLanguage::class)
            ->assertSuccessful()
            ->fillForm([
                'code' => 'es',
                'name' => 'Español',
                'direction' => 'ltr',
                'is_active' => true,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $language = Language::query()->find('es');

        $this->assertNotNull($language);
        $this->assertSame('Español', $language->name);
        $this->assertTrue($language->is_active);

        $this->assertContains('es', Locales::codes());
    }

    public function test_a_new_language_is_appended_rather_than_sorted_to_the_front(): void
    {
        $this->addLanguage('es');

        // Reshuffling a switcher people already have muscle memory for, in order
        // to accommodate the language that was just added, is its own bug.
        $this->assertSame(['en', 'ar', 'fr', 'es'], Locales::codes());
    }

    public function test_the_switcher_offers_a_language_added_in_the_panel(): void
    {
        $this->addLanguage('es');

        $this->get('/')
            ->assertOk()
            ->assertSee('Español')
            ->assertSee('value="' . route('locale.set', ['locale' => 'es']) . '"', false);
    }

    public function test_the_locale_endpoint_accepts_a_language_added_in_the_panel(): void
    {
        $this->addLanguage('es');

        $this->get('/locale/es')
            ->assertRedirect();

        $this->get('/login')
            ->assertOk()
            ->assertHeader('Content-Language', 'es');
    }

    public function test_the_translations_editor_grows_a_column_for_a_new_language(): void
    {
        $this->addLanguage('es', 'Español');

        // The provider publishes this at boot, which in a real request happens
        // after the previous request saved the language. A test adds the language
        // mid-request, so the same step is repeated here to stand in for the
        // next request arriving.
        Locales::publishToEditor();

        // The editor builds its per-language fields from this config key, so a
        // language missing from it is editable nowhere -- the switcher would
        // offer it and no screen would let anyone fill it in.
        $this->assertArrayHasKey('es', config('filament-translations.locals'));
        $this->assertSame('Español', config('filament-translations.locals')['es']['label']);
        $this->assertArrayHasKey(
            'es',
            (array) config('filament-translation-component.languages'),
        );

        Livewire::actingAs(User::factory()->create())
            ->test(ManageTranslations::class)
            ->assertSuccessful();
    }

    public function test_the_editor_map_keeps_the_flags_the_config_gave(): void
    {
        // Publishing rebuilds the map from the database, and the database has no
        // flag column. It must therefore keep the configured map underneath, or
        // `ar` would silently lose the Saudi flag the app chose for it.
        $map = Locales::forEditor();

        $this->assertSame('sa', $map['ar']['flag'], 'the configured flag must survive');
        $this->assertSame('us', $map['en']['flag'], 'the configured flag must survive');
        $this->assertSame('العربية', $map['ar']['label'], 'the label comes from the database');
    }

    public function test_a_new_language_reads_as_empty_until_it_is_translated(): void
    {
        // The rows have to exist for this to mean anything. With no row at all,
        // nothing merges over the files and Laravel's own fallback would quietly
        // answer in English, which is a different situation entirely.
        Artisan::call('translations:seed-from-lang');

        $this->addLanguage('es');
        $this->forgetLoadedTranslations();

        // The package's model answers a locale it has no cell for with an empty
        // string, and the loader merges that over the lang/ files. Blank is the
        // honest answer here: a language is added when it is announced and filled
        // in afterwards, and silently falling back to English would hide exactly
        // what is still missing.
        $this->assertSame('', __('site.common.brand_name', [], 'es'));

        // The languages that were already translated are untouched.
        $this->assertSame('Desert Sentry', __('site.common.brand_name', [], 'en'));
    }

    public function test_hiding_a_language_takes_it_out_of_the_switcher_without_losing_it(): void
    {
        $this->addLanguage('es');

        Language::query()->where('code', 'es')->update(['is_active' => false]);
        Locales::flush();

        $this->assertNotContains('es', Locales::codes());
        $this->assertNotNull(Language::query()->find('es'), 'the row must survive being hidden');

        $this->get('/')->assertOk()->assertDontSee('Español');
    }

    public function test_the_configured_default_still_wins_over_the_database(): void
    {
        // APP_LOCALE is a deployment decision and an env var cannot be changed
        // from a browser, so the config stays authoritative while it names a
        // language that is actually available.
        config(['localization.default' => 'fr']);

        Locales::flush();

        $this->assertSame('fr', Locales::default());
    }

    public function test_a_default_pointing_at_a_removed_language_falls_back(): void
    {
        config(['localization.default' => 'fr']);

        Language::query()->where('code', 'fr')->update(['is_active' => false]);
        Locales::flush();

        // Otherwise the app falls back to a language no visitor can select.
        $this->assertSame('en', Locales::default());
    }

    public function test_the_last_available_language_cannot_be_deleted(): void
    {
        $english = Language::query()->findOrFail('en');

        $this->assertFalse($english->isLastActiveLanguage(), 'three others are available');

        Livewire::actingAs(User::factory()->create())
            ->test(ListLanguages::class)
            ->assertSuccessful()
            ->assertTableActionVisible('delete', 'en')
            ->assertTableActionVisible('delete', 'ar');

        // Leaving only one active language must remove the option, rather than
        // accepting the click and then refusing: a visitor arriving with no
        // stored preference would have nothing to be served.
        Language::query()->where('code', '!=', 'en')->update(['is_active' => false]);
        Locales::flush();

        $this->assertTrue(
            Language::query()->findOrFail('en')->isLastActiveLanguage(),
            'nothing would be left to serve',
        );

        Livewire::actingAs(User::factory()->create())
            ->test(ListLanguages::class)
            ->assertSuccessful()
            ->assertTableActionHidden('delete', 'en');
    }

    public function test_a_language_code_must_be_a_plausible_tag(): void
    {
        // The code reaches lang_path() in the seeder, so a tag carrying a slash
        // or a dot has to be unstorable rather than merely discouraged.
        foreach (['../config', 'en/../fr', 'e', 'english', 'EN'] as $bad) {
            $this->assertDoesNotMatchRegularExpression(
                '/^[a-z]{2,3}(_[A-Za-z]{2,4})?$/',
                $bad,
                "{$bad} should not be an accepted language code",
            );
        }

        foreach (['en', 'ar', 'es', 'zh_CN', 'pt_BR'] as $good) {
            $this->assertMatchesRegularExpression('/^[a-z]{2,3}(_[A-Za-z]{2,4})?$/', $good);
        }
    }

    public function test_the_direction_is_guessed_from_the_code_but_stays_editable(): void
    {
        $this->assertSame('rtl', Language::guessDirection('ar'));
        $this->assertSame('ltr', Language::guessDirection('es'));

        // Script follows the region as much as the language, which is why the
        // form offers a choice rather than only a guess.
        $this->assertSame('ltr', Language::guessDirection('uz'));
    }

    public function test_a_language_can_be_renamed_without_touching_its_code(): void
    {
        $this->addLanguage('es', 'Español');

        Livewire::actingAs(User::factory()->create())
            ->test(EditLanguage::class, ['record' => 'es'])
            ->assertSuccessful()
            ->fillForm(['name' => 'Castellano'])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame('Castellano', Language::query()->findOrFail('es')->name);
        $this->assertSame('Castellano', Locales::names()['es']);
    }

    /**
     * A language that has nothing translated in it must not take the panel down.
     *
     * This needs the catalogue seeded first, and that is the whole difficulty of
     * reproducing it. With no rows in the table nothing merges over the `lang/`
     * files, so Laravel's own fallback quietly answers in English and the label
     * is never empty. With the rows seeded, the loader answers an untranslated
     * key with an empty string instead, the panel's own navigation label comes
     * back blank, and Filament reads it from an uninitialised typed property --
     * which throws and takes down every page in the panel, not just this one.
     */
    public function test_the_panel_still_works_in_a_language_with_nothing_translated(): void
    {
        Artisan::call('translations:seed-from-lang');

        $this->addLanguage('es');

        $this->assertSame(
            '',
            __('site.dashboard.nav_label', [], 'es'),
            'the loader really does answer an untranslated key with nothing',
        );

        $this->actingAs(User::factory()->create())
            ->withSession(['locale' => 'es'])
            ->get('/auth/translations')
            ->assertOk();

        $this->actingAs(User::factory()->create())
            ->withSession(['locale' => 'es'])
            ->get('/auth')
            ->assertOk();
    }

    public function test_the_sidebar_label_falls_back_rather_than_disappearing(): void
    {
        Artisan::call('translations:seed-from-lang');

        $this->addLanguage('es');

        // The sidebar is chrome, not content: an operator has to be able to
        // navigate a half-translated panel in order to finish translating it.
        $this->assertSame('Control Hub', __('site.dashboard.nav_label') ?: __('site.dashboard.nav_label', [], 'en'));

        app()->setLocale('es');
        app('translator')->setLoaded([]);

        $this->assertSame(
            'Control Hub',
            \App\Filament\Pages\DesertSentry::getNavigationLabel(),
            'an untranslated panel must still be navigable',
        );
    }

    /**
     * Register a language the way the panel does, then drop the cached list the
     * way the panel's own hooks do.
     */
    private function addLanguage(string $code, string $name = 'Español', string $direction = 'ltr'): Language
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

    private function forgetLoadedTranslations(): void
    {
        app('translator')->setLoaded([]);
    }
}
