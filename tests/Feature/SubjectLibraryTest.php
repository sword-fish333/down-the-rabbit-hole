<?php

namespace Tests\Feature;

use App\Models\Conversation;
use App\Models\SubjectFolder;
use App\Models\User;
use App\Services\Chat\SubjectFolderService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The library and the organiser.
 *
 * Two things are worth guarding here beyond "the page loads": that one learner
 * can never reach, move or delete another learner's subject, and that no move
 * can put a folder inside itself — a cycle doesn't render wrong, it hangs every
 * walk of the tree afterwards.
 */
class SubjectLibraryTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
    }

    private function subject(array $attributes = []): Conversation
    {
        $named = $attributes['subject'] ?? 'Transformers';

        return Conversation::create($attributes + [
            'user_id' => $this->user->id,
            'subject' => $named,
            'title' => $named,
            'status' => Conversation::STATUS_EXPLORING,
        ]);
    }

    /**
     * Only the rows, never the chrome. The rail lists recent subjects on every
     * screen, so asserting "the library does not show X" against a whole page
     * would be testing the rail by accident.
     */
    private function rows(array $query = [])
    {
        return $this->actingAs($this->user)
            ->get(route('subjects.index', $query + ['fragment' => 1]))
            ->assertOk();
    }

    /* --- The list ---------------------------------------------------------- */

    public function test_search_narrows_the_library(): void
    {
        $this->subject(['subject' => 'Stoicism']);
        $this->subject(['subject' => 'The fall of Rome']);

        $this->rows(['q' => 'Rome'])
            ->assertSee('The fall of Rome')
            ->assertDontSee('Stoicism');
    }

    public function test_a_search_term_with_wildcards_is_matched_literally(): void
    {
        $this->subject(['subject' => 'Stoicism']);
        $this->subject(['subject' => 'Inflation above 50% a year']);

        // Unescaped, `%` matches everything and search quietly becomes noise.
        $this->rows(['q' => '50%'])
            ->assertSee('Inflation above 50% a year')
            ->assertDontSee('Stoicism');
    }

    public function test_the_filters_select_by_state(): void
    {
        $this->subject(['subject' => 'Open one']);
        $this->subject(['subject' => 'Finished one', 'status' => Conversation::STATUS_SURFACED]);

        $this->rows(['filter' => Conversation::FILTER_SURFACED])
            ->assertSee('Finished one')
            ->assertDontSee('Open one');

        $this->rows(['filter' => Conversation::FILTER_ACTIVE])
            ->assertSee('Open one')
            ->assertDontSee('Finished one');
    }

    public function test_a_search_with_no_results_says_so_instead_of_going_blank(): void
    {
        $this->subject(['subject' => 'Stoicism']);

        // A first page with nothing on it is an empty state; the tail of a
        // scroll with nothing on it is just the end of the list.
        $this->rows(['q' => 'nothing like this'])->assertSee('Nothing matches that');
        $this->rows(['cursor' => 'x', 'q' => 'nothing like this'])->assertDontSee('Nothing matches that');
    }

    public function test_a_fragment_request_returns_rows_without_the_page(): void
    {
        $this->subject();

        $response = $this->actingAs($this->user)
            ->get(route('subjects.index', ['fragment' => 1]))
            ->assertOk()
            ->assertSee('Transformers')
            ->assertDontSee('<!doctype html>', false);

        $this->assertStringContainsString('data-rows-meta', $response->getContent());
    }

    public function test_the_library_pages_by_cursor(): void
    {
        config(['platform.subjects.per_page' => 1]);

        $older = $this->subject(['subject' => 'Older']);
        $newer = $this->subject(['subject' => 'Newer']);

        $older->forceFill(['updated_at' => now()->subDay()])->save();
        $newer->forceFill(['updated_at' => now()])->save();

        $first = $this->rows()->assertSee('Newer')->assertDontSee('Older');

        preg_match('/data-next-cursor="([^"]+)"/', $first->getContent(), $matches);
        $this->assertNotEmpty($matches[1] ?? null, 'The first page should offer a cursor.');

        $this->rows(['cursor' => $matches[1]])
            ->assertSee('Older')
            ->assertDontSee('Newer');
    }

    /* --- Removing several -------------------------------------------------- */

    public function test_bulk_delete_removes_only_the_learners_own_subjects(): void
    {
        $mine = $this->subject();
        $theirs = Conversation::create([
            'user_id' => User::factory()->create()->id,
            'subject' => 'Not mine',
            'status' => Conversation::STATUS_EXPLORING,
        ]);

        $this->actingAs($this->user)
            ->delete(route('subjects.destroy'), ['ids' => [$mine->id, $theirs->id]])
            ->assertRedirect();

        $this->assertModelMissing($mine);
        $this->assertModelExists($theirs);
    }

    /* --- Sharing ----------------------------------------------------------- */

    public function test_sharing_mints_a_link_and_unsharing_kills_it(): void
    {
        $subject = $this->subject();

        $this->actingAs($this->user)->post(route('subject.share', $subject))->assertRedirect();

        $token = $subject->fresh()->share_token;
        $this->assertNotNull($token);

        // Readable by anyone holding the link, signed in or not.
        $this->get(route('subject.shared', $token))->assertOk()->assertSee('Transformers');

        $this->actingAs($this->user)->post(route('subject.share', $subject))->assertRedirect();

        $this->assertNull($subject->fresh()->share_token);
        $this->get(route('subject.shared', $token))->assertNotFound();
    }

    public function test_sharing_someone_elses_subject_is_not_found(): void
    {
        $theirs = Conversation::create([
            'user_id' => User::factory()->create()->id,
            'subject' => 'Not mine',
            'status' => Conversation::STATUS_EXPLORING,
        ]);

        $this->actingAs($this->user)->post(route('subject.share', $theirs))->assertNotFound();
        $this->assertNull($theirs->fresh()->share_token);
    }

    /* --- Filing ------------------------------------------------------------ */

    public function test_a_subject_can_be_filed_and_unfiled(): void
    {
        $subject = $this->subject();
        $folder = SubjectFolder::create(['user_id' => $this->user->id, 'name' => 'AI']);

        $this->actingAs($this->user)
            ->patch(route('subject.file', $subject), ['folder_id' => $folder->id])
            ->assertRedirect();

        $this->assertSame($folder->id, $subject->fresh()->folder_id);

        $this->actingAs($this->user)
            ->patch(route('subject.file', $subject), ['folder_id' => null])
            ->assertRedirect();

        $this->assertNull($subject->fresh()->folder_id);
    }

    public function test_a_subject_cannot_be_filed_into_someone_elses_folder(): void
    {
        $subject = $this->subject();
        $foreign = SubjectFolder::create(['user_id' => User::factory()->create()->id, 'name' => 'Theirs']);

        $this->actingAs($this->user)
            ->patch(route('subject.file', $subject), ['folder_id' => $foreign->id])
            ->assertRedirect()
            ->assertSessionHas('error');

        $this->assertNull($subject->fresh()->folder_id);
    }

    /* --- The tree ---------------------------------------------------------- */

    public function test_a_folder_cannot_be_moved_inside_itself_or_its_own_child(): void
    {
        $parent = SubjectFolder::create(['user_id' => $this->user->id, 'name' => 'Parent']);
        $child = SubjectFolder::create(['user_id' => $this->user->id, 'parent_id' => $parent->id, 'name' => 'Child']);

        $folders = app(SubjectFolderService::class);

        $this->assertFalse($folders->move($parent, $parent->id)->isSuccessfulCheck());
        $this->assertFalse($folders->move($parent, $child->id)->isSuccessfulCheck());
        $this->assertNull($parent->fresh()->parent_id);
    }

    public function test_the_folder_depth_cap_is_enforced(): void
    {
        config(['platform.subjects.max_folder_depth' => 2]);

        $folders = app(SubjectFolderService::class);

        $root = $folders->create($this->user, 'One', null)->getValidatedItem('folder');
        $second = $folders->create($this->user, 'Two', $root->id)->getValidatedItem('folder');

        $this->assertFalse($folders->create($this->user, 'Three', $second->id)->isSuccessfulCheck());
    }

    public function test_deleting_a_folder_keeps_every_subject(): void
    {
        $parent = SubjectFolder::create(['user_id' => $this->user->id, 'name' => 'Parent']);
        $child = SubjectFolder::create(['user_id' => $this->user->id, 'parent_id' => $parent->id, 'name' => 'Child']);
        $subject = $this->subject(['folder_id' => $child->id]);

        $this->actingAs($this->user)
            ->delete(route('subjects.folders.destroy', $parent))
            ->assertRedirect();

        $this->assertModelMissing($parent);
        $this->assertModelMissing($child);
        $this->assertModelExists($subject);
        $this->assertNull($subject->fresh()->folder_id);
    }

    public function test_the_tree_nests_folders_and_lists_unfiled_subjects_separately(): void
    {
        $parent = SubjectFolder::create(['user_id' => $this->user->id, 'name' => 'Parent']);
        SubjectFolder::create(['user_id' => $this->user->id, 'parent_id' => $parent->id, 'name' => 'Child']);
        $filed = $this->subject(['subject' => 'Filed', 'folder_id' => $parent->id]);
        $loose = $this->subject(['subject' => 'Loose']);

        $tree = app(SubjectFolderService::class)->tree($this->user->id);

        $this->assertCount(1, $tree['folders']);
        $this->assertCount(1, $tree['folders']->first()->children);
        $this->assertTrue($tree['folders']->first()->conversations->contains($filed));
        $this->assertSame([$loose->id], $tree['unfiled']->pluck('id')->all());
    }

    public function test_another_learners_folder_cannot_be_renamed_or_deleted(): void
    {
        $foreign = SubjectFolder::create(['user_id' => User::factory()->create()->id, 'name' => 'Theirs']);

        $this->actingAs($this->user)
            ->patch(route('subjects.folders.update', $foreign), ['name' => 'Mine now'])
            ->assertNotFound();

        $this->actingAs($this->user)
            ->delete(route('subjects.folders.destroy', $foreign))
            ->assertNotFound();

        $this->assertSame('Theirs', $foreign->fresh()->name);
    }
}
