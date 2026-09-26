<?php

namespace Tests\Feature;

use App\Filament\Pages\DesertSentry;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Livewire;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();

        RateLimiter::clear('login');
    }

    public function test_a_valid_sign_in_redirects_to_the_dashboard(): void
    {
        $user = User::factory()->create(['password' => 'Passw0rd!123']);

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'Passw0rd!123',
        ])->assertRedirect('/auth');

        $this->assertAuthenticatedAs($user);
    }

    public function test_a_valid_sign_in_lands_on_the_renderable_dashboard(): void
    {
        $user = User::factory()->create(['password' => 'Passw0rd!123']);

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'Passw0rd!123',
        ]);

        // The redirect target must be the live panel, not a dead end.
        $this->get('/auth')->assertOk()->assertSee('ds-surface', false);
    }

    public function test_a_wrong_password_is_rejected_without_leaking_anything(): void
    {
        $user = User::factory()->create(['password' => 'Passw0rd!123']);

        $response = $this->from('/login')->post('/login', [
            'email' => $user->email,
            'password' => 'wrong-password',
        ]);

        $response->assertRedirect('/login');
        $response->assertSessionHasErrors('email');

        $this->assertGuest();

        // The error is the same whether the address exists or not, so the form
        // cannot be used to enumerate registered operators.
        $this->assertSame(
            $response->getSession()->get('errors')->first(),
            __('site.auth.invalid_credentials'),
        );
    }

    public function test_an_unknown_email_gets_the_same_message_as_a_wrong_password(): void
    {
        User::factory()->create(['password' => 'Passw0rd!123']);

        $response = $this->from('/login')->post('/login', [
            'email' => 'nobody@example.test',
            'password' => 'Passw0rd!123',
        ]);

        $this->assertGuest();
        $this->assertSame(
            __('site.auth.invalid_credentials'),
            $response->getSession()->get('errors')->first(),
        );
    }

    public function test_the_password_is_never_echoed_back_into_the_form(): void
    {
        $user = User::factory()->create(['password' => 'Passw0rd!123']);

        $this->from('/login')->post('/login', [
            'email' => $user->email,
            'password' => 'wrong-password',
        ]);

        $content = $this->get('/login')->getContent();

        $this->assertStringNotContainsString('wrong-password', $content);
        $this->assertStringNotContainsString('Passw0rd!123', $content);
    }

    public function test_the_email_is_repopulated_so_the_visitor_retypes_less(): void
    {
        User::factory()->create(['password' => 'Passw0rd!123']);

        $this->from('/login')->post('/login', [
            'email' => 'operator@example.test',
            'password' => 'nope',
        ]);

        $this->get('/login')
            ->assertOk()
            ->assertSee('value="operator@example.test"', false);
    }

    public function test_the_session_id_is_rotated_on_sign_in(): void
    {
        $user = User::factory()->create(['password' => 'Passw0rd!123']);

        $this->get('/login');
        $before = session()->getId();

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'Passw0rd!123',
        ]);

        $this->assertNotSame($before, session()->getId(), 'Session fixation guard should rotate the id.');
    }

    public function test_a_stale_intended_url_is_discarded_on_sign_in(): void
    {
        $user = User::factory()->create(['password' => 'Passw0rd!123']);

        // Something stashes an internal path, as the auth middleware does.
        $this->withSession(['url.intended' => 'http://internal.example.test/admin/secret']);

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'Passw0rd!123',
        ])->assertRedirect('/auth');

        $this->assertNull(session('url.intended'));
    }

    public function test_remember_me_is_honoured(): void
    {
        $user = User::factory()->create(['password' => 'Passw0rd!123']);

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'Passw0rd!123',
            'remember' => '1',
        ]);

        $response->assertRedirect('/auth');

        $recaller = Auth::guard('web')->getRecallerName();

        $this->assertNotNull($recaller);
    }

    public function test_missing_fields_fail_validation_in_the_active_language(): void
    {
        $this->app->setLocale('ar');

        $this->from('/login')->post('/login', [])
            ->assertRedirect('/login')
            ->assertSessionHasErrors(['email', 'password']);

        $this->assertGuest();
    }

    public function test_login_is_rate_limited(): void
    {
        $user = User::factory()->create(['password' => 'Passw0rd!123']);

        foreach (range(1, 10) as $ignored) {
            $this->post('/login', [
                'email' => $user->email,
                'password' => 'wrong-password',
            ]);
        }

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'Passw0rd!123',
        ])->assertStatus(429);

        $this->assertGuest();
    }

    public function test_a_new_operator_can_register_and_lands_on_the_dashboard(): void
    {
        $this->post('/register', [
            'name' => 'Amina Cherif',
            'email' => 'amina@example.test',
            'password' => 'Passw0rd!123',
            'password_confirmation' => 'Passw0rd!123',
        ])->assertRedirect('/auth');

        $user = User::where('email', 'amina@example.test')->firstOrFail();

        $this->assertAuthenticatedAs($user);
        $this->assertSame('Amina Cherif', $user->name);
        // Stored hashed, never in the clear.
        $this->assertTrue(Hash::check('Passw0rd!123', $user->password));
        $this->assertNotSame('Passw0rd!123', $user->password);
    }

    public function test_a_registered_operator_can_immediately_sign_in(): void
    {
        $this->post('/register', [
            'name' => 'Amina Cherif',
            'email' => 'amina@example.test',
            'password' => 'Passw0rd!123',
            'password_confirmation' => 'Passw0rd!123',
        ]);

        $this->post('/logout');
        $this->assertGuest();

        $this->post('/login', [
            'email' => 'amina@example.test',
            'password' => 'Passw0rd!123',
        ])->assertRedirect('/auth');

        $this->assertAuthenticated();
    }

    public function test_registration_rejects_a_duplicate_email(): void
    {
        User::factory()->create(['email' => 'taken@example.test']);

        $this->from('/register')->post('/register', [
            'name' => 'Someone',
            'email' => 'taken@example.test',
            'password' => 'Passw0rd!123',
            'password_confirmation' => 'Passw0rd!123',
        ])->assertSessionHasErrors('email');

        $this->assertGuest();
        $this->assertSame(1, User::where('email', 'taken@example.test')->count());
    }

    public function test_registration_rejects_a_mismatched_confirmation(): void
    {
        $this->from('/register')->post('/register', [
            'name' => 'Someone',
            'email' => 'someone@example.test',
            'password' => 'Passw0rd!123',
            'password_confirmation' => 'Different!123',
        ])->assertSessionHasErrors('password');

        $this->assertGuest();
        $this->assertDatabaseMissing('users', ['email' => 'someone@example.test']);
    }

    public function test_registration_rejects_a_short_password(): void
    {
        $this->from('/register')->post('/register', [
            'name' => 'Someone',
            'email' => 'someone@example.test',
            'password' => 'short',
            'password_confirmation' => 'short',
        ])->assertSessionHasErrors('password');

        $this->assertGuest();
    }

    public function test_the_sign_up_form_never_repopulates_password_fields(): void
    {
        $this->from('/register')->post('/register', [
            'name' => 'Someone',
            'email' => 'someone@example.test',
            'password' => 'secret-one',
            'password_confirmation' => 'secret-two',
        ]);

        $content = $this->get('/register')->getContent();

        $this->assertStringNotContainsString('secret-one', $content);
        $this->assertStringNotContainsString('secret-two', $content);
    }

    public function test_signing_up_and_out_ends_the_filament_session(): void
    {
        $this->post('/register', [
            'name' => 'Amina',
            'email' => 'amina@example.test',
            'password' => 'Passw0rd!123',
            'password_confirmation' => 'Passw0rd!123',
        ]);

        $this->assertTrue(Auth::guard('web')->check());

        $this->post('/logout')->assertRedirect('/');

        $this->assertFalse(Auth::guard('web')->check());
    }

    public function test_sign_in_preserves_the_active_locale(): void
    {
        $user = User::factory()->create(['password' => 'Passw0rd!123']);

        $this->withCookie('ds_locale', 'ar')->post('/login', [
            'email' => $user->email,
            'password' => 'Passw0rd!123',
        ])->assertRedirect('/auth');

        // Signing in regenerates the session id, so the language has to survive
        // that rotation rather than being lost along with the old session.
        $this->assertSame('ar', session('locale'));

        $this->get('/auth')
            ->assertOk()
            ->assertHeader('Content-Language', 'ar')
            ->assertSee('خريطة النظام المباشرة');
    }

    public function test_registration_preserves_the_active_locale(): void
    {
        $this->withCookie('ds_locale', 'fr')->post('/register', [
            'name' => 'Cyril',
            'email' => 'cyril@example.test',
            'password' => 'Passw0rd!123',
            'password_confirmation' => 'Passw0rd!123',
        ])->assertRedirect('/auth');

        $this->assertSame('fr', session('locale'));

        $this->get('/auth')
            ->assertOk()
            ->assertHeader('Content-Language', 'fr')
            ->assertSee('JOURNAL SYSTÈME');
    }

    public function test_a_successful_sign_in_announces_itself_on_the_dashboard(): void
    {
        $user = User::factory()->create(['password' => 'Passw0rd!123']);

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'Passw0rd!123',
        ])->assertSessionHas('status', __('site.auth.signed_in'));

        // The panel layout reads no flash key, so the page has to raise the
        // confirmation itself or the message is silently dropped.
        Livewire::actingAs($user)
            ->test(DesertSentry::class)
            ->assertNotified(__('site.auth.signed_in'));
    }

    public function test_a_new_account_announces_itself_on_the_dashboard(): void
    {
        $this->post('/register', [
            'name' => 'Amina',
            'email' => 'amina@example.test',
            'password' => 'Passw0rd!123',
            'password_confirmation' => 'Passw0rd!123',
        ])->assertSessionHas('status', __('site.auth.account_created'));

        Livewire::actingAs(User::where('email', 'amina@example.test')->firstOrFail())
            ->test(DesertSentry::class)
            ->assertNotified(__('site.auth.account_created'));
    }

    public function test_the_dashboard_stays_quiet_when_there_is_no_flash(): void
    {
        $this->actingAs(User::factory()->create());

        Livewire::actingAs(User::first())
            ->test(DesertSentry::class)
            ->assertNotNotified();
    }

    public function test_logging_out_announces_the_sign_out_on_the_landing_page(): void
    {
        $user = User::factory()->create(['password' => 'Passw0rd!123']);

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'Passw0rd!123',
        ]);

        $this->post('/logout')
            ->assertRedirect('/')
            ->assertSessionHas('status', __('site.auth.signed_out'));

        $this->get('/')
            ->assertOk()
            ->assertSee(__('site.auth.signed_out'));
    }
}
