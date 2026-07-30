<?php

namespace App\Services\FrontEnd;

use App\Jobs\SendEmailVerificationEmail;
use App\Models\Conversation;
use App\Models\User;
use App\Services\ValidationService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Laravel\Socialite\Contracts\User as SocialiteUser;

/**
 * Web-guard (learner) authentication. HTTP-free: the controller passes in the
 * session's guest-hole ids and maps the returned {@see ValidationService}.
 */
class AuthService
{
    /**
     * Register a learner, claim any holes they started as a guest this session,
     * sign them in, and queue the verification email.
     *
     * @param  array<string, mixed>  $data
     * @param  array<int, int>  $guestHoleIds
     */
    public function register(array $data, array $guestHoleIds = []): ValidationService
    {
        $validation = new ValidationService;

        $user = User::create([
            'first_name' => $data['first_name'] ?? null,
            'last_name' => $data['last_name'] ?? null,
            'name' => $data['name'] ?? null,
            'email' => $data['email'],
            'password' => $data['password'],
            'login_method' => User::AUTH_LOGIN_METHOD,
        ]);

        $this->claimGuestHoles($user, $guestHoleIds);

        Auth::guard('web')->login($user);

        SendEmailVerificationEmail::dispatch($user->id);

        return $validation->addValidatedItems(['user' => $user]);
    }

    /**
     * @param  array<int, int>  $guestHoleIds
     */
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

        return $validation->addValidatedItems(['user' => $user]);
    }

    /**
     * Sign in (or register, first time) a learner via Google, then claim their
     * guest holes. Unlike the admin side, a Google account auto-creates a user —
     * and Google has already verified the address, so no verification mail.
     *
     * @param  array<int, int>  $guestHoleIds
     */
    public function handleGoogleLogin(SocialiteUser $googleUser, array $guestHoleIds = []): ValidationService
    {
        $validation = new ValidationService;

        $existing = User::where('email', $googleUser->getEmail())->first();

        if ($existing && ! $existing->enabled) {
            return $validation->errorEncountered(__('frontend.auth.account-blocked'));
        }

        [$firstName, $lastName] = $this->splitName($googleUser->getName() ?: '');

        $user = User::updateOrCreate(
            ['email' => $googleUser->getEmail()],
            [
                'first_name' => $existing?->first_name ?: $firstName,
                'last_name' => $existing?->last_name ?: $lastName,
                'name' => $existing?->name ?: ($googleUser->getName() ?: Str::before($googleUser->getEmail(), '@')),
                'login_method' => User::GOOGLE_LOGIN_METHOD,
                'profile_image' => $existing?->profile_image ?: $googleUser->getAvatar(),
            ],
        );

        if (! $user->hasVerifiedEmail()) {
            $user->forceFill(['email_verified_at' => now()])->save();
        }

        $this->claimGuestHoles($user, $guestHoleIds);

        Auth::guard('web')->login($user);

        return $validation->addValidatedItems(['user' => $user]);
    }

    /**
     * Re-parent the visitor's anonymous holes onto their account. Only unowned
     * holes the visitor actually started this session are claimed.
     *
     * @param  array<int, int>  $guestHoleIds
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

    /**
     * @return array{0: ?string, 1: ?string}
     */
    private function splitName(string $name): array
    {
        $parts = preg_split('/\s+/', trim($name), 2) ?: [];

        return [$parts[0] ?? null, $parts[1] ?? null];
    }
}
