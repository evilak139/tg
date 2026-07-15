<?php

namespace App\Telegram\Handlers;

use App\Models\BroadcastTask;
use App\Models\User;
use SergiX44\Nutgram\Nutgram;

/**
 * 对应03.1文档"群发消息...点击率"：消息内追踪按钮被点击时更新click_count。
 */
class BroadcastClickHandler
{
    public function __invoke(Nutgram $bot): void
    {
        $taskId = (int) str_replace('broadcast_click:', '', $bot->callbackQuery()->data);

        BroadcastTask::where('id', $taskId)->increment('click_count');

        /** @var User $user */
        $user = $bot->get('member');

        $bot->answerCallbackQuery(text: "Pontos atuais: {$user->points_balance}", show_alert: true);
    }
}
