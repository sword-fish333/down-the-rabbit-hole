<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\Admin\AuthService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Laravel\Socialite\Facades\Socialite;

class AuthController extends Controller
{
    public function __construct(private readonly AuthService $authService) {}

    public function login(): View|RedirectResponse
    {
        if (Auth::guard('admin')->check()) {
            return redirect()->route('admin.dashboard');
        }

        return view('admin.auth.login');
    }

    public function submitLogin(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        $result = $this->authService->attemptLogin(
            $credentials['email'],
            $credentials['password'],
            $request->boolean('remember_me'),
        );

        if (!$result->isSuccessfulCheck()) {
            return back()->with('error', $result->getFirstError())->withInput($request->except('password'));
        }

        $request->session()->regenerate();

        return redirect()->intended(route('admin.dashboard'))
            ->with('success', __('admin/backend.auth.login-successful'));
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::guard('admin')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('admin.login')->with('success', __('admin/backend.auth.logout-successful'));
    }

    public function oauthRedirect(Request $request): RedirectResponse
    {
        $request->validate([
            'driver' => 'required|in:google,github,facebook',
        ]);

        return Socialite::driver($request->input('driver'))
            ->redirectUrl(route('admin.google.callback'))
            ->redirect();
    }

    public function googleLogin(): RedirectResponse
    {
        try {
            $googleUser = Socialite::driver('google')
                ->redirectUrl(route('admin.google.callback'))
                ->user();
        } catch (\Throwable $e) {
            fullLog(__('admin/backend.auth.google-socialite-error', ['error' => $e->getMessage()]));

            return redirect()->route('admin.login')->with('error', __('admin/backend.auth.google-auth-failed'));
        }

        $result = $this->authService->handleGoogleLogin($googleUser);

        if (!$result->isSuccessfulCheck()) {
            return redirect()->route('admin.login')->with('error', $result->getFirstError());
        }

        return redirect()->intended(route('admin.dashboard'))
            ->with('success', __('admin/backend.auth.login-successful'));
    }
}
