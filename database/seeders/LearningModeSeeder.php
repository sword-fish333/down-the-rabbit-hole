<?php

namespace Database\Seeders;

use App\Models\LearningMode;
use Illuminate\Database\Seeder;

/**
 * The six ways the guide can teach a layer. Idempotent — re-running updates the
 * copy and the directives in place rather than duplicating rows, so this stays
 * safe to run on an environment that already has holes pointing at these modes.
 */
class LearningModeSeeder extends Seeder
{
    public function run(): void
    {
        foreach ($this->modes() as $position => $mode) {
            LearningMode::updateOrCreate(
                ['slug' => $mode['slug']],
                $mode + ['position' => $position],
            );
        }
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function modes(): array
    {
        return [
            [
                'slug' => 'socratic',
                'name' => 'Socratic',
                'tagline' => 'Questions before answers.',
                'description' => 'The guide leads with questions and lets you build the idea yourself. Slowest route, deepest retention.',
                'icon' => 'psychology_alt',
                'accent' => LearningMode::ACCENT_WAVE,
                'is_default' => true,
                'prompt_directive' => 'Lead with a question that exposes the gap in the learner\'s current model, then let '
                    .'the explanation answer it. Prefer drawing the idea out over stating it. Never ask more than one '
                    .'question at a time, and always resolve the question you opened with before the checkpoint.',
            ],
            [
                'slug' => 'visual-explanation',
                'name' => 'Visual explanation',
                'tagline' => 'Diagrams, structure, spatial intuition.',
                'description' => 'Concepts arrive as structures you can picture — tables, trees, before/after comparisons.',
                'icon' => 'account_tree',
                'accent' => LearningMode::ACCENT_SEA,
                'prompt_directive' => 'Anchor the layer in something the learner can picture: a table, an ASCII diagram, a '
                    .'before/after comparison, or a spatial metaphor. Describe the structure explicitly and label its parts, '
                    .'then explain the mechanism using those labels.',
            ],
            [
                'slug' => 'exam-preparation',
                'name' => 'Exam preparation',
                'tagline' => 'Precision, terminology, common traps.',
                'description' => 'Exact definitions, the distinctions examiners test, and the mistakes that lose marks.',
                'icon' => 'fact_check',
                'accent' => LearningMode::ACCENT_SAND,
                'prompt_directive' => 'Be precise and exam-oriented: give the exact terminology, state definitions in the '
                    .'form an examiner expects, and call out the distinction most candidates get wrong on this topic. '
                    .'Make the checkpoint resemble a real exam question.',
            ],
            [
                'slug' => 'project-based',
                'name' => 'Project-based',
                'tagline' => 'Build something with each layer.',
                'description' => 'Every layer advances one concrete artefact you are building end to end.',
                'icon' => 'construction',
                'accent' => LearningMode::ACCENT_SAND,
                'prompt_directive' => 'Frame the layer as one step of a single concrete project the learner is building '
                    .'across the whole descent. Introduce the project on layer 0 and advance the same artefact every layer. '
                    .'The checkpoint should ask them to make or decide the next piece of it.',
            ],
            [
                'slug' => 'fast-overview',
                'name' => 'Fast overview',
                'tagline' => 'The shape of the field, quickly.',
                'description' => 'Wider layers, less depth per layer — for orienting yourself in unfamiliar territory.',
                'icon' => 'bolt',
                'accent' => LearningMode::ACCENT_CLAY,
                'prompt_directive' => 'Keep the layer short and orienting — roughly half your usual length. Prioritise the '
                    .'shape of the territory and how the pieces relate over exhaustive detail. Still teach exactly one '
                    .'layer; brevity is not permission to skip ahead.',
            ],
            [
                'slug' => 'deep-technical-descent',
                'name' => 'Deep technical descent',
                'tagline' => 'Mechanisms, edge cases, first principles.',
                'description' => 'Full rigour: derivations, failure modes, and the assumptions everything rests on.',
                'icon' => 'stairs',
                'accent' => LearningMode::ACCENT_WAVE,
                'prompt_directive' => 'Assume a technically capable learner. Go to the mechanism: derivations, invariants, '
                    .'complexity, failure modes, and the assumptions the result depends on. Name the edge cases that break '
                    .'naive understanding, and make the checkpoint require reasoning rather than recall.',
            ],
        ];
    }
}
