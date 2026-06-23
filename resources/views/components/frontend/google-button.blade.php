@props(['label'])

<a href="{{ route('oauth-login', ['driver' => 'google']) }}"
   class="flex w-full items-center justify-center gap-3 rounded-xl border border-border-strong bg-surface/60 px-4 py-3 text-sm font-semibold text-foreground backdrop-blur-md transition hover:border-primary/40 hover:bg-surface focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring">
    <img src="https://www.gstatic.com/firebasejs/ui/2.0.0/images/auth/google.svg" alt="" class="h-5 w-5" aria-hidden="true">
    {{ $label }}
</a>
