<?php

namespace App\Jobs;

use App\Enums\MessageTemplateType;
use App\Models\PointsMonthlyBatch;
use App\Models\User;
use App\Services\MessageTemplateRenderer;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\RateLimited;
use Illuminate\Queue\SerializesModels;
use SergiX44\Nutgram\Nutgram;

/**
 * 对应04文档"4. 积分月度批次过期处理"的到期提醒部分。走私聊直发而不是broadcast_tasks
 * 队列，因为"到期积分数"“到期日期"是每个用户不同的批次数据，broadcast_tasks那套
 * 群发机制只按同一个模板对全体目标渲染同一份文案，没法承载这种逐人不同的变量。
 * 仍然复用同一个限速中间件，遵守06文档"约每秒30条"的要求。
 */
class SendPointsExpiryReminderJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public int $userId, public int $batchId) {}

    /**
     * @return array<object>
     */
    public function middleware(): array
    {
        return [new RateLimited('telegram-broadcast')];
    }

    public function handle(Nutgram $bot, MessageTemplateRenderer $renderer): void
    {
        $user = User::find($this->userId);
        $batch = PointsMonthlyBatch::find($this->batchId);

        if ($user === null || $batch === null) {
            return;
        }

        $remaining = $batch->points_earned_total - $batch->points_consumed_total;

        if ($remaining <= 0) {
            return;
        }

        $rendered = $renderer->render(MessageTemplateType::PointsExpiry, $user, [
            'pontos_a_expirar' => (string) $remaining,
            'data_expiracao' => $batch->expire_at->format('Y-m-d'),
        ]);

        $bot->sendMessage($rendered['text'], chat_id: $user->tg_user_id);
    }
}
