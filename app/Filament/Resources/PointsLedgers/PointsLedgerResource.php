<?php

namespace App\Filament\Resources\PointsLedgers;

use App\Filament\Resources\PointsLedgers\Pages\ListPointsLedgers;
use App\Filament\Resources\PointsLedgers\Tables\PointsLedgersTable;
use App\Models\PointsLedger;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

/**
 * 对应03.5文档"账变记录"：points_ledger全量流水，只读，不提供后台手动增删改
 * （所有积分变动都必须走PointsService，保证ledger与points_monthly_batches一致）。
 */
class PointsLedgerResource extends Resource
{
    protected static ?string $model = PointsLedger::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedListBullet;

    protected static ?string $navigationLabel = '账变记录';

    protected static ?string $modelLabel = '账变记录';

    public static function table(Table $table): Table
    {
        return PointsLedgersTable::configure($table);
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
            'index' => ListPointsLedgers::route('/'),
        ];
    }
}
