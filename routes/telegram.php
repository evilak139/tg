<?php

/** @var Nutgram $bot */

use App\Telegram\Handlers\BroadcastClickHandler;
use App\Telegram\Handlers\CheckinMenuHandler;
use App\Telegram\Handlers\InviteMenuHandler;
use App\Telegram\Handlers\PointsHistoryHandler;
use App\Telegram\Handlers\ProfileMenuHandler;
use App\Telegram\Handlers\StartHandler;
use App\Telegram\Handlers\WithdrawMenuHandler;
use App\Telegram\Handlers\WithdrawSubmitHandler;
use App\Telegram\Middleware\ResolveMember;
use Illuminate\Support\Facades\Log;
use SergiX44\Nutgram\Nutgram;

/*
|--------------------------------------------------------------------------
| Nutgram Handlers
|--------------------------------------------------------------------------
|
| 对应02文档"会员端功能规格"：/start 注册 + 主菜单四个入口（邀请/签到/提现/我的）。
|
*/

// 全局兜底：任何handler内部抛出的异常，Nutgram自身的Polling循环默认只会
// fwrite到STDERR（见vendor/nutgram/nutgram/src/RunningMode/Polling.php），
// 不会进Laravel的日志系统，config('nutgram.log_channel')也管不到这条路径，
// 导致生产环境出问题时后台管理员完全无感知（排查过一次，见部署排障记录）。
// 这里显式接管，统一落到Laravel默认日志通道（不依赖nutgram.log_channel配置），
// 保证以后任何一个handler出错都能在 storage/logs/laravel.log 里找到。
$bot->onException(function (Nutgram $bot, Throwable $exception) {
    $update = $bot->update();

    Log::channel('single')->error('机器人处理消息时出现未捕获异常', [
        // update_id是typed property，未初始化时直接访问会再抛一次错，isset()对
        // 未初始化的typed property是安全的（返回false而不抛错），所以用它来判断。
        'update_id' => ($update !== null && isset($update->update_id)) ? $update->update_id : null,
        'message' => $exception->getMessage(),
        'file' => $exception->getFile(),
        'line' => $exception->getLine(),
        'trace' => $exception->getTraceAsString(),
    ]);

    try {
        $bot->sendMessage('Ocorreu um problema temporário no serviço, tente novamente mais tarde.');
    } catch (Throwable) {
        // 兜底通知本身失败也不再往外抛，避免二次异常又走一遍这个handler。
    }
});

$bot->middleware(ResolveMember::class);

// Nutgram的onCommand按精确正则匹配（/^\/start$/），带参数的深链接 "/start {payload}"
// 必须单独注册一条parameterized pattern才能匹配，否则 "/start 123" 这种带邀请payload
// 的deep link会直接匹配不到任何handler、静默丢弃。两条都指向同一个Handler，
// payload由StartHandler自己从原始文本里解析，不依赖Nutgram的具名参数注入。
$bot->onCommand('start', StartHandler::class)->description('Começar a usar');
$bot->onCommand('start {payload}', StartHandler::class);

$bot->onCallbackQueryData('menu:invite', InviteMenuHandler::class);
$bot->onCallbackQueryData('menu:checkin', CheckinMenuHandler::class);
$bot->onCallbackQueryData('menu:withdraw', WithdrawMenuHandler::class);
$bot->onCallbackQueryData('menu:profile', ProfileMenuHandler::class);
$bot->onCallbackQueryData('withdraw:submit', WithdrawSubmitHandler::class);
$bot->onCallbackQueryData('profile:ledger', PointsHistoryHandler::class);
$bot->onCallbackQueryData('broadcast_click:{taskId}', BroadcastClickHandler::class);
