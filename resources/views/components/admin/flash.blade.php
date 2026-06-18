{{-- Native toast for server-side flash messages (auto-dismissed by public/js/admin/app.js). --}}
@php($message = session('success') ?? session('error'))

@if ($message)
    @php($success = (bool) session('success'))
    <div data-toast role="{{ $success ? 'status' : 'alert' }}" aria-live="{{ $success ? 'polite' : 'assertive' }}"
         class="fixed top-4 right-4 z-[60] w-[calc(100%-2rem)] max-w-sm">
        <div @class([
            'flex items-start gap-3 rounded-2xl border bg-surface p-4 shadow-xl shadow-ink-950/10',
            'border-success/30' => $success,
            'border-danger/30' => ! $success,
        ])>
            <span @class([
                'grid h-8 w-8 shrink-0 place-items-center rounded-full',
                'bg-success/15 text-success' => $success,
                'bg-danger/15 text-danger' => ! $success,
            ])>
                <x-admin.icon :name="$success ? 'check_circle' : 'error'" filled class="text-lg" />
            </span>
            <p class="flex-1 pt-1 text-sm font-medium text-foreground">{{ $message }}</p>
            <button data-toast-close class="text-muted-foreground transition hover:text-foreground"
                    aria-label="{{ __('admin/frontend.general.dismiss') }}">
                <x-admin.icon name="close" class="text-lg" />
            </button>
        </div>
    </div>
@endif
