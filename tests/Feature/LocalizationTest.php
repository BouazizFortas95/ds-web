<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Crypt;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * The locale travels in the `ds_locale` cookie and the session, never in the
 * URL, so these tests drive the language by cookie rather than by path prefix.
 */
class LocalizationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }

    /**
     * Pretend to be a returning visitor who already chose this language.
     */
    private function asLocale(string $locale): static
    {
        return $this->withCookie('ds_locale', $locale);
    }

    public static function localeProvider(): array
    {
        return [
            'english' => ['en', 'ltr', 'Project Desert Sentry', 'Login'],
            'arabic' => ['ar', 'rtl', 'مشروع Desert Sentry', 'تسجيل الدخول'],
            'french' => ['fr', 'ltr', 'Projet Desert Sentry', 'Connexion'],
        ];
    }

    public static function authPageProvider(): array
    {
        return [
            'english login' => ['/login', 'en', 'ltr', 'Login · Desert Sentry', 'Email address', 'Password'],
            'arabic login' => ['/login', 'ar', 'rtl', 'تسجيل الدخول · حارس الصحراء', 'البريد الإلكتروني', 'كلمة المرور'],
            'french login' => ['/login', 'fr', 'ltr', 'Connexion · Desert Sentry', 'Adresse e-mail', 'Mot de passe'],
            'english register' => ['/register', 'en', 'ltr', 'Sign Up · Desert Sentry', 'Full name', 'Email address'],
            'arabic register' => ['/register', 'ar', 'rtl', 'إنشاء حساب · حارس الصحراء', 'الاسم الكامل', 'البريد الإلكتروني'],
            'french register' => ['/register', 'fr', 'ltr', 'Créer un compte · Desert Sentry', 'Nom complet', 'Adresse e-mail'],
        ];
    }

    public static function pageProvider(): array
    {
        return [
            'landing' => ['/'],
            'sign in' => ['/login'],
            'sign up' => ['/register'],
        ];
    }

    public static function filamentLocaleProvider(): array
    {
        return [
            'english' => ['en', 'ltr', 'Sign in'],
            'arabic' => ['ar', 'rtl', 'تسجيل الدخول'],
            'french' => ['fr', 'ltr', 'Connectez-vous à votre compte'],
        ];
    }

    #[DataProvider('localeProvider')]
    public function test_pages_render_the_selected_language_and_direction(string $locale, string $direction, string $headline, string $login): void
    {
        $this->asLocale($locale)
            ->get('/')
            ->assertOk()
            ->assertHeader('Content-Language', $locale)
            ->assertHeader('Vary', 'Accept-Language')
            ->assertSee('<html lang="' . $locale . '" dir="' . $direction . '"', false)
            ->assertSee($headline)
            ->assertSee($login);
    }

    public function test_the_english_document_title_is_preserved_exactly(): void
    {
        $title = 'Project Desert Sentry (حارس الصحراء) — Next-Gen Embedded Edge AI NVR Operating System';

        $this->asLocale('en')
            ->get('/')
            ->assertOk()
            ->assertSee('<title>' . $title . '</title>', false);
    }

    #[DataProvider('authPageProvider')]
    public function test_auth_pages_are_localized(string $path, string $locale, string $direction, string $title, string $emailLabel, string $passwordLabel): void
    {
        $this->asLocale($locale)
            ->get($path)
            ->assertOk()
            ->assertHeader('Content-Language', $locale)
            ->assertSee('<html lang="' . $locale . '" dir="' . $direction . '"', false)
            ->assertSee('<title>' . $title . '</title>', false)
            ->assertSee($emailLabel)
            ->assertSee($passwordLabel);
    }

    #[DataProvider('pageProvider')]
    public function test_every_page_lives_at_exactly_one_address(string $path): void
    {
        $this->get($path)->assertOk();
    }

    /**
     * @param  list<string>  $paths
     */
    #[DataProvider('retiredPrefixProvider')]
    public function test_locale_prefixed_urls_are_gone(string $path): void
    {
        // The prefixed routes were deleted outright rather than redirected, so
        // there is exactly one address per page and no duplicate-content pairs.
        $this->get($path)->assertNotFound();
    }

    public static function retiredPrefixProvider(): array
    {
        return [
            'arabic landing' => ['/ar'],
            'french landing' => ['/fr'],
            'arabic sign in' => ['/ar/login'],
            'french sign up' => ['/fr/register'],
            'arabic dashboard' => ['/ar/dashboard'],
            'unsupported locale' => ['/es'],
            'unsupported sign in' => ['/es/login'],
        ];
    }

    public function test_no_page_route_carries_a_locale_segment(): void
    {
        foreach (['home', 'login', 'register', 'dashboard'] as $name) {
            $path = parse_url(route($name), PHP_URL_PATH);

            $this->assertNotContains(
                $path,
                array_map(
                    fn (string $locale): string => '/'.$locale,
                    config('localization.locales'),
                ),
                "[{$name}] should not be locale-prefixed.",
            );
        }

        // The switcher endpoint is the one deliberate exception.
        $this->assertSame(url('/locale/fr'), route('locale.set', ['locale' => 'fr']));
    }

    public function test_a_locale_query_parameter_is_ignored(): void
    {
        $this->asLocale('ar')
            ->get('/login?locale=fr')
            ->assertOk()
            ->assertHeader('Content-Language', 'ar')
            ->assertSee('lang="ar" dir="rtl"', false);
    }

    public function test_an_unsupported_query_locale_is_ignored(): void
    {
        $this->asLocale('ar')
            ->get('/login?locale=es')
            ->assertOk()
            ->assertHeader('Content-Language', 'ar')
            ->assertSee('lang="ar" dir="rtl"', false);
    }

    public function test_auth_forms_post_with_a_csrf_token(): void
    {
        // These forms used to carry neither `method` nor `action`, so submitting
        // them issued a GET and pushed the credentials into the URL.
        foreach (['/login', '/register'] as $path) {
            $content = $this->get($path)->assertOk()->getContent();

            $this->assertMatchesRegularExpression(
                '/<form\s+method="POST"\s+action="[^"]+"/',
                $content,
                "[{$path}] should submit a POST to a real endpoint.",
            );
            $this->assertStringContainsString(
                'name="_token"',
                $content,
                "[{$path}] should carry a CSRF token.",
            );
            $this->assertStringNotContainsString(
                '<form class="space-y-5">',
                $content,
                "[{$path}] should no longer fall back to a bare GET form.",
            );
        }
    }

    public function test_the_login_form_never_reputs_a_password_in_the_markup(): void
    {
        $content = $this->get('/login')->assertOk()->getContent();

        $this->assertStringNotContainsString('type="password" value=', $content);
        $this->assertMatchesRegularExpression('/name="password"[^>]*>/', $content);
    }

    public function test_the_register_form_demands_a_confirmed_password(): void
    {
        $content = $this->get('/register')->assertOk()->getContent();

        $this->assertStringContainsString('name="password_confirmation"', $content);
    }

    public function test_the_dashboard_entry_point_hands_off_to_the_panel(): void
    {
        $this->get('/dashboard')->assertRedirect('/auth');
    }

    public function test_the_panel_dashboard_renders_the_selected_language(): void
    {
        // Each assertion needs a clean session: the resolved locale is persisted
        // there, and it outranks the cookie, so a second request in the same test
        // would inherit the first one's language.
        $this->flushSession();

        $this->actingAs(User::factory()->create())
            ->asLocale('ar')
            ->get('/auth')
            ->assertOk()
            ->assertHeader('Content-Language', 'ar')
            ->assertSee('خريطة النظام المباشرة');

        $this->flushSession();

        $this->actingAs(User::factory()->create())
            ->asLocale('fr')
            ->get('/auth')
            ->assertOk()
            ->assertHeader('Content-Language', 'fr')
            ->assertSee('JOURNAL SYSTÈME');
    }

    public function test_the_session_locale_wins_over_the_cookie_locale(): void
    {
        $this->withCookie('ds_locale', 'ar')
            ->withSession(['locale' => 'fr'])
            ->get('/login')
            ->assertOk()
            ->assertHeader('Content-Language', 'fr')
            ->assertSee('lang="fr"', false);
    }

    public function test_the_cookie_locale_is_used_when_no_session_locale_exists(): void
    {
        $this->asLocale('ar')
            ->get('/login')
            ->assertOk()
            ->assertHeader('Content-Language', 'ar')
            ->assertSee('lang="ar" dir="rtl"', false);
    }

    public function test_the_browser_accept_language_header_is_negotiated(): void
    {
        $this->withHeader('Accept-Language', 'fr-FR,fr;q=0.9,en;q=0.8')
            ->get('/login')
            ->assertOk()
            ->assertHeader('Content-Language', 'fr')
            ->assertSee('Connexion');
    }

    public function test_region_specific_arabic_accept_language_is_negotiated(): void
    {
        $this->withHeader('Accept-Language', 'ar-SA,ar;q=0.9,en;q=0.8')
            ->get('/login')
            ->assertOk()
            ->assertHeader('Content-Language', 'ar')
            ->assertSee('lang="ar" dir="rtl"', false);
    }

    public function test_the_stored_locale_wins_over_the_accept_language_header(): void
    {
        $this->asLocale('fr')
            ->withHeader('Accept-Language', 'ar-SA,ar;q=0.9')
            ->get('/login')
            ->assertOk()
            ->assertHeader('Content-Language', 'fr');
    }

    public function test_an_unsupported_accept_language_header_uses_the_configured_default(): void
    {
        config(['localization.default' => 'fr']);

        $this->withHeader('Accept-Language', 'es-ES,es;q=0.9')
            ->get('/login')
            ->assertOk()
            ->assertHeader('Content-Language', 'fr')
            ->assertSee('lang="fr"', false);
    }

    public function test_an_invalid_configured_default_falls_back_to_the_first_supported_locale(): void
    {
        config(['localization.default' => 'es']);

        $this->get('/login')
            ->assertOk()
            ->assertHeader('Content-Language', 'en');
    }

    public function test_the_selected_locale_is_persisted_for_the_next_request(): void
    {
        $response = $this->asLocale('ar')->get('/login')->assertOk();

        $response->assertSessionHas('locale', 'ar');

        $cookie = collect($response->headers->getCookies())
            ->first(fn ($cookie) => $cookie->getName() === 'ds_locale');

        $this->assertNotNull($cookie);
        $this->assertStringEndsWith(
            '|ar',
            Crypt::decryptString($cookie->getValue()),
            'The encrypted locale cookie should store the resolved locale.',
        );
        $this->assertTrue($cookie->isHttpOnly());
        $this->assertSame('lax', $cookie->getSameSite());
        $this->assertSame(365 * 24 * 60 * 60, $cookie->getMaxAge());
        $this->assertSame('/', $cookie->getPath());
    }

    // ── The locale endpoint ──────────────────────────────────────────────

    public function test_the_locale_endpoint_persists_the_chosen_language(): void
    {
        $this->asLocale('en')
            ->get('/locale/ar')
            ->assertRedirect('/')
            ->assertSessionHas('locale', 'ar');

        $this->assertSame('ar', $this->asLocale('ar')->get('/')->headers->get('Content-Language'));
    }

    public function test_the_locale_endpoint_returns_the_visitor_to_the_same_origin_page(): void
    {
        // The referer is the page the switcher was clicked on, so switching
        // language in place has to keep the visitor where they were.
        $this->get('/locale/fr', ['referer' => url('/login')])
            ->assertRedirect(url('/login'));
    }

    public function test_the_locale_endpoint_preserves_the_referer_query_string(): void
    {
        $this->get('/locale/fr', ['referer' => url('/auth').'?remember=1'])
            ->assertRedirect(url('/auth').'?remember=1');
    }

    public function test_the_locale_endpoint_refuses_an_off_site_referer(): void
    {
        $this->get('/locale/fr', ['referer' => 'https://evil.example.test/steal'])
            ->assertRedirect('/');
    }

    public function test_the_locale_endpoint_refuses_a_referer_that_only_looks_local(): void
    {
        // Same host, different scheme and port: a different origin, so the
        // referer must not be trusted.
        $this->get('/locale/fr', ['referer' => 'http://'.request()->getHost().':9999/steal'])
            ->assertRedirect('/');
    }

    public function test_the_locale_endpoint_falls_back_home_without_a_referer(): void
    {
        $this->get('/locale/fr')->assertRedirect('/');
    }

    public function test_the_locale_endpoint_rejects_an_unsupported_language(): void
    {
        $this->get('/locale/es')->assertNotFound();
    }

    public function test_the_locale_path_parameter_wins_over_the_stored_locale(): void
    {
        $this->withSession(['locale' => 'en'])
            ->get('/locale/ar')
            ->assertRedirect('/')
            ->assertSessionHas('locale', 'ar');
    }

    // ── The switchers ────────────────────────────────────────────────────

    public function test_the_language_switcher_exposes_every_supported_locale(): void
    {
        $content = $this->get('/')->assertOk()->getContent();

        $options = $this->localeSelectOptions($content);

        $this->assertSame(['en', 'ar', 'fr'], array_keys($options));

        // Every option targets the one switcher endpoint; there is no
        // "this page in French" address to link to any more.
        foreach (['en', 'ar', 'fr'] as $locale) {
            $this->assertSame(route('locale.set', ['locale' => $locale]), $options[$locale]);
        }

        $this->assertStringContainsString('>English<', $content);
        $this->assertStringContainsString('>العربية<', $content);
        $this->assertStringContainsString('>Français<', $content);
    }

    public function test_the_active_locale_is_the_selected_option(): void
    {
        foreach (config('localization.locales') as $locale) {
            // The resolved locale is cached in the session, so each iteration
            // needs a clean one or it would inherit the previous language.
            $this->flushSession();

            $content = $this->asLocale($locale)->get('/')->assertOk()->getContent();

            // Exactly one option is selected, and it is the active locale.
            $this->assertSame(
                1,
                preg_match_all('/<option[^>]*\bselected\b/', $content),
                "[{$locale}] should preselect exactly one option.",
            );

            $this->assertMatchesRegularExpression(
                '/<option[^>]*value="[^"]*' . preg_quote('/locale/' . $locale, '/') . '"[^>]*\bselected\b/',
                $content,
                "[{$locale}] should preselect its own locale.",
            );
        }
    }

    public function test_the_locale_picker_is_a_navigating_select(): void
    {
        $content = $this->get('/')->assertOk()->getContent();

        // The hook the delegated listener keys off, plus a real <label for> so
        // the control keeps an accessible name at every breakpoint.
        $this->assertStringContainsString('data-ds-locale-select', $content);
        $this->assertStringContainsString('sr-only', $content);
        $this->assertStringContainsString('sm:not-sr-only', $content);
        $this->assertStringContainsString('ds-locale-select', $content);

        // A bare <select> cannot navigate, so the behaviour ships with it.
        $this->assertStringContainsString("closest('[data-ds-locale-select]')", $content);
        $this->assertStringContainsString('window.location.assign(select.value)', $content);
    }

    public function test_the_locale_picker_still_works_without_javascript(): void
    {
        $content = $this->get('/')->assertOk()->getContent();

        // The anchor row is retained inside <noscript>, so the picker does not
        // silently become a dead control when scripting is unavailable.
        $this->assertStringContainsString('<noscript>', $content);

        foreach (['en', 'ar', 'fr'] as $locale) {
            $this->assertStringContainsString('hreflang="' . $locale . '"', $content);
        }

        $this->assertStringContainsString('aria-current="page"', $content);
    }

    #[DataProvider('pageProvider')]
    public function test_the_locale_picker_keeps_an_accessible_name(string $path): void
    {
        $flat = preg_replace('/\s+/', ' ', $this->get($path)->assertOk()->getContent());

        // The label must point at the control, otherwise the select is announced
        // with no name once the visible word is hidden below the `sm` breakpoint.
        $this->assertSame(1, preg_match('/<label\s+for="([^"]+)"/', $flat, $label), "[{$path}] needs a <label>.");
        $this->assertSame('ds-locale-select', $label[1]);

        $this->assertSame(1, preg_match('/<select\b[^>]*>/', $flat, $tag), "[{$path}] needs a <select>.");
        $this->assertSame(1, preg_match('/\bid="([^"]+)"/', $tag[0], $id));
        $this->assertSame('ds-locale-select', $id[1]);
        $this->assertStringContainsString('data-ds-locale-select', $tag[0]);
    }

    // ── The Filament panel ───────────────────────────────────────────────

    #[DataProvider('filamentLocaleProvider')]
    public function test_the_filament_login_page_is_localized(string $locale, string $direction, string $heading): void
    {
        $this->asLocale($locale)
            ->get('/auth/login')
            ->assertOk()
            ->assertHeader('Content-Language', $locale)
            ->assertSee('<html', false)
            ->assertSee('lang="' . $locale . '"', false)
            ->assertSee('dir="' . $direction . '"', false)
            ->assertSee('fi-locale-switcher', false)
            ->assertSee($heading);
    }

    public function test_the_filament_switcher_offers_every_locale_through_the_endpoint(): void
    {
        $content = $this->asLocale('fr')->get('/auth/login')->assertOk()->getContent();

        $options = $this->localeSelectOptions($content);

        $this->assertSame(['en', 'ar', 'fr'], array_keys($options));

        foreach (['en', 'ar', 'fr'] as $locale) {
            $this->assertSame(route('locale.set', ['locale' => $locale]), $options[$locale]);
        }

        // The panel no longer prints a locale anywhere in its URLs.
        $this->assertStringNotContainsString('?locale=', $content);
    }

    public function test_the_filament_locale_selection_survives_the_authentication_redirect(): void
    {
        $this->asLocale('fr')
            ->get('/auth')
            ->assertRedirect('/auth/login')
            ->assertSessionHas('locale', 'fr');

        $this->get('/auth/login')
            ->assertOk()
            ->assertHeader('Content-Language', 'fr')
            ->assertSee('Connexion');
    }

    /**
     * Pull `code => url` pairs out of a rendered locale `<select>`.
     *
     * @return array<string, string>
     */
    private function localeSelectOptions(string $content): array
    {
        preg_match_all(
            '/<option[^>]*value="([^"]+)"[^>]*lang="([a-z]{2})"/',
            $content,
            $matches,
            PREG_SET_ORDER,
        );

        $options = [];

        foreach ($matches as $match) {
            $options[$match[2]] = html_entity_decode($match[1], ENT_QUOTES | ENT_HTML5, 'UTF-8');
        }

        return $options;
    }
}
