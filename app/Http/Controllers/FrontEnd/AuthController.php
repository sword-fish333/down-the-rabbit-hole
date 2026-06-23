<?php

namespace App\Http\Controllers\FrontEnd;

use App\Http\Controllers\Controller;
use App\Services\FrontEnd\AuthService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Laravel\Socialite\Facades\Socialite;

/**
 * Web-guard (learner) auth: register, login, logout. Thin — the rules live in
 * App\Services\FrontEnd\AuthService. Mirrors the admin AuthController shape.
 */
class AuthController extends Controller
{
    public function __construct(private readonly AuthService $authService) {}

    public function showRegister(): View|RedirectResponse
    {
        if (Auth::guard('web')->check()) {
            return redirect()->route('home');
        }

        return view('frontend.auth.register');
    }

    public function register(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email',
            'password' => 'required|string|min:8|confirmed',
        ]);

        $this->authService->register($validated, $this->guestHoleIds($request));

        $request->session()->forget('dth_holes');
        $request->session()->regenerate();

        return redirect()->intended(route('home'))->with('success', __('frontend.auth.welcome'));
    }

    public function showLogin(): View|RedirectResponse
    {
        if (Auth::guard('web')->check()) {
            return redirect()->route('home');
        }

        return view('frontend.auth.login');
    }

    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        $result = $this->authService->attemptLogin(
            $credentials['email'],
            $credentials['password'],
            $request->boolean('remember'),
            $this->guestHoleIds($request),
        );

        if (! $result->isSuccessfulCheck()) {
            return back()->with('error', $result->getFirstError())->withInput($request->except('password'));
        }

        $request->session()->forget('dth_holes');
        $request->session()->regenerate();

        return redirect()->intended(route('home'))->with('success', __('frontend.auth.welcome-back'));
    }

    public function oauthRedirect(Request $request): RedirectResponse
    {
        $request->validate(['driver' => 'required|in:google']);

        return Socialite::driver($request->input('driver'))
            ->redirectUrl(route('google.callback'))
            ->redirect();
    }

    public function googleCallback(Request $request): RedirectResponse
    {
        try {
            $googleUser = Socialite::driver('google')
                ->redirectUrl(route('google.callback'))
                ->user();
        } catch (\Throwable $e) {
            fullLog(__('frontend.auth.google-error', ['error' => $e->getMessage()]));

            return redirect()->route('login')->with('error', __('frontend.auth.google-failed'));
        }

        $result = $this->authService->handleGoogleLogin($googleUser, $this->guestHoleIds($request));

        if (! $result->isSuccessfulCheck()) {
            return redirect()->route('login')->with('error', $result->getFirstError());
        }

        $request->session()->forget('dth_holes');
        $request->session()->regenerate();

        return redirect()->intended(route('home'))->with('success', __('frontend.auth.welcome'));
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('home')->with('success', __('frontend.auth.signed-out'));
    }

    private function guestHoleIds(Request $request): array
    {
        return $request->session()->get('dth_holes', []);
    }
}
