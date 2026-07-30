<?php

namespace App\Services\Llm;

use App\Models\CheckpointAttempt;

/**
 * The JSON Schemas the providers enforce for the non-streaming calls.
 *
 * Declared once and shared by every client: Anthropic sends it as
 * `output_config.format`, Gemini as `generationConfig.responseJsonSchema`. Both
 * constrain generation server-side, which is why the descent no longer parses a
 * trailing Markdown control block — a malformed verdict is now impossible
 * rather than merely unlikely.
 *
 * Kept to the portable subset: object/array/string/integer/boolean, `enum`,
 * `required`, `additionalProperties: false`. No numeric or length constraints —
 * those are not supported by structured outputs and would be silently dropped.
 */
class GradingSchema
{
    /**
     * @return array<string, mixed>
     */
    public function grading(): array
    {
        return [
            'type' => 'object',
            'additionalProperties' => false,
            'required' => [
                'verdict',
                'score',
                'confidence',
                'criteria',
                'demonstrated_concepts',
                'missing_concepts',
                'misconceptions',
                'feedback',
                'recommended_action',
            ],
            'properties' => [
                'verdict' => [
                    'type' => 'string',
                    'enum' => CheckpointAttempt::VERDICTS,
                    'description' => 'pass when the core idea was demonstrated, partial when it was half-shown, retry otherwise.',
                ],
                'score' => [
                    'type' => 'integer',
                    'description' => 'How completely the answer demonstrated the layer, 0 to 100.',
                ],
                'confidence' => [
                    'type' => 'integer',
                    'description' => 'How certain you are of this verdict, 0 to 100.',
                ],
                'criteria' => [
                    'type' => 'array',
                    'description' => 'Each thing a good answer had to show, judged independently.',
                    'items' => [
                        'type' => 'object',
                        'additionalProperties' => false,
                        'required' => ['name', 'met', 'note'],
                        'properties' => [
                            'name' => ['type' => 'string'],
                            'met' => ['type' => 'boolean'],
                            'note' => ['type' => 'string', 'description' => 'One short sentence of evidence.'],
                        ],
                    ],
                ],
                'demonstrated_concepts' => [
                    'type' => 'array',
                    'description' => 'Concepts the learner clearly showed they understand.',
                    'items' => ['type' => 'string'],
                ],
                'missing_concepts' => [
                    'type' => 'array',
                    'description' => 'Concepts the answer needed but did not reach.',
                    'items' => ['type' => 'string'],
                ],
                'misconceptions' => [
                    'type' => 'array',
                    'description' => 'Beliefs that are actively wrong, not merely absent. Empty when there are none.',
                    'items' => [
                        'type' => 'object',
                        'additionalProperties' => false,
                        'required' => ['concept', 'belief', 'correction'],
                        'properties' => [
                            'concept' => ['type' => 'string'],
                            'belief' => ['type' => 'string', 'description' => 'What the learner appears to believe.'],
                            'correction' => ['type' => 'string', 'description' => 'The shortest correction that fixes it.'],
                        ],
                    ],
                ],
                'feedback' => [
                    'type' => 'string',
                    'description' => 'Two or three sentences to the learner: what landed, then the one thing to fix. Never punitive.',
                ],
                'recommended_action' => [
                    'type' => 'string',
                    'enum' => CheckpointAttempt::ACTIONS,
                    'description' => 'descend to open the next layer, retry to re-answer, review to revisit an earlier layer.',
                ],
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function contextSummary(): array
    {
        return [
            'type' => 'object',
            'additionalProperties' => false,
            'required' => ['summary', 'covered_concepts'],
            'properties' => [
                'summary' => [
                    'type' => 'string',
                    'description' => 'What has been taught and proven so far, in under 150 words.',
                ],
                'covered_concepts' => [
                    'type' => 'array',
                    'description' => 'Named concepts already covered, shortest useful label each.',
                    'items' => ['type' => 'string'],
                ],
            ],
        ];
    }
}
