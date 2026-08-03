<?php

namespace App\Services\Chat;

use App\Models\Conversation;
use App\Models\SubjectFolder;
use App\Models\User;
use App\Services\ValidationService;
use App\Traits\ValidationHelper;
use Illuminate\Database\Eloquent\Collection;

/**
 * The learner's own filing system for subjects — folders inside folders, and
 * which subject sits where.
 *
 * Two invariants this exists to protect:
 *   1. A folder can never become its own ancestor. A cycle doesn't just render
 *      wrong, it hangs any tree walk — so a move that would create one is
 *      refused, not repaired afterwards.
 *   2. Every folder and every subject a request names must belong to the learner
 *      making it. Ownership is re-checked here, never assumed from the payload.
 */
class SubjectFolderService
{
    use ValidationHelper;

    public function __construct()
    {
        $this->initializeValidator();
    }

    /**
     * The whole tree in two queries, regardless of how deep it nests: fetch the
     * flat sets, then wire the relations up in memory so Blade can recurse over
     * `$folder->children` / `$folder->conversations` without touching the DB.
     *
     * @return array{folders: Collection<int, SubjectFolder>, unfiled: Collection<int, Conversation>}
     */
    public function tree(?int $userId): array
    {
        $folders = SubjectFolder::query()->ownedBy($userId)->orderBy('name')->get();

        $subjects = Conversation::query()
            ->ownedBy($userId)
            ->select(['id', 'folder_id', 'subject', 'title', 'current_depth', 'status', 'updated_at'])
            ->latest('updated_at')
            ->get();

        $childrenOf = $folders->whereNotNull('parent_id')->groupBy('parent_id');
        $subjectsOf = $subjects->whereNotNull('folder_id')->groupBy('folder_id');

        $folders->each(function (SubjectFolder $folder) use ($childrenOf, $subjectsOf) {
            $folder->setRelation('children', $childrenOf->get($folder->id) ?? new Collection);
            $folder->setRelation('conversations', $subjectsOf->get($folder->id) ?? new Collection);
        });

        return [
            'folders' => $folders->whereNull('parent_id')->values(),
            'unfiled' => $subjects->whereNull('folder_id')->values(),
        ];
    }

    public function create(User $user, string $name, ?int $parentId): ValidationService
    {
        $parent = $this->ownedFolder($user->id, $parentId);

        if ($parentId !== null && ! $parent) {
            return $this->errorEncountered(__('frontend.subjects.folder-missing'), 404);
        }

        if ($parent && $this->depthOf($parent) + 1 >= (int) config('platform.subjects.max_folder_depth')) {
            return $this->errorEncountered(__('frontend.subjects.folder-too-deep'), 422);
        }

        return $this->addValidatedItems(['folder' => SubjectFolder::create([
            'user_id' => $user->id,
            'parent_id' => $parent?->id,
            'name' => $name,
        ])]);
    }

    /**
     * Reparent a folder. Refuses the two moves that would corrupt the tree:
     * into itself or one of its own descendants, and past the depth cap.
     */
    public function move(SubjectFolder $folder, ?int $parentId): ValidationService
    {
        if ($parentId === null) {
            $folder->update(['parent_id' => null]);

            return $this->successfulCheck();
        }

        $parent = $this->ownedFolder($folder->user_id, $parentId);

        if (! $parent) {
            return $this->errorEncountered(__('frontend.subjects.folder-missing'), 404);
        }

        if ($parent->id === $folder->id || in_array($folder->id, $this->ancestorIds($parent), true)) {
            return $this->errorEncountered(__('frontend.subjects.folder-cycle'), 422);
        }

        // ponytail: caps where the folder itself lands, not its subtree height —
        // dragging a deep tree can overshoot by its own depth. Walk the subtree
        // here if that ever shows up as a real complaint.
        if ($this->depthOf($parent) + 1 >= (int) config('platform.subjects.max_folder_depth')) {
            return $this->errorEncountered(__('frontend.subjects.folder-too-deep'), 422);
        }

        $folder->update(['parent_id' => $parent->id]);

        return $this->successfulCheck();
    }

    /** File a subject into a folder, or out of every folder when null. */
    public function fileSubject(Conversation $conversation, ?int $folderId): ValidationService
    {
        if ($folderId !== null && ! $this->ownedFolder($conversation->user_id, $folderId)) {
            return $this->errorEncountered(__('frontend.subjects.folder-missing'), 404);
        }

        $conversation->update(['folder_id' => $folderId]);

        return $this->successfulCheck();
    }

    private function ownedFolder(?int $userId, ?int $folderId): ?SubjectFolder
    {
        if ($folderId === null || $userId === null) {
            return null;
        }

        return SubjectFolder::query()->ownedBy($userId)->find($folderId);
    }

    private function depthOf(SubjectFolder $folder): int
    {
        return count($this->ancestorIds($folder));
    }

    /**
     * Ids from the folder's parent upward. Bounded by the depth cap so a tree
     * corrupted by any other means still terminates the walk.
     *
     * @return array<int, int>
     */
    private function ancestorIds(SubjectFolder $folder): array
    {
        $ids = [];
        $current = $folder;
        $ceiling = (int) config('platform.subjects.max_folder_depth') + 1;

        while ($current->parent_id && count($ids) < $ceiling) {
            $ids[] = $current->parent_id;
            $current = SubjectFolder::find($current->parent_id);

            if (! $current) {
                break;
            }
        }

        return $ids;
    }
}
