<?php

namespace App\Models;

use App\Enums\BroadcastStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BroadcastTask extends Model
{
    protected $fillable = [
        'template_id',
        'target_filter',
        'scheduled_time',
        'status',
        'total_target_count',
        'sent_count',
        'click_count',
        'created_by',
        'failure_reason',
    ];

    protected function casts(): array
    {
        return [
            'target_filter' => 'array',
            'scheduled_time' => 'datetime',
            'status' => BroadcastStatus::class,
        ];
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(MessageTemplate::class, 'template_id');
    }
}
