@props([
    'content' => '',
    'class' => '',
])

@php
    /**
     * Server-rendered Markdown for persisted turns. The live stream renders the
     * same shapes client-side (public/js/frontend/markdown.js) so a reloaded
     * transcript is pixel-identical to the one the learner watched arrive.
     *
     * `html_input: escape` is the load-bearing option: the guide's output is
     * model-generated text, so raw HTML in it must never become markup.
     */
    $html = Str::markdown($content, [
        'html_input' => 'escape',
        'allow_unsafe_links' => false,
    ]);

    // The checkpoint line is a UI element, not prose — see .dth-checkpoint-line.
    $html = preg_replace(
        '/<p>(\s*<strong>Checkpoint:?<\/strong>)/i',
        '<p class="dth-checkpoint-line">$1',
        $html,
    );

    // Wide content scrolls inside itself; the page body never scrolls sideways.
    $html = str_replace('<table>', '<div class="table-scroll"><table>', $html);
    $html = str_replace('</table>', '</table></div>', $html);
@endphp

<div {{ $attributes->class(['prose-dth', $class]) }}>{!! $html !!}</div>
