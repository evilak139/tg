<?php

namespace App\Filament\Resources\Users;

use App\Filament\Resources\Users\Pages\ListUsers;
use App\Filament\Resources\Users\Tables\UsersTable;
use App\Models\User;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

/**
 * 对应03.2文档"会员列表"。会员只通过机器人自助注册，后台不提供手动新建/编辑表单，
 * 只提供筛选查看 + "调整积分""拉黑/风控标记"两个专用操作（见UsersTable）。
 */
class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUsers;

    protected static ?string $navigationLabel = '会员列表';

    protected static ?string $modelLabel = '会员';

    public static function table(Table $table): Table
    {
        return UsersTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListUsers::route('/'),
        ];
    }

    public static function canCreate(): bool
    {
        return false;
    }
}
