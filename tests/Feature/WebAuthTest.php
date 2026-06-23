<?php

namespace Tests\Feature;

use App\Models\Conversation;
use App\Models\User;
use App\Services\FrontEnd\AuthService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;

class WebAuthTest extends TestCase
{
    use RefreshDatabase;

    private function guestHole(): Conversation
    {
        return Conversation::create([
            'subject' => 'Test subject',
            'current_depth' => 0,
            'status' => Conversation::STATUS_EXPLORING,
        ]);
    }

    public function test_registering_claims_only_the_visitors_guest_holes(): void
    {
        $mine = $this->guestHole();
        $notMine = $this->guestHole();

        app(AuthService::class)->register(
            ['name' => 'Bo', 'email' => 'bo@example.com', 'password' => 'password123'],
            [$mine->id],
        );

        $user = User::where('email', 'bo@example.com')->firstOrFail();
        $this->assertSame($user->id, $mine->fresh()->user_id);
        $this->assertNull($notMine->fresh()->user_id);
        $this->assertTrue(Auth::guard('web')->check());
    }

    public function test_login_claims_guest_holes(): void
    {
        $user = User::create(['name' => 'Mia', 'email' => 'mia@example.com', 'password' => 'password123']);
        $hole = $this->guestHole();

        $result = app(AuthService::class)->attemptLogin('mia@example.com', 'password123', false, [$hole->id]);

        $this->assertTrue($result->isSuccessfulCheck());
        $this->assertSame($user->id, $hole->fresh()->user_id);
    }

    public function test_login_rejects_bad_credentials(): void
    {
        User::create(['name' => 'Mia', 'email' => 'mia@example.com', 'password' => 'password123']);

        $result = app(AuthService::class)->attemptLogin('mia@example.com', 'wrong-password', false);

        $this->assertFalse($result->isSuccessfulCheck());
        $this->assertFalse(Auth::guard('web')->check());
    }

    public function test_a_disabled_account_cannot_log_in(): void
    {
        $user = User::create(['name' => 'X', 'email' => 'x@example.com', 'password' => 'password123']);
        $user->enabled = false;
        $user->save();

        $result = app(AuthService::class)->attemptLogin('x@example.com', 'password123', false);

        $this->assertFalse($result->isSuccessfulCheck());
        $this->assertFalse(Auth::guard('web')->check());
    }

    public function test_register_endpoint_creates_account_and_signs_in(): void
    {
        $response = $this->post(route('register.submit'), [
            'name' => 'Cy',
            'email' => 'cy@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertRedirect(route('home'));
        $this->assertAuthenticated();
        $this->assertDatabaseHas('users', ['email' => 'cy@example.com']);
    }

    public function test_google_login_creates_the_user_and_claims_their_hole(): void
    {
        $hole = $this->guestHole();

        $googleUser = \Mockery::mock(\Laravel\Socialite\Contracts\User::class);
        $googleUser->shouldReceive('getEmail')->andReturn('gee@example.com');
        $googleUser->shouldReceive('getName')->andReturn('Gee');
        $googleUser->shouldReceive('getAvatar')->andReturn('https://avatars.example/gee.png');

        $result = app(AuthService::class)->handleGoogleLogin($googleUser, [$hole->id]);

        $this->assertTrue($result->isSuccessfulCheck());

        $user = User::where('email', 'gee@example.com')->firstOrFail();
        $this->assertSame(User::GOOGLE_LOGIN_METHOD, $user->login_method);
        $this->assertSame('https://avatars.example/gee.png', $user->profile_image);
        $this->assertSame($user->id, $hole->fresh()->user_id);
        $this->assertTrue(Auth::guard('web')->check());
    }

    public function test_login_is_rate_limited_after_repeated_attempts(): void
    {
        $attempt = fn () => $this->post(route('login.submit'), [
            'email' => 'spam@example.com',
            'password' => 'wrong-password',
        ]);

        for ($i = 0; $i < 5; $i++) {
            $attempt();
        }

        $attempt()->assertStatus(429);
    }
}
