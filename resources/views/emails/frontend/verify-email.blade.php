<x-mail::message>
# {{ __('frontend.mail.verify-heading', ['name' => $user->fullName()]) }}

{{ __('frontend.mail.verify-body') }}

<x-mail::button :url="$verificationUrl">
{{ __('frontend.mail.verify-cta') }}
</x-mail::button>

{{ __('frontend.mail.verify-expiry', ['minutes' => config('auth.verification.expire', 60)]) }}

{{ __('frontend.mail.verify-ignore') }}

{{ __('frontend.mail.sign-off') }}<br>
{{ config('app.name') }}
</x-mail::message>
