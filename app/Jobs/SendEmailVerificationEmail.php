<?php

namespace App\Jobs;

use App\Mail\FrontEnd\VerifyEmailMail;
use App\Models\User;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;
use Throwable;

/**
 * Sends the "confirm your email" message after sign-up.
 *
 * Queued so a slow or unreachable SMTP host never blocks the moment that
 * matters most — the learner's first descent. Verification is not a gate: an
 * unverified learner can still learn, they just see a reminder banner.
 */
class SendEmailVerificationEmail implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    /**
     * The locale is captured here, in the request that dispatched the job — a
     * queue worker has no locale of its own and would send every learner the
     * English mail.
     */
    public function __construct(
        private readonly int $userId,
        private readonly string $locale = 'en',
    ) {}

    public function handle(): void
    {
        $user = User::find($this->userId);

        if (! $user || $user->hasVerifiedEmail()) {
            return;
        }

        try {
            Mail::to($user->email)->locale($this->locale)
                ->send(new VerifyEmailMail($user, $this->signedUrl($user)));
        } catch (Throwable $e) {
            fullLog($e);

            throw $e;
        }
    }

    /**
     * A signed, expiring link keyed to the address on file, so changing the
     * email address invalidates any link already in flight.
     */
    private function signedUrl(User $user): string
    {
        return URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes((int) config('auth.verification.expire', 60)),
            ['id' => $user->getKey(), 'hash' => sha1($user->getEmailForVerification())],
        );
    }
}
