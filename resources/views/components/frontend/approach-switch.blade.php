@props(['conversation'])

@php($icons = [
    \App\Models\Conversation::APPROACH_GUIDED => 'auto_stories',
    \App\Models\Conversation::APPROACH_QUESTION => 'psychology_alt',
])

{{-- Change how the NEXT layer opens, from inside the descent.

     Two submit buttons sharing one name — no radio, no confirm step, no
     JavaScript: pressing the option you want IS the change. It sits under the
     one control on offer rather than beside it, because it modifies that action
     rather than competing with it, and it only ever appears between layers,
     where a settings decision costs nothing. --}}
<form method="POST" action="{{ route('subject.approach', $conversation) }}"
      {{ $attributes->class('flex flex-col items-center gap-2') }}>
    @csrf
    @method('PATCH')

    <p id="dth-approach-legend" class="dth-coord">{{ __('frontend.approach.next-label') }}</p>

    {{-- Stacked on a phone, segmented from `sm`: two full labels side by side do
         not fit at 320px, and neither option can lose its words — "which one am
         I on" has to be readable, not inferred from an icon. --}}
    <div role="group" aria-labelledby="dth-approach-legend"
         class="flex w-full flex-col gap-0.5 rounded-xl border border-border/70 bg-surface/40 p-0.5 sm:w-auto sm:flex-row sm:items-center">
        @foreach (\App\Models\Conversation::APPROACHES as $approach)
            @php($current = $conversation->approach === $approach)

            <button type="submit" name="approach" value="{{ $approach }}"
                    aria-pressed="{{ $current ? 'true' : 'false' }}"
                    title="{{ __('frontend.approach.'.$approach.'-hint') }}"
                    @class([
                        'inline-flex items-center justify-center gap-1.5 rounded-[0.6rem] px-3 py-1.5 text-xs font-medium transition duration-(--motion-feedback) ease-(--ease-snap) focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring',
                        'bg-primary/12 text-primary' => $current,
                        'text-foreground-muted hover:text-foreground' => ! $current,
                    ])>
                <span class="material-symbols-outlined text-[1rem]" aria-hidden="true">{{ $icons[$approach] }}</span>
                {{ __('frontend.approach.'.$approach) }}
            </button>
        @endforeach
    </div>
</form>
