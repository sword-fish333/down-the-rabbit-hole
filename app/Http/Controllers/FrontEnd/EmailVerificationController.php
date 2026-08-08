<?php

namespace App\Http\Controllers\FrontEnd;

use App\Http\Controllers\Controller;
use App\Jobs\SendEmailVerificationEmail;
use App\Models\User;
use Illuminate\Auth\Events\Verified;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Email confirmation. Verification is a nudge, not a gate — an unverified
 * learner can still descend; they just carry a reminder until they confirm.
 */
class EmailVerificationController extends Controller
{
    public function notice(): View|RedirectResponse
    {
        if (auth()->user()->hasVerifiedEmail()) {
            return redirect()->route('subjects.index');
        }

        return view('frontend.auth.verify-email');
    }

    /**
     * The signed link from the email. The hash is of the address the link was
     * issued for, so changing the address invalidates any link in flight.
     */
    public function verify(Request $request, int $id, string $hash): RedirectResponse
    {
        $user = User::findOrFail($id);

        if (! hash_equals($hash, sha1($user->getEmailForVerification()))) {
            return redirect()->route('home')->with('error', __('frontend.auth.verify-invalid'));
        }

        if ($user->hasVerifiedEmail()) {
            return redirect()->route('subjects.index')->with('success', __('frontend.auth.verify-already'));
        }

        $user->markEmailAsVerified();
        event(new Verified($user));

        return redirect()->route('subjects.index')->with('success', __('frontend.auth.verify-done'));
    }

    public function resend(Request $request): RedirectResponse
    {
        $user = $request->user();

        if ($user->hasVerifiedEmail()) {
            return back()->with('success', __('frontend.auth.verify-already'));
        }

        SendEmailVerificationEmail::dispatch($user->id, app()->getLocale());

        return back()->with('success', __('frontend.auth.verify-sent'));
    }
}
