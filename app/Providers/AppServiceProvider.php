<?php

namespace App\Providers;

use App\Enums\EnableStatus;
use App\Models\Bot;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // 故意不注册成singleton：机器人长轮询进程是一次性启动、常驻处理多条更新的
        // （见00文档"进程管理"），如果这里绑成singleton，第一条消息之后points_config
        // 的内存缓存就再也不会刷新，管理员在后台改的签到积分/菜单按钮文案等要重启
        // 轮询进程才生效。points_config就几十行，每次都查一遍代价可以忽略。
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->useActiveBotToken();
        $this->configureBroadcastRateLimit();
    }

    /**
     * 对应06/00文档："群发限速用Laravel Queue自带的RateLimited中间件卡住发送速率"，
     * 约每秒30条到不同用户，见SendBroadcastMessageJob。
     */
    protected function configureBroadcastRateLimit(): void
    {
        RateLimiter::for('telegram-broadcast', fn () => Limit::perSecond(30));
    }

    /**
     * 对应CLAUDE.md不可违反的设计约束："Bot Token 加密存储，不要明文入库或写进 .env"，
     * 以及01文档"bots 表任何时刻只能有一条 is_active=true"。Nutgram 的 token 默认读
     * config('nutgram.token')（即 .env 里的 NUTGRAM_TOKEN），这里在容器还没解析出
     * Nutgram单例之前把它替换成当前生效机器人的（已解密）token，实现"切换生效机器人
     * 不需要重启服务"（对应03.8文档）。安装向导跑之前 bots 表还不存在，做好防御不报错。
     */
    protected function useActiveBotToken(): void
    {
        if (! Schema::hasTable('bots')) {
            return;
        }

        $token = Bot::query()->where('is_active', true)->where('status', EnableStatus::Enabled)->first()?->token;

        if ($token !== null) {
            config(['nutgram.token' => $token]);
        }
    }
}
