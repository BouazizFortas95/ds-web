<?php

namespace Tests\Feature;

use App\Filament\Pages\DesertSentry;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Lang;
use Livewire\Livewire;
use Tests\TestCase;

class DashboardPageTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }

    public function test_the_panel_home_is_the_desert_sentry_page(): void
    {
        $this->assertSame(
            '/',
            (string) (new \ReflectionMethod(DesertSentry::class, 'getRoutePath'))
                ->invoke(null, \Filament\Facades\Filament::getPanel('auth')),
        );
    }

    public function test_the_panel_home_redirects_anonymous_visitors_to_login(): void
    {
        $this->get('/auth')->assertRedirect('/auth/login');
    }

    public function test_the_dashboard_renders_for_an_authenticated_operator(): void
    {
        $this->actingAs(User::factory()->create())
            ->get('/auth')
            ->assertOk()
            ->assertSee(__('site.dashboard.control_hub'))
            ->assertSee(__('site.dashboard.live_map'))
            ->assertSee(__('site.dashboard.system_log'));
    }

    public function test_the_dashboard_uses_the_desert_sentry_styling(): void
    {
        $content = $this->actingAs(User::factory()->create())
            ->get('/auth')
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('ds-surface', $content);
        $this->assertStringContainsString('ds-eyebrow', $content);
        $this->assertStringContainsString('ds-technical-ltr', $content);
    }

    public function test_the_dashboard_keeps_the_original_topology_and_metrics(): void
    {
        $content = $this->actingAs(User::factory()->create())
            ->get('/auth')
            ->assertOk()
            ->getContent();

        // The topology nodes and metric labels carried over from the standalone
        // dashboard must survive the move into Filament.
        foreach (['RK3588', 'YOLOv8', 'NVR', 'RTSP', 'RAM'] as $node) {
            $this->assertStringContainsString($node, $content);
        }

        foreach ([
            __('site.dashboard.threat_detection'),
            __('site.dashboard.edge_load'),
            __('site.dashboard.disk_wear'),
            __('site.dashboard.airgap'),
        ] as $label) {
            $this->assertStringContainsString($label, $content);
        }
    }

    public function test_switching_the_range_recomputes_the_metrics(): void
    {
        $this->actingAs(User::factory()->create());

        $component = Livewire::test(DesertSentry::class)
            ->assertSet('range', '24h')
            ->assertSee(__('site.dashboard.range_24h'));

        $daily = $component->instance()->metrics()['events']['value'];

        $component->call('setRange', '1h');

        $hourly = $component->instance()->metrics()['events']['value'];

        $this->assertNotSame($daily, $hourly, 'Range changes should change the detection count.');
        $this->assertSame(
            __('site.dashboard.metric_events_detail', ['range' => __('site.dashboard.range_1h')]),
            $component->instance()->metrics()['events']['detail'],
        );
    }

    public function test_an_unknown_range_is_rejected(): void
    {
        $this->actingAs(User::factory()->create());

        Livewire::test(DesertSentry::class)
            ->call('setRange', '99y')
            ->assertSet('range', '24h');
    }

    public function test_the_events_metric_is_not_labelled_with_a_fixed_window(): void
    {
        $this->actingAs(User::factory()->create());

        $component = Livewire::test(DesertSentry::class);

        // The unit is a noun; the window belongs in the range-aware detail, so
        // switching ranges never leaves a stale "24h" on a 1-hour or 7-day value.
        $this->assertSame(__('site.dashboard.events_unit'), $component->instance()->metrics()['events']['unit']);

        foreach (array_keys(DesertSentry::ranges()) as $range) {
            $component->call('setRange', $range);

            $detail = $component->instance()->metrics()['events']['detail'];

            $this->assertSame(
                __('site.dashboard.metric_events_detail', ['range' => $component->instance()->rangeLabel()]),
                $detail,
            );
            $this->assertStringNotContainsString('24h', $detail);
        }
    }

    public function test_the_range_badge_shows_the_localized_label_not_the_internal_key(): void
    {
        $this->actingAs(User::factory()->create());

        Livewire::test(DesertSentry::class)
            ->assertSet('range', '24h')
            ->assertSee(__('site.dashboard.range_24h'))
            ->call('setRange', '7d')
            ->assertSee(__('site.dashboard.range_7d'))
            ->assertDontSee('site.dashboard.range_7d');
    }

    public function test_localized_log_prose_keeps_the_page_direction(): void
    {
        $this->actingAs(User::factory()->create());

        $html = Livewire::test(DesertSentry::class)->html();

        // The log row is translated prose, so it must inherit the page direction
        // rather than being pinned to LTR like the technical spans around it.
        preg_match_all('/<li\s+class="([^"]*)"/', $html, $items);

        $this->assertNotEmpty($items[0], 'The system log should render at least one row.');

        foreach ($items[1] as $rowClasses) {
            $this->assertStringNotContainsString('ds-technical-ltr', $rowClasses);
        }

        // The level tag and the timestamp *are* technical, so they keep it.
        $this->assertStringContainsString('ds-technical-ltr', $html);
    }

    public function test_the_stream_can_be_paused_and_resumed(): void
    {
        $this->actingAs(User::factory()->create());

        $component = Livewire::test(DesertSentry::class)
            ->assertSet('streaming', true)
            ->call('toggleStreaming')
            ->assertSet('streaming', false)
            ->assertSee(__('site.dashboard.resume_stream'))
            ->call('toggleStreaming')
            ->assertSet('streaming', true)
            ->assertSee(__('site.dashboard.pause_stream'));
    }

    public function test_polling_advances_the_stream(): void
    {
        $this->actingAs(User::factory()->create());

        $component = Livewire::test(DesertSentry::class)->assertSet('tick', 0);

        $before = $component->instance()->systemLog()[0]['time'];

        $component->call('refreshStream');

        $this->assertSame(1, $component->get('tick'));
        $this->assertNotSame(
            $before,
            $component->instance()->systemLog()[0]['time'],
            'Advancing the stream should age the newest log entry.',
        );
    }

    public function test_the_sparkline_is_bounded_for_every_range(): void
    {
        $this->actingAs(User::factory()->create());

        $component = Livewire::test(DesertSentry::class);

        foreach (array_keys(DesertSentry::ranges()) as $range) {
            $component->call('setRange', $range);

            foreach ($component->instance()->sparkline() as $value) {
                $this->assertGreaterThanOrEqual(0, $value);
                $this->assertLessThanOrEqual(100, $value);
            }
        }
    }

    public function test_the_public_dashboard_route_redirects_into_the_panel(): void
    {
        $this->get('/dashboard')->assertRedirect('/auth');
        $this->get('/dashboard')->assertRedirect('/auth');
        $this->get('/dashboard')->assertRedirect('/auth');
    }

    public function test_the_dashboard_renders_in_every_locale(): void
    {
        $user = User::factory()->create();

        // `Lang::get` is given an explicit locale rather than relying on `__()`.
        // Inside a test body `__()` resolves against the test process's own
        // locale, not the one the response was rendered in, so it would happily
        // agree with a response that came back in the wrong language.
        //
        // The session is flushed between each case because the resolved locale
        // is cached there, and on the next request it outranks the cookie.
        foreach (['en', 'ar', 'fr'] as $locale) {
            $this->flushSession();

            $this->actingAs($user)
                ->withCookie('ds_locale', $locale)
                ->get('/auth')
                ->assertOk()
                ->assertHeader('Content-Language', $locale)
                ->assertSee(Lang::get('site.dashboard.live_map', [], $locale))
                ->assertSee(Lang::get('site.dashboard.system_log', [], $locale));
        }
    }

    public function test_the_dashboard_is_gated_behind_the_panel_authentication(): void
    {
        // The page is reachable only through the panel, so the gate is the
        // panel's `Authenticate` middleware rather than the page itself.
        $this->get('/auth')->assertRedirect('/auth/login');

        $this->get('/auth/login')
            ->assertOk()
            ->assertHeader('Content-Language', 'en');

        // The sign-in page Filament bounced us to still renders in the language
        // `SetLocale` resolved for the panel request that got redirected.
        $this->flushSession();

        $this->withCookie('ds_locale', 'ar')->get('/auth')->assertRedirect('/auth/login');

        $this->get('/auth/login')
            ->assertOk()
            ->assertHeader('Content-Language', 'ar')
            ->assertSee(Lang::get('site.auth.email', [], 'ar'));
    }
}
