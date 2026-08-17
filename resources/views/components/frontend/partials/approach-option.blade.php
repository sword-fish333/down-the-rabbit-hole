{{-- One "how a layer opens" card. Same shape as a learning-mode card, so the two
     read as one set of settings: the whole card is the <label>, and the radio
     inside it keeps keyboard and screen-reader behaviour native. --}}
@php($icons = [
    \App\Models\Conversation::APPROACH_GUIDED => 'auto_stories',
    \App\Models\Conversation::APPROACH_QUESTION => 'psychology_alt',
])

<label data-option-card data-selected="{{ $approach === $selected ? 'true' : 'false' }}"
       style="--i: {{ $index }}"
       class="dth-stagger group/approach relative flex cursor-pointer items-start gap-2.5 rounded-xl border border-border bg-surface/40 p-3 transition duration-(--motion-feedback) ease-(--ease-snap) hover:border-primary/40 hover:bg-surface/70 has-[:focus-visible]:ring-2 has-[:focus-visible]:ring-ring data-[selected=true]:border-primary/60 data-[selected=true]:bg-primary/8">
    <input type="radio" name="approach" value="{{ $approach }}" @checked($approach === $selected) class="sr-only">

    <span class="material-symbols-outlined mt-px shrink-0 text-[1.2rem] text-primary" aria-hidden="true">{{ $icons[$approach] }}</span>

    <span class="min-w-0">
        <span class="block text-sm font-medium text-foreground">{{ __('frontend.approach.'.$approach) }}</span>
        <span class="mt-0.5 block text-xs leading-relaxed text-foreground-muted">{{ __('frontend.approach.'.$approach.'-hint') }}</span>
    </span>

    <span class="material-symbols-outlined is-filled absolute right-2.5 top-2.5 text-[1rem] text-primary opacity-0 transition-opacity duration-(--motion-instant) group-data-[selected=true]/approach:opacity-100" aria-hidden="true">check_circle</span>
</label>
