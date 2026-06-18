<x-mail::message>
# {{ __('admin/backend.mail.support-heading') }}

**{{ __('admin/frontend.profile.support-from') }}:** {{ $admin->name }} ({{ $admin->email }})
**{{ __('admin/frontend.profile.support-topic') }}:** {{ __('admin/frontend.profile.topics.'.$support['topic']) }}
**{{ __('admin/frontend.profile.support-title') }}:** {{ $support['title'] }}

---

{{ $support['message'] }}

<x-mail::button :url="config('app.url')">
{{ __('admin/frontend.general.open-dashboard') }}
</x-mail::button>

{{ __('admin/backend.mail.thanks') }}<br>
{{ config('app.name') }}
</x-mail::message>
