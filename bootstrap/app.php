<?php

use App\Http\Middleware\EnsureInstalled;
use App\Http\Middleware\UseFileSessionsUntilMigrated;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // UseFileSessionsUntilMigrated必须在StartSession之前跑（prepend），否则sessions表
        // 还没建好时Laravel自己保存session都会报错，见该中间件的注释。
        $middleware->web(prepend: [UseFileSessionsUntilMigrated::class]);

        // 对应07文档：装完前强制走/install，装完后/install失效，见EnsureInstalled。
        // Filament的/admin/*路由走自己的中间件栈（见AdminPanelProvider），这里只覆盖
        // routes/web.php这类普通web路由，/admin那边另外挂了一份。
        $middleware->web(append: [EnsureInstalled::class]);
    })
    ->withSchedule(function (Schedule $schedule): void {
        // 对应04文档全部定时任务
        $schedule->command('app:dispatch-due-broadcast-tasks')->everyMinute(); // 7. 群发任务队列消费
        $schedule->command('app:check-bot-health')->hourly(); // 1. 机器人健康检测
        $schedule->command('app:check-domain-health')->hourly(); // 2. 域名健康检测
        $schedule->command('app:update-activity-tags')->dailyAt('03:00'); // 3. 会员活跃度层级更新（凌晨低峰期）
        $schedule->command('app:expire-points-batches')->dailyAt('03:30'); // 4. 积分月度批次过期处理
        $schedule->command('app:settle-monthly-leaderboard')->monthlyOn(1, '00:30'); // 6. 月度邀请排行榜结算
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*'),
        );
    })->create();
