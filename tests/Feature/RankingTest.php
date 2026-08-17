<?php

namespace Tests\Feature;

use App\Models\Conversation;
use App\Models\User;
use App\Models\XpEvent;
use App\Services\Gamification\LearningRecordService;
use App\Services\Gamification\RankingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

/**
 * The boards, and the two promises they rest on.
 *
 * The first is privacy: nobody appears on a ranking, or has a readable record,
 * without having said yes. The second is that the numbers agree — a learner's
 * own record and the board they stand on are aggregates of the same ledger, so
 * "12 layers cleared" on a profile has to be the same twelve rows that place
 * them on the layers board. Both are the kind of promise that breaks quietly,
 * so both are asserted rather than trusted.
 */
class RankingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Boards are cached; every test here writes the ledger first.
        Cache::flush();
    }

    private function rankings(): RankingService
    {
        return app(RankingService::class);
    }

    /**
     * @param  array<int, array{type: string, amount: int, depth?: int, subject?: int, at?: string}>  $events
     */
    private function learner(string $name, array $events, bool $ranked = true): User
    {
        $user = User::factory()->create(['first_name' => $name, 'ranked' => $ranked]);

        foreach ($events as $event) {
            $at = isset($event['at']) ? now()->parse($event['at']) : now();

            XpEvent::create([
                'user_id' => $user->id,
                'conversation_id' => $event['subject'] ?? null,
                'type' => $event['type'],
                'amount' => $event['amount'],
                'depth' => $event['depth'] ?? null,
            ])->forceFill(['created_at' => $at])->save();
        }

        // users.xp is a guarded projection of the ledger — the app only ever
        // moves it through XpService, so the fixture writes it the same way.
        $user->forceFill(['xp' => array_sum(array_column($events, 'amount'))])->save();

        return $user->fresh();
    }

    /**
     * @return array<int, array{type: string, amount: int, depth: int, subject: int|null}>
     */
    private function layers(int $count, ?int $subject = null): array
    {
        return array_map(fn (int $depth) => [
            'type' => XpEvent::TYPE_LAYER_COMPLETED,
            'amount' => 50,
            'depth' => $depth,
            'subject' => $subject,
        ], range(0, $count - 1));
    }

    private function subject(string $title): Conversation
    {
        return Conversation::create(['subject' => $title, 'title' => $title]);
    }

    public function test_each_board_ranks_by_its_own_measure(): void
    {
        $topology = $this->subject('Topology');
        $stoicism = $this->subject('Stoicism');

        // Wide: two subjects, neither deep. Deep: one subject taken a long way
        // down. Both have cleared four layers in total.
        $wide = $this->learner('Wide', [
            ...$this->layers(2, $topology->id),
            ...$this->layers(2, $stoicism->id),
            ['type' => XpEvent::TYPE_CONCEPT_MASTERED, 'amount' => 20],
            ['type' => XpEvent::TYPE_CONCEPT_MASTERED, 'amount' => 20],
        ]);

        $deep = $this->learner('Deep', [
            ...$this->layers(5, $topology->id),
            ['type' => XpEvent::TYPE_SUBJECT_SURFACED, 'amount' => 200, 'subject' => $topology->id],
        ]);

        $first = fn (string $board) => $this->rankings()->board($board, RankingService::PERIOD_ALL)->first();

        $this->assertSame($deep->id, $first(RankingService::BOARD_XP)['user']->id);
        $this->assertSame($deep->id, $first(RankingService::BOARD_LAYERS)['user']->id);
        $this->assertSame($deep->id, $first(RankingService::BOARD_SURFACED)['user']->id);

        // Deepest dive is layers proven inside ONE subject, not layers overall.
        $this->assertSame(5, $first(RankingService::BOARD_DEPTH)['value']);

        // Two different behaviours, two different leaders — the whole point of
        // running six boards rather than one.
        $this->assertSame($wide->id, $first(RankingService::BOARD_CONCEPTS)['user']->id);
        $this->assertSame($wide->id, $first(RankingService::BOARD_SUBJECTS)['user']->id);
        $this->assertSame(2, $first(RankingService::BOARD_SUBJECTS)['value']);
    }

    public function test_a_learner_who_has_not_joined_is_on_no_board(): void
    {
        $this->learner('Public', $this->layers(1));
        $private = $this->learner('Private', $this->layers(9), ranked: false);

        $board = $this->rankings()->board(RankingService::BOARD_LAYERS, RankingService::PERIOD_ALL);

        $this->assertCount(1, $board);
        $this->assertNotSame($private->id, $board->first()['user']->id);

        // But they are still told where they would land — the invitation.
        $standing = $this->rankings()->standing($private, RankingService::BOARD_LAYERS, RankingService::PERIOD_ALL);
        $this->assertSame(1, $standing['rank']);
        $this->assertSame(9, $standing['value']);
    }

    public function test_a_disabled_account_leaves_the_boards(): void
    {
        $blocked = $this->learner('Blocked', $this->layers(4));
        $blocked->update(['enabled' => false]);
        Cache::flush();

        $this->assertCount(0, $this->rankings()->board(RankingService::BOARD_LAYERS, RankingService::PERIOD_ALL));
    }

    public function test_the_windows_only_count_what_happened_inside_them(): void
    {
        $veteran = $this->learner('Veteran', [
            ['type' => XpEvent::TYPE_LAYER_COMPLETED, 'amount' => 50, 'depth' => 0, 'at' => '-4 months'],
            ['type' => XpEvent::TYPE_LAYER_COMPLETED, 'amount' => 50, 'depth' => 1, 'at' => '-4 months'],
            ['type' => XpEvent::TYPE_LAYER_COMPLETED, 'amount' => 50, 'depth' => 2, 'at' => '-4 months'],
        ]);

        $newcomer = $this->learner('Newcomer', [
            ['type' => XpEvent::TYPE_LAYER_COMPLETED, 'amount' => 50, 'depth' => 0],
        ]);

        $all = $this->rankings()->board(RankingService::BOARD_LAYERS, RankingService::PERIOD_ALL);
        $this->assertSame($veteran->id, $all->first()['user']->id);

        // A monthly board is what makes joining today worth doing at all.
        $month = $this->rankings()->board(RankingService::BOARD_LAYERS, RankingService::PERIOD_MONTH);
        $this->assertCount(1, $month);
        $this->assertSame($newcomer->id, $month->first()['user']->id);
    }

    public function test_equal_records_share_a_rank_on_the_board_and_in_your_own_standing(): void
    {
        $first = $this->learner('First', $this->layers(3));
        $tied = $this->learner('Tied', $this->layers(3));
        $this->learner('Third', $this->layers(1));

        $board = $this->rankings()->board(RankingService::BOARD_LAYERS, RankingService::PERIOD_ALL);

        $this->assertSame([1, 1, 3], $board->pluck('rank')->all());
        $this->assertSame(
            1,
            $this->rankings()->standing($tied, RankingService::BOARD_LAYERS, RankingService::PERIOD_ALL)['rank'],
            'The rank under your own row must match the one beside it.',
        );
        $this->assertNotSame($first->id, $tied->id);
    }

    public function test_a_learner_with_nothing_yet_has_no_rank_rather_than_a_last_place(): void
    {
        $empty = $this->learner('Empty', []);

        $standing = $this->rankings()->standing($empty, RankingService::BOARD_XP, RankingService::PERIOD_ALL);

        $this->assertNull($standing['rank']);
        $this->assertSame(0, $standing['value']);
        $this->assertTrue($this->rankings()->standings($empty)->isEmpty());
    }

    /** The record and the boards read the same ledger, so they cannot drift. */
    public function test_the_learning_record_matches_the_all_time_boards(): void
    {
        $topology = $this->subject('Topology');

        $user = $this->learner('Alice', [
            ...$this->layers(4, $topology->id),
            ['type' => XpEvent::TYPE_CONCEPT_MASTERED, 'amount' => 20],
            ['type' => XpEvent::TYPE_SUBJECT_SURFACED, 'amount' => 200, 'subject' => $topology->id],
        ]);

        $record = app(LearningRecordService::class)->for($user);
        $value = fn (string $board) => $this->rankings()->standing($user, $board, RankingService::PERIOD_ALL)['value'];

        $this->assertSame($value(RankingService::BOARD_LAYERS), $record['layers']);
        $this->assertSame($value(RankingService::BOARD_CONCEPTS), $record['mastered']);
        $this->assertSame($value(RankingService::BOARD_SURFACED), $record['surfaced']);
        $this->assertSame($value(RankingService::BOARD_DEPTH), $record['deepest']);
        $this->assertSame($value(RankingService::BOARD_XP), $record['xp']);
    }

    public function test_the_boards_page_rejects_a_board_or_window_that_does_not_exist(): void
    {
        $user = $this->learner('Alice', $this->layers(1));

        $this->actingAs($user)->get(route('rankings.index', ['board' => 'time_on_site']))
            ->assertSessionHasErrors('board');

        $this->actingAs($user)->get(route('rankings.index', ['period' => 'forever']))
            ->assertSessionHasErrors('period');
    }

    public function test_joining_and_leaving_the_boards_is_one_switch(): void
    {
        $user = User::factory()->create(['ranked' => false]);

        $this->actingAs($user)->post(route('profile.update-ranking'), ['ranked' => '1'])->assertRedirect();
        $this->assertTrue($user->fresh()->isRanked());

        $this->actingAs($user)->post(route('profile.update-ranking'), ['ranked' => '0'])->assertRedirect();
        $this->assertFalse($user->fresh()->isRanked());
    }

    /**
     * The public record is the same yes as the boards. Nothing else opens it,
     * and the owner can always see their own — that preview is how someone
     * decides whether to say yes at all.
     */
    public function test_a_record_is_readable_only_once_its_owner_has_joined(): void
    {
        $private = $this->learner('Private', $this->layers(2), ranked: false);
        $reader = $this->learner('Reader', $this->layers(1));

        $this->actingAs($reader)->get(route('learners.show', $private))->assertNotFound();
        $this->actingAs($private)->get(route('learners.show', $private))->assertOk();

        $private->update(['ranked' => true]);
        $this->actingAs($reader)->get(route('learners.show', $private))->assertOk();
    }

    public function test_the_boards_are_not_open_to_visitors(): void
    {
        $user = $this->learner('Alice', $this->layers(1));

        $this->get(route('rankings.index'))->assertRedirect(route('login'));
        $this->get(route('learners.show', $user))->assertRedirect(route('login'));
    }
}
