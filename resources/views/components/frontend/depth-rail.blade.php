@props([
    'conversation',
    'concepts' => null,
    'mastery' => [],
])

@php
    use App\Models\Concept;

    $maxDepth = (int) config('platform.chat.max_depth');
    $depth = $conversation->current_depth;
    $concepts = $concepts ?? collect();

    // A layer stays flagged for review while any concept first met there is
    // still misunderstood — that is what makes this a map rather than a bar.
    $reviewDepths = $concepts
        ->where('state', Concept::STATE_MISUNDERSTOOD)
        ->pluck('first_seen_depth')
        ->unique();
@endphp

{{-- The descent, as a map: depth, cleared layers, the current layer, what is
     still locked, and which layers hold something to review. Read vertically
     because that is the direction of travel. Only state CHANGES animate. --}}
<div class="dth-peripheral" aria-labelledby="dth-rail-heading">
    <h2 id="dth-rail-heading" class="dth-coord mb-3">{{ __('frontend.chat.depth-rail') }}</h2>

    <ol id="dth-rail" class="relative space-y-1.5">
        @for ($layer = 0; $layer < $maxDepth; $layer++)
            @php
                $state = $layer < $depth ? 'cleared' : ($layer === $depth ? 'current' : 'locked');
                if ($reviewDepths->contains($layer) && $state === 'cleared') {
                    $state = 'review';
                }
                $labels = [
                    'cleared' => __('frontend.chat.layer-state.cleared'),
                    'current' => __('frontend.chat.layer-state.current'),
                    'review' => __('frontend.chat.layer-state.review'),
                    'locked' => __('frontend.chat.layer-state.locked'),
                ];
            @endphp

            <li class="dth-rail-node" data-state="{{ $state }}"
                data-review="{{ $reviewDepths->contains($layer) ? '1' : '0' }}"
                @if ($state === 'current') aria-current="step" @endif>
                <span class="flex items-center gap-2.5">
                    <span class="dth-rail-mark w-6 shrink-0" aria-hidden="true"></span>
                    <span class="dth-coord">{{ str_pad($layer, 2, '0', STR_PAD_LEFT) }}</span>
                    {{-- Never colour alone: each state also carries an icon and a label. --}}
                    <span @class([
                        'material-symbols-outlined text-[0.95rem]',
                        'text-success' => $state === 'cleared',
                        'text-primary' => $state === 'current',
                        'text-accent' => $state === 'review',
                        'text-foreground-muted/50' => $state === 'locked',
                    ]) aria-hidden="true">{{ ['cleared' => 'check', 'current' => 'my_location', 'review' => 'history', 'locked' => 'lock'][$state] }}</span>
                    <span class="sr-only">{{ __('frontend.chat.layer') }} {{ $layer }} — {{ $labels[$state] }}</span>
                </span>
            </li>
        @endfor
    </ol>

    {{-- Mastery tally. Four states, each with its own icon + word, so the map is
         legible without relying on hue. --}}
    @if (array_sum($mastery) > 0)
        <h2 class="dth-coord mb-2 mt-7">{{ __('frontend.chat.mastery') }}</h2>
        <dl class="space-y-1.5 text-xs">
            @foreach ([
                Concept::STATE_MASTERED => ['verified', 'text-success'],
                Concept::STATE_DEVELOPING => ['trending_up', 'text-primary'],
                Concept::STATE_MISUNDERSTOOD => ['priority_high', 'text-accent'],
                Concept::STATE_UNEXPLORED => ['radio_button_unchecked', 'text-foreground-muted/60'],
            ] as $state => [$icon, $tone])
                @continue(($mastery[$state] ?? 0) === 0)
                <div class="flex items-center justify-between gap-3">
                    <dt class="flex items-center gap-1.5 text-foreground-muted">
                        <span class="material-symbols-outlined text-[0.95rem] {{ $tone }}" aria-hidden="true">{{ $icon }}</span>
                        {{ __('frontend.chat.concept-state.'.$state) }}
                    </dt>
                    <dd class="font-mono tabular-nums text-foreground">{{ $mastery[$state] }}</dd>
                </div>
            @endforeach
        </dl>
    @endif
</div>
