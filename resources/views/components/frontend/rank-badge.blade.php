@props(['rank'])

@php
    // Top three are lit; everyone else is a coordinate. The tone is never the
    // only signal — the numeral is always there, and the podium ranks carry an
    // icon as well, so the order reads with any colour-vision profile and in
    // print.
    $podium = [
        1 => ['tone' => 'border-accent/50 bg-accent/12 text-accent', 'icon' => 'military_tech'],
        2 => ['tone' => 'border-primary/45 bg-primary/10 text-primary', 'icon' => 'workspace_premium'],
        3 => ['tone' => 'border-border-strong bg-surface-muted/60 text-foreground', 'icon' => 'workspace_premium'],
    ][$rank] ?? null;
@endphp

<span {{ $attributes->class([
    'inline-flex shrink-0 items-center gap-1 rounded-full border px-2 py-0.5 font-mono text-xs tabular-nums',
    $podium['tone'] ?? 'border-transparent text-foreground-muted',
]) }}>
    @if ($podium)
        <span class="material-symbols-outlined is-filled text-[0.9rem]" aria-hidden="true">{{ $podium['icon'] }}</span>
    @endif
    {{ str_pad((string) $rank, 2, '0', STR_PAD_LEFT) }}
</span>
