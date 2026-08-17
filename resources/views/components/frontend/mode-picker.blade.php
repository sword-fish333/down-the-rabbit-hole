@props([
    'modes',
    'selected' => null,
])

@php
    $selectedId = $selected?->id;
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

         Which mode is selected is resolved by the caller, so the summary that
         names it and the card that shows it checked can never disagree. --}}
    <fieldset>
        <legend class="dth-coord mb-2.5">{{ __('frontend.home.mode-legend') }}</legend>

        <div class="grid gap-2 sm:grid-cols-2 lg:grid-cols-3">
            @foreach ($modes as $index => $mode)
                @include('components.frontend.partials.mode-option', compact('mode', 'selectedId', 'accents', 'index'))
            @endforeach
        </div>
    </fieldset>
@endif
