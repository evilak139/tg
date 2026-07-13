<?php

namespace App\Models;

use App\Enums\WithdrawStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WithdrawRequest extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'points_amount',
        'exchange_amount',
        'status',
        'risk_flag',
        'applied_at',
        'completed_at',
        'operator',
    ];

    protected function casts(): array
    {
        return [
            'exchange_amount' => 'decimal:2',
            'status' => WithdrawStatus::class,
            'risk_flag' => 'boolean',
            'applied_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
