<?php

namespace App\Filament\Exports;

use App\Models\PointsLedger;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Exporter;
use Filament\Actions\Exports\Models\Export;
use Illuminate\Support\Number;

class PointsLedgerExporter extends Exporter
{
    protected static ?string $model = PointsLedger::class;

    public static function getColumns(): array
    {
        return [
            ExportColumn::make('id')->label('ID'),
            ExportColumn::make('user.tg_user_id')->label('会员Telegram ID'),
            ExportColumn::make('user.nickname')->label('会员昵称'),
            ExportColumn::make('change_type')->label('变动类型')->formatStateUsing(fn ($state) => $state?->value ?? $state),
            ExportColumn::make('amount')->label('变动数量'),
            ExportColumn::make('balance_after')->label('变动后余额'),
            ExportColumn::make('related_user.nickname')->label('关联下级'),
            ExportColumn::make('operator')->label('操作人'),
            ExportColumn::make('created_at')->label('时间'),
        ];
    }

    public static function getCompletedNotificationBody(Export $export): string
    {
        $body = '账变记录导出完成，共导出 '.Number::format($export->successful_rows).' 条。';

        if ($failedRowsCount = $export->getFailedRowsCount()) {
            $body .= ' '.Number::format($failedRowsCount).' 条导出失败。';
        }

        return $body;
    }
}
