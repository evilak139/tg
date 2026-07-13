<?php

namespace App\Filament\Resources\Bots\Tables;

use App\Models\Bot;
use App\Services\BotDeploymentService;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class BotsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('bot_username')->label('Bot用户名')->searchable(),
                TextColumn::make('status')->label('状态')->badge(),
                IconColumn::make('is_active')->label('当前生效')->boolean(),
                TextColumn::make('last_health_check_time')->label('最近健康检测')->dateTime('Y-m-d H:i:s')->sortable(),
            ])
            ->recordActions([
                self::deployAction(),
                self::activateAction(),
                EditAction::make(),
            ]);
    }

    /**
     * "一键部署"：把后台配置的命令菜单说明（积分配置页面的"机器人菜单"部分）推送到
     * Telegram，不用手动跑@BotFather，保存Token后点一下这个按钮机器人就能直接用。
     */
    protected static function deployAction(): Action
    {
        return Action::make('deploy')
            ->label('一键部署')
            ->icon('heroicon-o-rocket-launch')
            ->color('primary')
            ->action(function (Bot $record, BotDeploymentService $deployer) {
                try {
                    $username = $deployer->deploy($record);
                    Notification::make()->title("部署成功：@{$username}")->success()->send();
                } catch (RuntimeException $e) {
                    Notification::make()->title($e->getMessage())->danger()->send();
                }
            });
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
