<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Checkin extends Model
{
    protected $fillable = [
        'user_id',
        'checkin_date',
        'streak_at_checkin',
        'points_earned',
    ];

    protected function casts(): array
    {
        return [
            'checkin_date' => 'date',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
