@props([
    'concept',
    'currentDepth' => 0,
])

@php
    use App\Models\Concept;

    $icons = [
        Concept::STATE_MASTERED => 'verified',
        Concept::STATE_DEVELOPING => 'trending_up',
        Concept::STATE_MISUNDERSTOOD => 'priority_high',
        Concept::STATE_UNEXPLORED => 'radio_button_unchecked',
    ];
    // Knowledge echo: a concept first met on an earlier layer, resurfacing here.
    $echo = $concept->isMisunderstood() && $concept->resurfacedAt($currentDepth);
@endphp

<span class="dth-concept inline-flex max-w-full items-center gap-1.5 rounded-full border px-2.5 py-1 text-xs"
      data-state="{{ $concept->state }}">
    <span class="material-symbols-outlined text-[0.9rem]" aria-hidden="true">{{ $icons[$concept->state] }}</span>
    <span class="truncate">{{ $concept->name }}</span>
    <span class="sr-only">— {{ __('frontend.chat.concept-state.'.$concept->state) }}</span>

    @if ($echo)
        <span class="dth-echo ml-0.5 pl-1.5 font-mono text-[0.65rem]"
              title="{{ __('frontend.chat.resurfaced', ['depth' => $concept->first_seen_depth]) }}">
            {{ __('frontend.chat.resurfaced-short', ['depth' => $concept->first_seen_depth]) }}
        </span>
    @endif
</span>
