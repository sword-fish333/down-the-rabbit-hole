@props([
    'name',
    'label',
    'checked' => false,
    'hint' => null,
])

{{-- A real checkbox with a styled surface, so keyboard, form submission and
     screen-reader semantics come from the platform. The hidden zero-value input
     means an unchecked box still posts a value. --}}
<label class="flex cursor-pointer items-start gap-3 rounded-xl border border-border bg-surface p-3.5 transition has-[:checked]:border-primary/50 has-[:checked]:bg-primary/6 has-[:focus-visible]:ring-2 has-[:focus-visible]:ring-primary/40">
    <input type="hidden" name="{{ $name }}" value="0">
    <input type="checkbox" name="{{ $name }}" value="1" @checked(old($name, $checked))
           class="mt-0.5 h-4 w-4 shrink-0 rounded border-border text-primary focus:ring-2 focus:ring-primary/30">
    <span class="min-w-0">
        <span class="block text-sm font-medium text-foreground">{{ $label }}</span>
        @if ($hint)
            <span class="mt-0.5 block text-xs leading-relaxed text-muted-foreground">{{ $hint }}</span>
        @endif
    </span>
</label>
