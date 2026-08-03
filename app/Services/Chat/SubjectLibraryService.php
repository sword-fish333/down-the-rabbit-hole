<?php

namespace App\Services\Chat;

use App\Models\Conversation;
use App\Models\User;
use Illuminate\Contracts\Pagination\CursorPaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

/**
 * The learner's library of subjects: the list, the search, the recents in the
 * sidebar, and bulk removal.
 *
 * Cursor pagination rather than offset, because the library is an append-mostly
 * list read newest-first: a cursor is a stable keyset scan (no `count(*)`, no
 * rows sliding between pages while a descent updates `updated_at` mid-scroll).
 */
class SubjectLibraryService
{
    /**
     * One page of the library. Eager-loads exactly what a row renders, so a
     * 25-row page is a fixed four queries no matter how long the list gets.
     */
    public function page(?int $userId, ?string $search, ?string $filter, ?string $cursor = null): CursorPaginator
    {
        return $this->query($userId)
            ->matching($search)
            ->filtered($filter)
            ->orderByDesc('updated_at')
            ->orderByDesc('id')
            ->cursorPaginate(
                perPage: (int) config('platform.subjects.per_page'),
                cursor: $cursor,
            );
    }

    /**
     * The sidebar's recents — the reason to keep the app open: an unfinished
     * descent is one click away from every screen.
     *
     * @return Collection<int, Conversation>
     */
    public function recent(?int $userId): Collection
    {
        return $this->query($userId)
            ->latest('updated_at')
            ->limit((int) config('platform.subjects.sidebar_limit'))
            ->get();
    }

    /**
     * Remove a selection. Scoped to the owner inside the delete itself rather
     * than checked beforehand, so a forged id can't reach another learner's row.
     *
     * @param  array<int, int|string>  $ids
     */
    public function delete(User $user, array $ids): int
    {
        $ids = array_slice(array_filter(array_map('intval', $ids)), 0, 500);

        if ($ids === []) {
            return 0;
        }

        return Conversation::query()->ownedBy($user->id)->whereIn('id', $ids)->delete();
    }

    private function query(?int $userId): Builder
    {
        return Conversation::query()
            ->ownedBy($userId)
            ->with(['learningMode:id,name,icon', 'folder:id,name', 'sources:id,conversation_id,site,title'])
            ->withCount(['concepts as mastered_count' => fn ($query) => $query->where('state', 'mastered')]);
    }
}
