<?php

namespace App\Models;

use App\Enums\BatchStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PointsMonthlyBatch extends Model
{
    protected $fillable = [
        'user_id',
        'batch_month',
        'points_earned_total',
        'points_consumed_total',
        'expire_at',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'expire_at' => 'datetime',
            'status' => BatchStatus::class,
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
