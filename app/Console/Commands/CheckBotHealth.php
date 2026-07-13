<?php

namespace App\Console\Commands;

use App\Enums\AdminRole;
use App\Enums\EnableStatus;
use App\Models\AdminUser;
use App\Models\Bot;
use Filament\Notifications\Notification;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use SergiX44\Nutgram\Nutgram;
use Throwable;

/**
 * 对应04文档"1. 机器人健康检测"：逐一调用getMe，失败置异常；若失败的正是当前生效
 * 机器人，站内告警给超级管理员（03.8机器人配置仅超级管理员可管）。
 */
#[Signature('app:check-bot-health')]
#[Description('检测全部机器人Token是否仍然有效')]
class CheckBotHealth extends Command
{
    public function handle(): void
    {
        $bots = Bot::all();

        foreach ($bots as $bot) {
            $this->checkOne($bot);
        }

        $this->info("已检测 {$bots->count()} 个机器人。");
    }

    protected function checkOne(Bot $bot): void
    {
        $healthy = $this->pingBot($bot);

        if ($healthy) {
            $wasAbnormal = $bot->status === EnableStatus::Abnormal;

            $bot->update([
                'last_health_check_time' => now(),
                'status' => $wasAbnormal ? EnableStatus::Enabled : $bot->status,
            ]);

            return;
        }

        $wasActive = $bot->is_active;

        $bot->update([
            'last_health_check_time' => now(),
            'status' => EnableStatus::Abnormal,
        ]);

        if ($wasActive) {
            $this->alertSuperAdmins($bot);
        }
    }

    protected function pingBot(Bot $bot): bool
    {
        try {
            return (new Nutgram($bot->token))->getMe() !== null;
        } catch (Throwable) {
            return false;
        }
    }

    protected function alertSuperAdmins(Bot $bot): void
    {
        $superAdmins = AdminUser::query()->where('role', AdminRole::SuperAdmin)->get();

        Notification::make()
            ->danger()
            ->title('当前生效机器人异常')
            ->body("机器人 {$bot->bot_username} 的Token健康检测失败，需要人工切换备用机器人。")
            ->sendToDatabase($superAdmins);
    }
}
