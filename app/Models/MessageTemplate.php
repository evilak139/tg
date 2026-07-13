<?php

namespace App\Models;

use App\Enums\MessageTemplateType;
use Illuminate\Database\Eloquent\Model;

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

    protected function casts(): array
    {
        return [
            'type' => MessageTemplateType::class,
            'updated_at' => 'datetime',
        ];
    }
}
