<?php

namespace App\Models;

use App\Enums\AdminRole;
use Filament\Models\Contracts\FilamentUser;
use Filament\Models\Contracts\HasName;
use Filament\Panel;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class AdminUser extends Authenticatable implements FilamentUser, HasName
{
    use Notifiable;

    const UPDATED_AT = null;

    protected $fillable = [
        'username',
        'password_hash',
        'role',
    ];

    protected $hidden = [
        'password_hash',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'password_hash' => 'hashed',
            'role' => AdminRole::class,
        ];
    }

    public function getAuthPassword(): string
    {
        return $this->password_hash;
    }

    public function canAccessPanel(Panel $panel): bool
    {
        return true;
    }

    public function getFilamentName(): string
    {
        return $this->username;
    }

    /**
     * 对应03.6文档："机器人配置""域名配置"仅超级管理员可操作。
     */
    public function isSuperAdmin(): bool
    {
        return $this->role === AdminRole::SuperAdmin;
    }

    /**
     * 对应03.6文档："标记提现完成"客服和超级管理员可操作。
     */
    public function canManageWithdrawals(): bool
    {
        return in_array($this->role, [AdminRole::SuperAdmin, AdminRole::CustomerService], true);
    }
}
