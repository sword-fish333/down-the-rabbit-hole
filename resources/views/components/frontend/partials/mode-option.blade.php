{{-- One learning-mode card. The whole card is the <label>, so the hit target is
     the card and not just the (visually hidden) radio inside it. --}}
<label data-option-card data-selected="{{ $mode->id === $selectedId ? 'true' : 'false' }}"
       style="--i: {{ $index }}"
       class="dth-stagger group/mode relative flex cursor-pointer items-start gap-2.5 rounded-xl border border-border bg-surface/40 p-3 transition duration-(--motion-feedback) ease-(--ease-snap) hover:border-primary/40 hover:bg-surface/70 has-[:focus-visible]:ring-2 has-[:focus-visible]:ring-ring data-[selected=true]:border-primary/60 data-[selected=true]:bg-primary/8">
    <input type="radio" name="learning_mode_id" value="{{ $mode->id }}"
           @checked($mode->id === $selectedId)
           class="sr-only">

    <span class="material-symbols-outlined mt-px shrink-0 text-[1.2rem] {{ $accents[$mode->accent] ?? 'text-primary' }}" aria-hidden="true">{{ $mode->icon }}</span>

    <span class="min-w-0">
        <span class="block text-sm font-medium text-foreground">{{ $mode->label('name') }}</span>
        @if ($mode->tagline)
            <span class="mt-0.5 block text-xs leading-relaxed text-foreground-muted">{{ $mode->label('tagline') }}</span>
        @endif
    </span>

    {{-- Selected state is announced by the radio itself; this is the visual half. --}}
    <span class="material-symbols-outlined is-filled absolute right-2.5 top-2.5 text-[1rem] text-primary opacity-0 transition-opacity duration-(--motion-instant) group-data-[selected=true]/mode:opacity-100" aria-hidden="true">check_circle</span>
</label>
