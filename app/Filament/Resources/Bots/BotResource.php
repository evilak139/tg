<?php

namespace App\Filament\Resources\Bots;

use App\Filament\Resources\Bots\Pages\CreateBot;
use App\Filament\Resources\Bots\Pages\EditBot;
use App\Filament\Resources\Bots\Pages\ListBots;
use App\Filament\Resources\Bots\Schemas\BotForm;
use App\Filament\Resources\Bots\Tables\BotsTable;
use App\Models\Bot;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

/**
 * 对应03.8文档"机器人配置"，仅超级管理员可操作（见03.6文档）。
 */
class BotResource extends Resource
{
    protected static ?string $model = Bot::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCpuChip;

    protected static ?string $navigationLabel = '机器人配置';

    protected static ?string $modelLabel = '机器人';

    protected static UnitEnum|string|null $navigationGroup = '系统管理';

    public static function form(Schema $schema): Schema
    {
        return BotForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return BotsTable::configure($table);
    }

    public static function canViewAny(): bool
    {
        return auth()->user()?->isSuperAdmin() ?? false;
    }

    public static function canCreate(): bool
    {
        return auth()->user()?->isSuperAdmin() ?? false;
    }

    public static function canEdit($record): bool
    {
        return auth()->user()?->isSuperAdmin() ?? false;
    }

    public static function canDelete($record): bool
    {
        return auth()->user()?->isSuperAdmin() ?? false;
    }

    public static function getPages(): array
    {
        return [
            'index' => ListBots::route('/'),
            'create' => CreateBot::route('/create'),
            'edit' => EditBot::route('/{record}/edit'),
        ];
    }
}
