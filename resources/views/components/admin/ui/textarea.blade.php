@props([
    'name',
    'label' => null,
    'value' => null,
    'rows' => 4,
    'required' => false,
    'hint' => null,
    'placeholder' => '',
    'mono' => false,
])

<div>
    @if ($label)
        <label for="{{ $name }}" class="mb-1.5 block text-sm font-medium text-foreground">
            {{ $label }}@if ($required)<span class="text-danger" aria-hidden="true"> *</span>@endif
        </label>
    @endif

    <textarea id="{{ $name }}" name="{{ $name }}" rows="{{ $rows }}" @required($required)
              placeholder="{{ $placeholder }}"
              @if ($hint) aria-describedby="{{ $name }}-hint" @endif
              @if ($errors->has($name)) aria-invalid="true" @endif
              {{ $attributes->class([
                  'w-full rounded-xl border bg-surface px-4 py-2.5 text-sm text-foreground shadow-sm transition placeholder:text-muted-foreground focus:ring-2 focus:outline-none',
                  'font-mono text-[0.82rem] leading-relaxed' => $mono,
                  'border-danger/60 focus:border-danger focus:ring-danger/25' => $errors->has($name),
                  'border-border focus:border-primary focus:ring-primary/25' => ! $errors->has($name),
              ]) }}>{{ old($name, $value) }}</textarea>

    @if ($hint && ! $errors->has($name))
        <p id="{{ $name }}-hint" class="mt-1.5 text-xs leading-relaxed text-muted-foreground">{{ $hint }}</p>
    @endif

    @error($name)
        <p class="mt-1.5 text-xs text-danger">{{ $message }}</p>
    @enderror
</div>
