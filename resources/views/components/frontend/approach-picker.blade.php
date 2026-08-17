@props(['selected' => \App\Models\Conversation::APPROACH_GUIDED])

{{-- How a layer arrives: taught, or asked.

     The single most consequential setting on the composer, so it is a pair of
     real radios in the descend form rather than a menu — both options are named,
     both are explained in a line, and neither is hidden behind a click. The
     product default is `guided`, because "you will be asked something you do not
     know yet" is the right offer to *accept*, never the right one to discover. --}}
<fieldset>
    <legend class="dth-coord mb-2.5">{{ __('frontend.approach.legend') }}</legend>

    <div class="grid gap-2 sm:grid-cols-2">
        @foreach (\App\Models\Conversation::APPROACHES as $index => $approach)
            @include('components.frontend.partials.approach-option', [
                'approach' => $approach,
                'selected' => $selected,
                'index' => $index,
            ])
        @endforeach
    </div>
</fieldset>
