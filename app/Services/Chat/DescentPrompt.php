<?php

namespace App\Services\Chat;

use App\Models\Concept;
use App\Models\Conversation;
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

        $parts = ["[GUIDE DIRECTIVE] Teach layer {$depth} of this subject now, building on everything "
            .'already covered. Finish with the `**Checkpoint:**` line.'];

        if ($mode = $conversation->learningMode) {
            $parts[] = "Teach it in {$mode->name} mode: {$mode->prompt_directive}";
        }

        if ($resurfacing->isNotEmpty()) {
            $parts[] = $this->resurfacingNote($resurfacing);
        }

        return implode("\n\n", $parts);
    }

    /**
     * A contextual action the learner asked for mid-layer ("explain differently",
     * "give me an analogy", "challenge me"). Re-teaches the same layer; the
     * checkpoint requirement is unchanged so the state machine still holds.
     */
    public function reframeInstruction(Conversation $conversation, string $intent): string
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

        return "[GUIDE DIRECTIVE] {$directives[$intent]} "
            .'Finish with the `**Checkpoint:**` line as usual.';
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
