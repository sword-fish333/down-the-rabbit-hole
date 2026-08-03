<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A shelf in the learner's own library — one node of the folder tree they file
 * subjects into. The tree is theirs alone; nothing in the descent reads it.
 *
 * Ordering is alphabetical everywhere, deliberately: a manual sort order is one
 * more thing to maintain and drag around, and a learner looking for "Stoicism"
 * wants it where the alphabet says it is.
 */
#[Fillable(['user_id', 'parent_id', 'name'])]
class SubjectFolder extends Model
{
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id')->orderBy('name');
    }

    /** The subjects filed directly in this folder, most recently touched first. */
    public function conversations(): HasMany
    {
        return $this->hasMany(Conversation::class, 'folder_id')->latest('updated_at');
    }

    #[Scope]
    protected function ownedBy(Builder $query, ?int $userId): void
    {
        $query->where('user_id', $userId);
    }

    #[Scope]
    protected function roots(Builder $query): void
    {
        $query->whereNull('parent_id');
    }
}
