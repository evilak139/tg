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
     * "一键部署"：把后台配置的命令菜单说明（"机器人菜单"页面，见ManageBotMenu）推送到
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
     *
     * 注意：切换只改数据库字段，短链跳转（HTTP请求，每次都重新查库）会立即生效，
     * 但机器人长轮询进程（nutgram:run）是常驻进程，启动时只读一次当前生效机器人的
     * token（见AppServiceProvider::useActiveBotToken()），运行期间不会感知这次切换，
     * 必须手动重启该进程才会真正开始接管新机器人——排查过一次"切换了但菜单消失"，
     * 就是没重启这个进程（见部署排障记录），这里在确认弹窗和成功提示里都显式提醒。
     */
    protected static function activateAction(): Action
    {
        return Action::make('activate')
            ->label('设为当前生效')
            ->icon('heroicon-o-bolt')
            ->color('warning')
            ->visible(fn (Bot $record) => ! $record->is_active)
            ->requiresConfirmation()
            ->modalDescription('切换后短链跳转会立即指向这个机器人，历史短链无需重新生成。但机器人长轮询进程不会自动感知这次切换，切换后必须手动重启该进程（服务器上用宝塔"进程守护管理器"点重启，不要手动kill），否则新机器人不会有任何响应。')
            ->action(function (Bot $record) {
                DB::transaction(function () use ($record) {
                    Bot::query()->where('is_active', true)->update(['is_active' => false]);
                    $record->update(['is_active' => true]);
                });

                Notification::make()
                    ->title('已切换当前生效机器人')
                    ->body('别忘了去服务器重启机器人长轮询进程，否则新机器人暂时不会有任何响应。')
                    ->warning()
                    ->persistent()
                    ->send();
            });
    }
}
