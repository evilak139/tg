<?php

namespace App\Providers;

use App\Enums\EnableStatus;
use App\Models\Bot;
use App\Services\PointsConfigRepository;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(PointsConfigRepository::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->useActiveBotToken();
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
