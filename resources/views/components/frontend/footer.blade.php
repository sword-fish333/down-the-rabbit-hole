{{-- Frontend footer. Quiet, wet-matte; the bold colour lives up in the hero. --}}
<footer class="relative z-10 border-t border-border/70 bg-surface/40">
    <div class="mx-auto flex max-w-7xl flex-col items-center justify-between gap-4 px-4 py-8 sm:flex-row sm:px-6 lg:px-8">
        <p class="flex items-center gap-2 text-sm text-foreground-muted">
            <span class="material-symbols-outlined text-[1.1rem] text-primary">arrow_downward</span>
            <span class="font-display font-medium text-foreground">{{ config('app.name') }}</span>
            <span class="text-foreground-muted/70">— {{ __('frontend.footer.tagline') }}</span>
        </p>
        <p class="text-xs text-foreground-muted/70">
            &copy; {{ date('Y') }} {{ config('app.name') }}. {{ __('frontend.footer.rights') }}
        </p>
    </div>
</footer>
