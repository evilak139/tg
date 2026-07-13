<?php

namespace App\Filament\Resources\WithdrawRequests;

use App\Filament\Resources\WithdrawRequests\Pages\ListWithdrawRequests;
use App\Filament\Resources\WithdrawRequests\Tables\WithdrawRequestsTable;
use App\Models\WithdrawRequest;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

/**
 * 对应03.7文档"提现申请管理"：申请只能由会员通过机器人提交，后台只负责核实并标记完成。
 */
class WithdrawRequestResource extends Resource
{
    protected static ?string $model = WithdrawRequest::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBanknotes;

    protected static ?string $navigationLabel = '提现申请管理';

    protected static ?string $modelLabel = '提现申请';

    public static function table(Table $table): Table
    {
        return WithdrawRequestsTable::configure($table);
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit($record): bool
    {
        return false;
    }

    public static function canDelete($record): bool
    {
        return false;
    }

    public static function getPages(): array
    {
        return [
            'index' => ListWithdrawRequests::route('/'),
        ];
    }
}
