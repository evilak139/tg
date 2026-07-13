<?php

namespace App\Models;

use App\Enums\PointsChangeType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PointsLedger extends Model
{
    const UPDATED_AT = null;

    protected $fillable = [
        'user_id',
        'change_type',
        'amount',
        'balance_after',
        'related_user_id',
        'operator',
    ];

    protected function casts(): array
    {
        return [
            'change_type' => PointsChangeType::class,
            'created_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function relatedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'related_user_id');
    }
}
