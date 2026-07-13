<?php

namespace App\Filament\Resources\BroadcastTasks;

use App\Enums\BroadcastStatus;
use App\Filament\Resources\BroadcastTasks\Pages\CreateBroadcastTask;
use App\Filament\Resources\BroadcastTasks\Pages\EditBroadcastTask;
use App\Filament\Resources\BroadcastTasks\Pages\ListBroadcastTasks;
use App\Filament\Resources\BroadcastTasks\Schemas\BroadcastTaskForm;
use App\Filament\Resources\BroadcastTasks\Tables\BroadcastTasksTable;
use App\Models\BroadcastTask;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

/**
 * 对应03.6文档"群发消息系统"。任务一旦开始发送（status不再是待发送），
 * 目标名单/模板/时间就不该再改，只允许在"待发送"阶段编辑或删除。
 */
class BroadcastTaskResource extends Resource
{
    protected static ?string $model = BroadcastTask::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedMegaphone;

    protected static ?string $navigationLabel = '群发消息';

    protected static ?string $modelLabel = '群发任务';

    protected static UnitEnum|string|null $navigationGroup = '系统管理';

    public static function form(Schema $schema): Schema
    {
        return BroadcastTaskForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return BroadcastTasksTable::configure($table);
    }

    public static function canEdit($record): bool
    {
        return $record->status === BroadcastStatus::Pending;
    }

    public static function canDelete($record): bool
    {
        return $record->status === BroadcastStatus::Pending;
    }

    public static function getPages(): array
    {
        return [
            'index' => ListBroadcastTasks::route('/'),
            'create' => CreateBroadcastTask::route('/create'),
            'edit' => EditBroadcastTask::route('/{record}/edit'),
        ];
    }
}
