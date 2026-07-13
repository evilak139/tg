<?php

namespace App\Models;

use App\Enums\ActivityTag;
use App\Enums\IdentityLevel;
use App\Enums\UserStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class User extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'tg_user_id',
        'tg_username',
        'nickname',
        'invited_by_l1',
        'invited_by_l2',
        'invited_by_l3',
        'points_balance',
        'register_time',
        'last_active_time',
        'checkin_streak',
        'last_checkin_date',
        'identity_level',
        'activity_tag',
        'is_high_value',
        'milestones_claimed',
        'device_fingerprint',
        'register_ip',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'register_time' => 'datetime',
            'last_active_time' => 'datetime',
            'last_checkin_date' => 'date',
            'is_high_value' => 'boolean',
            'milestones_claimed' => 'array',
            'identity_level' => IdentityLevel::class,
            'activity_tag' => ActivityTag::class,
            'status' => UserStatus::class,
        ];
    }

    public function inviterL1(): BelongsTo
    {
        return $this->belongsTo(User::class, 'invited_by_l1');
    }

    public function inviterL2(): BelongsTo
    {
        return $this->belongsTo(User::class, 'invited_by_l2');
    }

    public function inviterL3(): BelongsTo
    {
        return $this->belongsTo(User::class, 'invited_by_l3');
    }

    public function directInvitees(): HasMany
    {
        return $this->hasMany(User::class, 'invited_by_l1');
    }

    public function inviteLinks(): HasMany
    {
        return $this->hasMany(InviteLink::class);
    }

    public function pointsLedgers(): HasMany
    {
        return $this->hasMany(PointsLedger::class);
    }

    public function pointsMonthlyBatches(): HasMany
    {
        return $this->hasMany(PointsMonthlyBatch::class);
    }

    public function checkins(): HasMany
    {
        return $this->hasMany(Checkin::class);
    }

    public function withdrawRequests(): HasMany
    {
        return $this->hasMany(WithdrawRequest::class);
    }
}
