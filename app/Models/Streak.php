<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * The user's "White Rabbit" daily streak — one row per user. Advanced once a day
 * when a layer is completed; see App\Services\Gamification\StreakService.
 */
#[Fillable(['user_id', 'current_count', 'longest_count', 'last_activity_date', 'freezes'])]
class Streak extends Model
{
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    protected function casts(): array
    {
        return [
            'last_activity_date' => 'date',
            'current_count' => 'integer',
            'longest_count' => 'integer',
            'freezes' => 'integer',
        ];
    }
}
