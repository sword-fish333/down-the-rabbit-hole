<?php

namespace App\Services\FrontEnd;

use App\Models\Conversation;
use App\Models\User;
use App\Services\ValidationService;
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\Contracts\User as SocialiteUser;

/**
 * Web-guard (learner) authentication. HTTP-free: the controller passes in the
 * session's guest-hole ids and maps the returned {@see ValidationService}.
 */
class AuthService
{
    /**
     * Register a learner, claim any holes they started as a guest this session,
     * and sign them in.
     */
    public function register(array $data, array $guestHoleIds = []): ValidationService
    {
        $validation = new ValidationService;

        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => $data['password'],
            'login_method' => User::AUTH_LOGIN_METHOD,
        ]);

        $this->claimGuestHoles($user, $guestHoleIds);

        Auth::guard('web')->login($user);

        return $validation->successfulCheck();
    }

    public function attemptLogin(string $email, string $password, bool $remember, array $guestHoleIds = []): ValidationService
    {
        $validation = new ValidationService;

        $user = User::where('email', $email)->first();

        if ($user && ! $user->enabled) {
            return $validation->errorEncountered(__('frontend.auth.account-blocked'));
        }

        if (! Auth::guard('web')->attempt(compact('email', 'password'), $remember)) {
            return $validation->errorEncountered(__('frontend.auth.invalid-credentials'));
        }

        $user = Auth::guard('web')->user();
        $user->update(['login_method' => User::AUTH_LOGIN_METHOD]);

        $this->claimGuestHoles($user, $guestHoleIds);

        return $validation->successfulCheck();
    }

    /**
     * Sign in (or register, first time) a learner via Google, then claim their
     * guest holes. Unlike the admin side, a Google account auto-creates a user.
     */
    public function handleGoogleLogin(SocialiteUser $googleUser, array $guestHoleIds = []): ValidationService
    {
        $validation = new ValidationService;

        $existing = User::where('email', $googleUser->getEmail())->first();

        if ($existing && ! $existing->enabled) {
            return $validation->errorEncountered(__('frontend.auth.account-blocked'));
        }

        $user = User::updateOrCreate(
            ['email' => $googleUser->getEmail()],
            [
                'name' => $existing?->name ?: ($googleUser->getName() ?: strtok($googleUser->getEmail(), '@')),
                'login_method' => User::GOOGLE_LOGIN_METHOD,
                'profile_image' => $existing?->profile_image ?: $googleUser->getAvatar(),
            ],
        );

        $this->claimGuestHoles($user, $guestHoleIds);

        Auth::guard('web')->login($user);

        return $validation->successfulCheck();
    }

    /**
     * Re-parent the visitor's anonymous holes onto their account. Only unowned
     * holes the visitor actually started this session are claimed.
     */
    private function claimGuestHoles(User $user, array $guestHoleIds): void
    {
        if (! $guestHoleIds) {
            return;
        }

        Conversation::whereIn('id', $guestHoleIds)
            ->whereNull('user_id')
            ->update(['user_id' => $user->id]);
    }
}
