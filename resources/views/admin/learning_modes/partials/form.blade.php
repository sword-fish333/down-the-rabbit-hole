@php
    $accentLabels = collect(App\Models\LearningMode::ACCENTS)
        ->mapWithKeys(fn (string $accent) => [$accent => __('admin/frontend.learning-modes.accents.'.$accent)])
        ->all();
@endphp

<div class="grid gap-6 lg:grid-cols-3">
    {{-- Identity + the directive: the two things that actually matter. --}}
    <div class="lg:col-span-2 space-y-6">
        <x-admin.ui.card :title="__('admin/frontend.learning-modes.section-identity')">
            <div class="space-y-5">
                <div class="grid gap-5 sm:grid-cols-2">
                    <x-admin.ui.input name="name" :label="__('admin/frontend.learning-modes.name')"
                                      :value="$mode->name" required />
                    <x-admin.ui.input name="slug" :label="__('admin/frontend.learning-modes.slug')"
                                      :value="$mode->slug" />
                </div>

                <x-admin.ui.input name="tagline" :label="__('admin/frontend.learning-modes.tagline')"
                                  :value="$mode->tagline" />

                <x-admin.ui.textarea name="description" :label="__('admin/frontend.learning-modes.description')"
                                     :value="$mode->description" :rows="3"
                                     :hint="__('admin/frontend.learning-modes.description-hint')" />
            </div>
        </x-admin.ui.card>

        <x-admin.ui.card :title="__('admin/frontend.learning-modes.section-behaviour')"
                         :subtitle="__('admin/frontend.learning-modes.section-behaviour-hint')">
            <x-admin.ui.textarea name="prompt_directive" :label="__('admin/frontend.learning-modes.directive')"
                                 :value="$mode->prompt_directive" :rows="8" mono required
                                 :hint="__('admin/frontend.learning-modes.directive-hint')" />
        </x-admin.ui.card>
    </div>

    {{-- Presentation + availability --}}
    <div class="space-y-6">
        <x-admin.ui.card :title="__('admin/frontend.learning-modes.section-appearance')">
            <div class="space-y-5">
                <x-admin.ui.input name="icon" :label="__('admin/frontend.learning-modes.icon')"
                                  :value="$mode->icon ?: 'school'" required
                                  placeholder="school" />
                <p class="-mt-3 text-xs text-muted-foreground">
                    {!! __('admin/frontend.learning-modes.icon-hint') !!}
                </p>

                <x-admin.ui.select name="accent" :label="__('admin/frontend.learning-modes.accent')"
                                   :value="$mode->accent ?: 'wave'" :options="$accentLabels" required
                                   :hint="__('admin/frontend.learning-modes.accent-hint')" />

                <x-admin.ui.input name="position" type="number" :label="__('admin/frontend.learning-modes.position')"
                                  :value="$mode->position ?? 0"
                                  :hint="__('admin/frontend.learning-modes.position-hint')" />
            </div>
        </x-admin.ui.card>

        <x-admin.ui.card :title="__('admin/frontend.learning-modes.section-availability')">
            <div class="space-y-3">
                <x-admin.ui.toggle name="enabled" :label="__('admin/frontend.learning-modes.enabled')"
                                   :checked="$mode->exists ? $mode->enabled : true"
                                   :hint="__('admin/frontend.learning-modes.enabled-hint')" />

                <x-admin.ui.toggle name="is_default" :label="__('admin/frontend.learning-modes.is-default')"
                                   :checked="$mode->is_default"
                                   :hint="__('admin/frontend.learning-modes.is-default-hint')" />
            </div>
        </x-admin.ui.card>
    </div>
</div>

<div class="mt-6 flex flex-wrap items-center gap-3">
    <x-admin.ui.button type="submit">
        <x-admin.icon name="save" class="text-lg" />
        {{ $mode->exists ? __('admin/frontend.learning-modes.save') : __('admin/frontend.learning-modes.create') }}
    </x-admin.ui.button>

    <a href="{{ route('admin.learning-mode.index') }}"
       class="inline-flex items-center gap-2 rounded-xl px-4 py-2.5 text-sm font-medium text-muted-foreground transition hover:text-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary/40">
        {{ __('admin/frontend.general.cancel') }}
    </a>
</div>
