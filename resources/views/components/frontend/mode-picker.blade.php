@props([
    'modes',
    'selected' => null,
    'collapsible' => true,
])

@php
    $selectedId = old('learning_mode_id', $selected?->id ?? $modes->firstWhere('is_default', true)?->id ?? $modes->first()?->id);
    $accents = [
        'wave' => 'text-primary',
        'sand' => 'text-accent',
        'sea' => 'text-success',
        'clay' => 'text-clay-500',
    ];
@endphp

@if ($modes->isNotEmpty())
    {{-- How the guide should teach. A native radio group: real <label>s wrapping
         real <input type="radio">, so arrow-key navigation, focus order and
         screen-reader semantics are the platform's rather than reimplemented.

         Collapsed behind a <details> on the landing page — picking a mode is a
         genuine choice, but it must not stand between a visitor and typing a
         subject. The default is preselected and already correct. --}}
    @if ($collapsible)
        <details class="group/modes w-full text-left">
            <summary class="flex cursor-pointer list-none items-center justify-between gap-3 rounded-xl px-1 py-2 text-sm text-foreground-muted transition duration-(--motion-feedback) hover:text-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring">
                <span class="flex items-center gap-2">
                    <span class="material-symbols-outlined text-[1.1rem] text-primary/80" aria-hidden="true">tune</span>
                    {{ __('frontend.home.mode-label') }}
                    <span class="font-medium text-foreground">{{ $modes->firstWhere('id', $selectedId)?->label('name') }}</span>
                </span>
                <span class="material-symbols-outlined text-[1.15rem] transition-transform duration-(--motion-state) group-open/modes:rotate-180" aria-hidden="true">expand_more</span>
            </summary>

            <fieldset class="mt-3">
                <legend class="sr-only">{{ __('frontend.home.mode-legend') }}</legend>
                <div class="grid gap-2 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach ($modes as $index => $mode)
                        @include('components.frontend.partials.mode-option', compact('mode', 'selectedId', 'accents', 'index'))
                    @endforeach
                </div>
            </fieldset>
        </details>
    @else
        <fieldset>
            <legend class="dth-coord mb-3">{{ __('frontend.home.mode-legend') }}</legend>
            <div class="grid gap-2 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($modes as $index => $mode)
                    @include('components.frontend.partials.mode-option', compact('mode', 'selectedId', 'accents', 'index'))
                @endforeach
            </div>
        </fieldset>
    @endif
@endif
