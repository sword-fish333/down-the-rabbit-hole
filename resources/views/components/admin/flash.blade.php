{{-- Native toast for server-side flash messages (auto-dismissed by public/js/admin/app.js). --}}
@php($message = session('success') ?? session('error'))

@if ($message)
    @php($success = (bool) session('success'))
    <div data-toast class="fixed top-4 right-4 z-[60] w-[calc(100%-2rem)] max-w-sm">
        <div @class([
            'flex items-start gap-3 rounded-2xl border p-4 shadow-lg',
            'border-emerald-200 bg-emerald-50 dark:border-emerald-500/30 dark:bg-emerald-500/10' => $success,
            'border-rose-200 bg-rose-50 dark:border-rose-500/30 dark:bg-rose-500/10' => ! $success,
        ])>
            <i @class([
                'fa-solid mt-0.5',
                'fa-circle-check text-emerald-500' => $success,
                'fa-circle-exclamation text-rose-500' => ! $success,
            ])></i>
            <p @class([
                'flex-1 text-sm font-medium',
                'text-emerald-800 dark:text-emerald-200' => $success,
                'text-rose-800 dark:text-rose-200' => ! $success,
            ])>{{ $message }}</p>
            <button data-toast-close class="text-zinc-400 transition hover:text-zinc-600 dark:hover:text-zinc-200"
                    aria-label="{{ __('admin/frontend.general.dismiss') }}">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>
    </div>
@endif
