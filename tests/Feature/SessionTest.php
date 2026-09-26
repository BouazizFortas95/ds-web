<?php

namespace Tests\Feature;

use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class SessionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }

    /**
     * The language no longer rides along on the redirect target, so a test that
     * cares about it has to say which one it wants.
     */
    private function asLocale(string $locale): static
    {
        return $this->withCookie('ds_locale', $locale);
    }

    /**
     * A signed-out visitor to the landing page.
     */
    private function landingPageAsGuest(string $locale = 'en'): string
    {
        return $this->asLocale($locale)->get('/')->assertOk()->getContent();
    }

    private function landingPageAsOperator(string $locale = 'en'): string
    {
        return $this->actingAs(User::factory()->create())
            ->asLocale($locale)
            ->get('/')
            ->assertOk()
            ->getContent();
    }

    public function test_logout_ends_the_session_and_redirects_home(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->withSession(['marker' => 'value'])
            ->post('/logout')
            ->assertRedirect(route('home'));

        $this->assertGuest();
        $this->assertNull(session('marker'), 'Session data should be invalidated on logout.');
    }

    public function test_logout_regenerates_the_csrf_token(): void
    {
        $this->get('/login');
        $before = session()->token();

        $this->post('/logout');

        $this->assertNotSame($before, session()->token());
    }

    public function test_logout_is_repeatable_for_an_anonymous_visitor(): void
    {
        $this->post('/logout')->assertRedirect();
        $this->assertGuest();

        $this->post('/logout')->assertRedirect();
        $this->assertGuest();
    }

    public function test_logout_keeps_the_visitor_in_their_language(): void
    {
        $this->asLocale('ar')->get('/login')->assertOk();

        $this->post('/logout')->assertRedirect(route('home'));

        // The session is wiped by logging out, so the language now has to come
        // from the `ds_locale` cookie, which deliberately outlives the session.
        $this->assertSame(
            'ar',
            $this->asLocale('ar')->get('/')->assertOk()->headers->get('Content-Language'),
        );
    }

    public function test_logout_requires_the_post_method(): void
    {
        $this->get('/logout')->assertStatus(405);
    }

    public function test_the_logout_route_is_csrf_protected(): void
    {
        // The 419 rejection itself cannot be asserted in a feature test because
        // `ValidateCsrfToken` intentionally skips validation during tests. So
        // assert the route-level guarantees instead: it is a POST endpoint in the
        // `web` group, and that group carries the CSRF middleware.
        $route = Route::getRoutes()->getByName('logout');

        $this->assertNotNull($route);
        $this->assertContains('POST', $route->methods());
        $this->assertContains('web', $route->middleware());

        $webGroup = app('router')->getMiddlewareGroups()['web'] ?? [];

        $this->assertContains(
            \Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class,
            $webGroup,
        );

        $this->get('/logout')->assertStatus(405);
    }

    public function test_logging_out_of_the_custom_route_also_ends_the_filament_session(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user);

        $this->assertTrue(Auth::guard('web')->check());

        $this->post('/logout');

        $this->assertFalse(Auth::guard('web')->check());
    }

    public function test_the_locale_cookie_survives_logout(): void
    {
        $response = $this->asLocale('ar')->get('/login')->assertOk();

        $cookie = collect($response->headers->getCookies())
            ->first(fn ($cookie) => $cookie->getName() === 'ds_locale');

        $this->assertNotNull($cookie);
        $this->assertStringEndsWith('|ar', Crypt::decryptString($cookie->getValue()));
    }

    // ── The landing page adapts to who is asking ────────────────────────

    public function test_a_guest_is_offered_sign_in_and_sign_up_but_not_the_dashboard(): void
    {
        $content = $this->landingPageAsGuest();

        $this->assertStringContainsString('href="'.route('login').'"', $content);
        $this->assertStringContainsString('href="'.route('register').'"', $content);

        // A guest has no session to put behind the panel, so the nav must not
        // advertise it; the hero's primary call to action is the one that stays,
        // and Filament funnels that through the sign-in form.
        $this->assertSame(
            1,
            substr_count($content, 'href="'.Filament::getUrl().'"'),
            'Only the hero call to action should point a guest at the panel.',
        );
    }

    public function test_a_signed_in_operator_is_offered_the_dashboard_but_not_the_auth_forms(): void
    {
        $content = $this->landingPageAsOperator();

        // Offering a sign-in link to someone who is already signed in invites a
        // second account on a console meant to have one operator.
        $this->assertStringNotContainsString('href="'.route('login').'"', $content);
        $this->assertStringNotContainsString('href="'.route('register').'"', $content);

        $this->assertStringContainsString('href="'.Filament::getUrl().'"', $content);
    }

    public function test_the_operator_dashboard_link_goes_straight_to_the_panel(): void
    {
        // No bounce through the `/dashboard` shim: the link names the panel home.
        $this->assertSame(Filament::getUrl(), url('/auth'));
        $this->get(Filament::getUrl())->assertRedirect('/auth/login');
    }

    public function test_the_auth_pages_link_back_to_the_landing_page(): void
    {
        $this->get('/login')->assertOk()
            ->assertSee('href="'.route('register').'"', false)
            ->assertSee('href="'.route('home').'"', false);

        $this->get('/register')->assertOk()
            ->assertSee('href="'.route('login').'"', false);
    }

    /**
     * The nav used to be rebuilt per language, because each language had its own
     * address. With one address per page the markup is now identical in every
     * language, and only the words change.
     */
    public function test_the_navigation_targets_do_not_change_with_the_language(): void
    {
        $this->assertSame(
            $this->linkTargets($this->landingPageAsGuest('en')),
            $this->linkTargets($this->landingPageAsGuest('ar')),
            'The same pages should be linked in every language.',
        );
    }

    public function test_the_navigation_targets_do_not_change_for_an_operator_either(): void
    {
        $this->assertSame(
            $this->linkTargets($this->landingPageAsOperator('en')),
            $this->linkTargets($this->landingPageAsOperator('fr')),
            'The same pages should be linked in every language.',
        );
    }

    /**
     * The distinct in-site addresses a rendered page links to, absolute or
     * root-relative, sorted so the comparison does not depend on markup order.
     *
     * @return list<string>
     */
    private function linkTargets(string $html): array
    {
        preg_match_all('/<a\b[^>]*\shref="([^"]+)"/', $html, $matches);

        $targets = [];

        foreach ($matches[1] as $href) {
            $targets[$href] = true;
        }

        $targets = array_keys($targets);

        // Anchor jumps and off-site links say nothing about the locale scheme.
        $targets = array_values(array_filter(
            $targets,
            fn (string $href): bool => ! str_starts_with($href, '#')
                && ! str_starts_with($href, 'http')
                && ! str_starts_with($href, 'mailto:')
                && ! str_starts_with($href, 'tel:'),
        ));

        sort($targets);

        return $targets;
    }

    public function test_the_panel_locale_switcher_targets_the_locale_endpoint(): void
    {
        $content = $this->asLocale('fr')->get('/auth/login')->assertOk()->getContent();

        $this->assertStringContainsString('fi-locale-switcher-select', $content);
        $this->assertStringContainsString('data-ds-locale-select', $content);

        // `fr` is the active locale, so its option is the preselected one.
        $this->assertMatchesRegularExpression(
            '/<option[^>]*value="[^"]*\/locale\/fr"[^>]*\bselected\b/',
            $content,
        );
    }
}
