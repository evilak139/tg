<?php

namespace App\Models;

use App\Enums\MessageTemplateType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MessageTemplate extends Model
{
    const CREATED_AT = null;

    protected $fillable = [
        'type',
        'title',
        'content',
        'image_url',
        'updated_by',
    ];

    public function broadcastTasks(): HasMany
    {
        return $this->hasMany(BroadcastTask::class, 'template_id');
    }

    protected function casts(): array
    {
        return [
            'type' => MessageTemplateType::class,
            'updated_at' => 'datetime',
        ];
    }
}
