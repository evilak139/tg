<?php

namespace App\Telegram\Middleware;

use App\Models\User;
use SergiX44\Nutgram\Nutgram;

/**
 * 全局中间件：把当前Telegram用户解析成本地 App\Models\User 并放进 $bot->set('member', ...)
 * 供后续Handler直接取用。/start 命令本身负责注册（见02文档"/start处理逻辑"），
 * 因此这里放行 /start，不在中间件里重复建号；其余任何交互若查不到对应用户
 * （例如从未 /start 过就点了历史消息里的按钮），提示先 /start，不继续往下传。
 */
class ResolveMember
{
    public function __invoke(Nutgram $bot, callable $next): void
    {
        $text = $bot->message()?->text;

        if ($text !== null && str_starts_with(ltrim($text), '/start')) {
            $next($bot);

            return;
        }

        $tgUserId = $bot->userId();

        if ($tgUserId === null) {
            $next($bot);

            return;
        }

        $user = User::query()->where('tg_user_id', $tgUserId)->first();

        if ($user === null) {
            $bot->sendMessage('Envie /start primeiro para começar a usar');

            return;
        }

        $bot->set('member', $user);

        $next($bot);
    }
}
