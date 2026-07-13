<?php

namespace App\Jobs;

use App\Models\BroadcastTask;
use App\Models\User;
use App\Services\MessageTemplateRenderer;
use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\RateLimited;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use SergiX44\Nutgram\Nutgram;
use SergiX44\Nutgram\Telegram\Types\Keyboard\InlineKeyboardButton;
use SergiX44\Nutgram\Telegram\Types\Keyboard\InlineKeyboardMarkup;
use Throwable;

/**
 * 单条群发消息发送，对应06/00文档"群发限速...约每秒30条到不同用户，必须走队列限速发送"。
 * 消息带一个"查看我的积分"追踪按钮，对应03.1文档"群发消息...点击率"统计需要的点击信号。
 */
class SendBroadcastMessageJob implements ShouldQueue
{
    use Batchable, Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public int $broadcastTaskId, public int $userId) {}

    /**
     * @return array<object>
     */
    public function middleware(): array
    {
        return [new RateLimited('telegram-broadcast')];
    }

    public function handle(Nutgram $bot, MessageTemplateRenderer $renderer): void
    {
        if ($this->batch()?->cancelled()) {
            return;
        }

        $task = BroadcastTask::find($this->broadcastTaskId);
        $user = User::find($this->userId);

        if ($task === null || $user === null) {
            return;
        }

        try {
            $rendered = $renderer->render($task->template->type, $user);
            $keyboard = InlineKeyboardMarkup::make()->addRow(
                InlineKeyboardButton::make(text: '查看我的积分', callback_data: "broadcast_click:{$task->id}")
            );

            if (filled($rendered['image_url'])) {
                $bot->sendPhoto(
                    photo: $rendered['image_url'],
                    chat_id: $user->tg_user_id,
                    caption: $rendered['text'],
                    reply_markup: $keyboard,
                );
            } else {
                $bot->sendMessage($rendered['text'], chat_id: $user->tg_user_id, reply_markup: $keyboard);
            }

            BroadcastTask::where('id', $task->id)->increment('sent_count');
        } catch (Throwable $e) {
            Log::warning("群发任务#{$task->id}发送给用户#{$user->id}失败：{$e->getMessage()}");
        }
    }
}
