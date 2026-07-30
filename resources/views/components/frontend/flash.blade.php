@php
    $success = session('success');
    $error = session('error');
    $message = $success ?? $error;
@endphp

@if ($message)
    {{-- role/aria-live differ by severity: an error interrupts, a confirmation
         waits its turn. Errors are not auto-dismissed — the learner may need to
         read one twice. --}}
    <div data-flash @if ($success) data-autohide @endif
         role="{{ $success ? 'status' : 'alert' }}"
         aria-live="{{ $success ? 'polite' : 'assertive' }}"
         class="dth-stagger fixed right-4 top-20 z-[60] w-[calc(100%-2rem)] max-w-sm sm:right-6">
        <div @class([
            'dth-glass flex items-start gap-3 rounded-2xl border bg-surface/95 p-4 shadow-2xl backdrop-blur-xl',
            'border-success/35' => $success,
            'border-danger/35' => ! $success,
        ])>
            <span @class([
                'grid h-8 w-8 shrink-0 place-items-center rounded-full',
                'bg-success/15 text-success' => $success,
                'bg-danger/15 text-danger' => ! $success,
            ])>
                <span class="material-symbols-outlined is-filled text-[1.1rem]" aria-hidden="true">{{ $success ? 'check_circle' : 'error' }}</span>
            </span>
            <p class="flex-1 pt-1 text-sm font-medium text-foreground">{{ $message }}</p>
            <button type="button" data-dismiss
                    class="rounded-lg p-1 text-foreground-muted transition hover:text-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring"
                    aria-label="{{ __('frontend.general.dismiss') }}">
                <span class="material-symbols-outlined text-[1.1rem]" aria-hidden="true">close</span>
            </button>
        </div>
    </div>
@endif
