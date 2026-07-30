<?php

namespace App\Services\Llm;

use App\DTOs\Llm\ContextSummaryRequest;
use App\DTOs\Llm\GradingRequest;

/**
 * The user-turn prompts for the two structured (non-streaming) calls. Kept next
 * to the clients because they are provider-agnostic wire text, not pedagogy —
 * how the guide *teaches* lives in App\Services\Chat\DescentPrompt.
 */
class GradingPrompt
{
    public static function checkpoint(GradingRequest $request): string
    {
        $confidence = $request->selfRating === null
            ? 'The learner did not rate their own confidence.'
            : "Before answering, the learner rated their own confidence at {$request->selfRating}/100. "
                .'Do not let that rating influence the verdict — it is recorded only so they can see how well calibrated they are.';

        return <<<PROMPT
        Subject: {$request->subject}
        Layer: {$request->depth}

        The checkpoint that was posed:
        \"\"\"
        {$request->checkpoint}
        \"\"\"

        The learner's answer:
        \"\"\"
        {$request->answer}
        \"\"\"

        {$confidence}

        Grade the answer. Pass them when they demonstrably grasped the core idea — wording
        needn't match yours and minor gaps are fine. Hold them back only on a real
        misunderstanding, and separate the two failure modes carefully: a concept that is
        simply *absent* belongs in missing_concepts, while a belief that is actively *wrong*
        belongs in misconceptions. Write the feedback to the learner, in second person,
        never punitive.
        PROMPT;
    }

    public static function summarySystem(): string
    {
        return 'You compress a learning transcript into a short briefing that lets a tutor '
            .'resume teaching without re-reading it. Keep what was taught and what the learner '
            .'proved; drop pleasantries, restatements and formatting.';
    }

    public static function summary(ContextSummaryRequest $request): string
    {
        $transcript = collect($request->messages)
            ->map(fn (array $message) => strtoupper($message['role']).': '.$message['content'])
            ->implode("\n\n");

        $previous = $request->previousSummary
            ? "Summary of everything before this excerpt:\n\"\"\"\n{$request->previousSummary}\n\"\"\"\n\n"
            : '';

        return <<<PROMPT
        Subject: {$request->subject}
        Layers covered so far: 0 to {$request->depth}

        {$previous}Transcript excerpt to fold in:
        \"\"\"
        {$transcript}
        \"\"\"

        Produce one combined summary covering everything above, and list the concepts already covered.
        PROMPT;
    }
}
