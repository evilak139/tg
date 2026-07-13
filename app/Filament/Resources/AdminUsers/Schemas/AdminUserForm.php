<?php

namespace App\Filament\Resources\AdminUsers\Schemas;

use App\Enums\AdminRole;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class AdminUserForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('username')
                    ->label('用户名')
                    ->required()
                    ->unique(ignoreRecord: true),
                TextInput::make('password_hash')
                    ->label('密码')
                    ->password()
                    ->revealable()
                    ->required(fn (string $operation) => $operation === 'create')
                    ->dehydrated(fn (?string $state) => filled($state))
                    ->helperText('编辑时留空表示不修改密码'),
                Select::make('role')
                    ->label('角色')
                    ->options(AdminRole::class)
                    ->required(),
            ]);
    }
}
