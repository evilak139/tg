<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LeaderboardSnapshot extends Model
{
    protected $table = 'leaderboard_snapshot';

    protected $fillable = [
        'period',
        'user_id',
        'rank',
        'invite_count_this_period',
        'reward_points',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
