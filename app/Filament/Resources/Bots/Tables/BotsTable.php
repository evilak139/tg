<?php

namespace App\Filament\Resources\Bots\Tables;

use App\Models\Bot;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Facades\DB;

class BotsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('bot_username')->label('Bot用户名')->searchable(),
                TextColumn::make('status')->label('状态')->badge(),
                IconColumn::make('is_active')->label('当前生效')->boolean(),
                TextColumn::make('last_health_check_time')->label('最近健康检测')->dateTime()->sortable(),
            ])
            ->recordActions([
                self::activateAction(),
                EditAction::make(),
            ]);
    }

    /**
     * 对应03.8文档："当前生效标记：列表中可将某条记录设为当前生效，系统需保证同一时刻
     * 只有一条记录为true（切换时自动将旧记录置为false）"。
     */
    protected static function activateAction(): Action
    {
        return Action::make('activate')
            ->label('设为当前生效')
            ->icon('heroicon-o-bolt')
            ->color('warning')
            ->visible(fn (Bot $record) => ! $record->is_active)
            ->requiresConfirmation()
            ->modalDescription('切换后短链跳转会立即指向这个机器人，历史短链无需重新生成。')
            ->action(function (Bot $record) {
                DB::transaction(function () use ($record) {
                    Bot::query()->where('is_active', true)->update(['is_active' => false]);
                    $record->update(['is_active' => true]);
                });

                Notification::make()->title('已切换当前生效机器人')->success()->send();
            });
    }
}
