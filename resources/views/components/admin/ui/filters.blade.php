@props([
    'action',
    'search' => true,
    'placeholder' => null,
])

{{-- A plain GET form: filters live in the URL, so a filtered view is
     shareable, bookmarkable, and survives the back button. No JS involved. --}}
<form method="GET" action="{{ $action }}" class="mb-4 flex flex-col gap-3 sm:flex-row sm:items-end">
    @if ($search)
        <div class="min-w-0 flex-1">
            <label for="search" class="sr-only">{{ __('admin/frontend.general.search') }}</label>
            <div class="relative">
                <x-admin.icon name="search" class="pointer-events-none absolute top-1/2 left-3.5 -translate-y-1/2 text-xl text-muted-foreground" />
                <input id="search" name="search" type="search" value="{{ request('search') }}"
                       placeholder="{{ $placeholder ?? __('admin/frontend.general.search') }}"
                       class="w-full rounded-xl border border-border bg-surface py-2.5 pl-11 pr-4 text-sm text-foreground shadow-sm transition placeholder:text-muted-foreground focus:border-primary focus:ring-2 focus:ring-primary/25 focus:outline-none">
            </div>
        </div>
    @endif

    {{ $slot }}

    <div class="flex items-center gap-2">
        <x-admin.ui.button type="submit">
            <x-admin.icon name="filter_alt" class="text-lg" />
            {{ __('admin/frontend.general.apply') }}
        </x-admin.ui.button>

        @if (request()->hasAny(['search', 'status', 'mode']))
            <a href="{{ $action }}"
               class="inline-flex items-center gap-1.5 rounded-xl px-3 py-2.5 text-sm font-medium text-muted-foreground transition hover:text-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary/40">
                {{ __('admin/frontend.general.clear') }}
            </a>
        @endif
    </div>
</form>
