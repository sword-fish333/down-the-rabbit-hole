<?php

namespace App\Services\Admin;

use App\Models\Admin;
use App\Services\ValidationService;
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\Contracts\User as SocialiteUser;

/**
 * Encapsulates admin authentication business rules. Knows nothing about HTTP:
 * it returns a {@see ValidationService} result that the controller maps to a response.
 */
class AuthService
{
    public function attemptLogin(string $email, string $password, bool $remember): ValidationService
    {
        $validation = new ValidationService;

        $admin = Admin::where('email', $email)->first();

        if ($admin && !$admin->isEnabled()) {
            return $validation->errorEncountered(__('admin/backend.auth.account-blocked'));
        }

        if (!Auth::guard('admin')->attempt(compact('email', 'password'), $remember)) {
            return $validation->errorEncountered(__('admin/backend.auth.invalid-credentials'));
        }

        Auth::guard('admin')->user()->update(['login_method' => Admin::CREDENTIALS_LOGIN_METHOD]);

        return $validation->successfulCheck();
    }

    public function handleGoogleLogin(SocialiteUser $googleUser): ValidationService
    {
        $validation = new ValidationService;

        $admin = Admin::where('email', $googleUser->getEmail())->first();

        if (!$admin) {
            return $validation->errorEncountered(__('admin/backend.auth.no-account-available'));
        }

        if (!$admin->isEnabled()) {
            return $validation->errorEncountered(__('admin/backend.auth.account-blocked'));
        }

        $admin->update([
            'login_method' => Admin::GOOGLE_LOGIN_METHOD,
            'profile_image' => $admin->profile_image ?: $googleUser->getAvatar(),
        ]);

        Auth::guard('admin')->login($admin);

        return $validation->successfulCheck();
    }
}
