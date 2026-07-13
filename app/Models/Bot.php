<?php

namespace App\Models;

use App\Enums\EnableStatus;
use Illuminate\Database\Eloquent\Model;

class Bot extends Model
{
    protected $fillable = [
        'token',
        'bot_username',
        'status',
        'is_active',
        'last_health_check_time',
    ];

    protected $hidden = [
        'token',
    ];

    protected function casts(): array
    {
        return [
            'token' => 'encrypted',
            'status' => EnableStatus::class,
            'is_active' => 'boolean',
            'last_health_check_time' => 'datetime',
        ];
    }
}
