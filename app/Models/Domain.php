<?php

namespace App\Models;

use App\Enums\EnableStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Domain extends Model
{
    protected $fillable = [
        'domain',
        'status',
        'last_check_time',
        'last_check_result',
    ];

    protected function casts(): array
    {
        return [
            'status' => EnableStatus::class,
            'last_check_time' => 'datetime',
        ];
    }

    public function inviteLinks(): HasMany
    {
        return $this->hasMany(InviteLink::class);
    }
}
