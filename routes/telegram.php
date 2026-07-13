<?php

/** @var Nutgram $bot */

use App\Telegram\Handlers\CheckinMenuHandler;
use App\Telegram\Handlers\InviteMenuHandler;
use App\Telegram\Handlers\PointsHistoryHandler;
use App\Telegram\Handlers\ProfileMenuHandler;
use App\Telegram\Handlers\StartHandler;
use App\Telegram\Handlers\WithdrawMenuHandler;
use App\Telegram\Handlers\WithdrawSubmitHandler;
use App\Telegram\Middleware\ResolveMember;
use SergiX44\Nutgram\Nutgram;

/*
|--------------------------------------------------------------------------
| Nutgram Handlers
|--------------------------------------------------------------------------
|
| 对应02文档"会员端功能规格"：/start 注册 + 主菜单四个入口（邀请/签到/提现/我的）。
|
*/

$bot->middleware(ResolveMember::class);

// Nutgram的onCommand按精确正则匹配（/^\/start$/），带参数的深链接 "/start {payload}"
// 必须单独注册一条parameterized pattern才能匹配，否则 "/start 123" 这种带邀请payload
// 的deep link会直接匹配不到任何handler、静默丢弃。两条都指向同一个Handler，
// payload由StartHandler自己从原始文本里解析，不依赖Nutgram的具名参数注入。
$bot->onCommand('start', StartHandler::class)->description('开始使用');
$bot->onCommand('start {payload}', StartHandler::class);

$bot->onCallbackQueryData('menu:invite', InviteMenuHandler::class);
$bot->onCallbackQueryData('menu:checkin', CheckinMenuHandler::class);
$bot->onCallbackQueryData('menu:withdraw', WithdrawMenuHandler::class);
$bot->onCallbackQueryData('menu:profile', ProfileMenuHandler::class);
$bot->onCallbackQueryData('withdraw:submit', WithdrawSubmitHandler::class);
$bot->onCallbackQueryData('profile:ledger', PointsHistoryHandler::class);
