<?php

namespace App\Services\Chat;

use App\Models\Concept;
use App\Models\Conversation;
use App\Models\Source;
use Illuminate\Support\Collection;

/**
 * Builds the descent's prompts. The system prompt is FROZEN — no per-conversation
 * interpolation — so its cached prefix stays byte-stable across turns. The
 * subject, the learning mode and the per-turn directive travel in the message
 * list instead.
 *
 * This is the one place to tune how the guide teaches.
 */
class DescentPrompt
{
    /**
     * The frozen system prompt. Keep it free of dynamic values (no subject, no
     * date, no depth) or the prompt cache breaks.
     */
    public function system(): string
    {
        return <<<'PROMPT'
        You are the Guide for "Down the Rabbit Hole" — a deep-learning experience where a
        learner names a subject and descends through it one layer at a time, proving they
        understood each layer before the next, deeper one opens, until they surface an expert.

        The learner names their subject in the first message. Teach that subject and nothing else.

        HOW DEPTH WORKS
        - Layer 0 is the absolute foundation a curious beginner needs. Each deeper layer builds
          strictly on the ones before, moving toward genuine expert-level mastery.
        - Teach exactly ONE layer per turn. Never dump the whole subject. Go deep, not wide.

        LANGUAGE
        - Every word the learner reads goes in the language named by the `[LANGUAGE]` directive.
        - If the learner is plainly writing to you in a different language, follow the learner and
          keep following them — they are telling you which language they think in.
        - Keep established technical terms in the form the field actually uses, and gloss each one
          once, in the learner's language, the first time it appears.
        - Two things never translate: the literal marker `**Checkpoint:**`, and code.

        VOICE
        - Rigorous, vivid, and concise. No filler, no flattery, no "great question".
        - Favour intuition and concrete examples over jargon; define a term the first time you use it.
        - Write in Markdown: short paragraphs, `code` where it clarifies, tables and fenced code
          blocks where they genuinely help. Use LaTeX ($...$ inline, $$...$$ display) only for
          real mathematics.

        EVERY TEACHING TURN
        1. Teach the current layer clearly and memorably.
        2. Name the concepts you are teaching explicitly — the learner's mastery map is built
           from the words you use, so call things by a consistent name.
        3. End with a line that begins exactly `**Checkpoint:**` followed by ONE task that forces
           the learner to *demonstrate* understanding — explain the idea back in their own words,
           apply it to a new case, or predict an outcome. Never trivia, never a definition they
           could copy back. Nothing comes after the checkpoint line.
        PROMPT;
    }

    /**
     * Directive appended (as a user turn) to make the model teach the next layer.
     * Cheap models don't support mid-conversation system messages, so this rides
     * in the message list rather than the system block.
     */
    public function teachInstruction(Conversation $conversation, Collection $resurfacing): string
    {
        $depth = $conversation->current_depth;

        return $this->compose(
            $conversation,
            "[GUIDE DIRECTIVE] Teach layer {$depth} of this subject now, building on everything "
                .'already covered. Finish with the `**Checkpoint:**` line.',
            $resurfacing,
        );
    }

    /**
     * The same layer, posed instead of taught — the learner asked to be tested
     * before they are told anything.
     *
     * The hard part is the framing line: without it the checkpoint is
     * unanswerable ("prove you understand layer 3" — of what?), and one sentence
     * too many hands over the answer. Hence the explicit prohibitions: name the
     * territory, never the content.
     */
    public function questionInstruction(Conversation $conversation, Collection $resurfacing): string
    {
        $depth = $conversation->current_depth;

        return $this->compose(
            $conversation,
            "[GUIDE DIRECTIVE] Do NOT teach layer {$depth} yet — the learner has asked to be tested "
                ."before being taught.\n\n"
                .'Write at most two short sentences naming what this layer is about: the territory, '
                .'not the content. Enough that the checkpoint is unambiguous, and nothing that answers '
                ."it. Do not define the terms, do not give examples, do not explain the mechanism.\n\n"
                .'Then the `**Checkpoint:**` line exactly as usual: ONE task that forces them to '
                .'demonstrate this layer. Nothing comes after it. They may well not know this yet — '
                .'that is the point, so pose it such that a wrong answer is revealing rather than '
                .'merely wrong.',
            $resurfacing,
        );
    }

    /**
     * A contextual action the learner asked for mid-layer ("explain differently",
     * "give me an analogy", "challenge me"). Re-teaches the same layer; the
     * checkpoint requirement is unchanged so the state machine still holds.
     */
    public function reframeInstruction(Conversation $conversation, string $intent, Collection $resurfacing): string
    {
        $directives = [
            DescentService::REFRAME_DIFFERENT => 'Explain this same layer again from a completely different angle. '
                .'Do not repeat your previous framing — change the entry point, not the difficulty.',
            DescentService::REFRAME_ANALOGY => 'Explain this same layer through one extended, concrete analogy, '
                .'then say plainly where the analogy breaks down.',
            DescentService::REFRAME_CHALLENGE => 'Stay on this same layer but raise the difficulty: pose a harder, '
                .'more applied version of the checkpoint that an expert would find interesting.',
            DescentService::REFRAME_EVIDENCE => 'Show your evidence for this layer: name the specific results, '
                .'papers, books or standards it rests on, and state plainly which parts are '
                .'established fact, which are interpretation, and which are genuinely uncertain.',
        ];

        return $this->compose(
            $conversation,
            "[GUIDE DIRECTIVE] {$directives[$intent]} Finish with the `**Checkpoint:**` line as usual.",
            $resurfacing,
        );
    }

    /**
     * Everything every turn needs, around whichever directive this turn carries.
     *
     * One assembler rather than three: the language, the mode, the page being
     * studied and the concepts still unresolved are true of the *descent*, not of
     * one kind of turn — and a reframe that quietly dropped the source note was
     * a guide improvising about a page it could no longer see.
     */
    private function compose(Conversation $conversation, string $directive, Collection $resurfacing): string
    {
        $parts = [$this->languageNote($conversation), $directive];

        if ($mode = $conversation->learningMode) {
            $parts[] = "Teach it in {$mode->name} mode: {$mode->prompt_directive}";
        }

        // Re-sent every turn rather than left in the message history: the older
        // turns get compacted into a summary as a subject deepens, and the
        // material must not be what falls out of the window.
        if ($source = $conversation->sources->first()) {
            $parts[] = $this->sourceNote($source);
        }

        if ($resurfacing->isNotEmpty()) {
            $parts[] = $this->resurfacingNote($resurfacing);
        }

        return implode("\n\n", $parts);
    }

    /**
     * Names the language of this descent, every turn.
     *
     * It rides in the message list rather than the system block for two reasons:
     * the system prompt must stay byte-stable for the cache, and a directive the
     * model sees *last* is the one it obeys. Sent every turn because a model
     * drifts back to the language of its instructions — which are English —
     * somewhere around the third layer, and drifting mid-descent reads as a bug.
     */
    private function languageNote(Conversation $conversation): string
    {
        $language = $conversation->language();

        return "[LANGUAGE] Write this entire turn in {$language}. "
            ."If the learner's own messages are in another language, use theirs instead.";
    }

    /**
     * Fed back as the opening user turn once older messages have been folded
     * into a running summary, so the guide keeps continuity for free.
     */
    public function summaryPreamble(string $summary): string
    {
        return "[SESSION SO FAR] {$summary}";
    }

    /**
     * Ground the layer in a specific page. The honesty clause matters more than
     * it looks: a guide that quietly fills gaps from its own knowledge turns
     * "make me understand this article" back into "explain this topic", and the
     * learner has no way to tell which they got.
     */
    private function sourceNote(Source $source): string
    {
        $extract = $source->extract((int) config('platform.sources.prompt_words'));

        return 'The learner is studying ONE specific page, not the subject in general. Teach FROM it: '
            .'every layer must be grounded in what this page actually says, quoting it where a quote '
            .'earns its place. Where you go beyond the page, say so in one short clause. Where the page '
            ."is wrong or thin, say that too — it is material, not scripture.\n\n"
            ."[SOURCE] {$source->displayTitle()} — {$source->url}\n\"\"\"\n{$extract}\n\"\"\"";
    }

    /**
     * Ask the guide to weave an unresolved concept back in rather than teaching
     * it cold again — spaced review, driven by graded evidence.
     */
    private function resurfacingNote(Collection $resurfacing): string
    {
        $lines = $resurfacing->map(function (Concept $concept) {
            $note = $concept->note ? " (they believed: {$concept->note})" : '';

            return "- {$concept->name}, first met at layer {$concept->first_seen_depth}{$note}";
        })->implode("\n");

        return "The learner still has unresolved trouble with these concepts:\n{$lines}\n"
            .'Weave a brief, natural correction for them into this layer — do not devote the whole layer to it.';
    }
}
