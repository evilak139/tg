<?php

namespace App\Jobs;

use App\Enums\BroadcastStatus;
use App\Models\BroadcastTask;
use App\Services\BroadcastTargetResolver;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Bus;

/**
 * 对应04文档"7. 群发任务队列消费"：解析target_filter得到目标用户列表，
 * 逐条发送（走限速队列，见SendBroadcastMessageJob），全部处理完后标记已完成。
 *
 * 用Laravel的Job Batch而不是自己维护"已处理计数"字段：batch的finally()回调
 * 保证不管每条消息发送成功还是失败，全部跑完后都会触发一次，这正是判定
 * "已完成"的正确时机（01文档broadcast_tasks表没有设计"已处理数"这种字段，
 * 用batch内建机制可以避免额外加字段）。
 */
class DispatchBroadcastTaskJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public int $broadcastTaskId) {}

    public function handle(BroadcastTargetResolver $resolver): void
    {
        $task = BroadcastTask::find($this->broadcastTaskId);

        if ($task === null || $task->status !== BroadcastStatus::Pending) {
            return;
        }

        $targets = $resolver->resolve($task->target_filter ?? []);

        if ($targets->isEmpty()) {
            $task->update([
                'status' => BroadcastStatus::Failed,
                'failure_reason' => '没有匹配的目标用户',
            ]);

            return;
        }

        $task->update([
            'status' => BroadcastStatus::Sending,
            'total_target_count' => $targets->count(),
        ]);

        $jobs = $targets->map(fn ($user) => new SendBroadcastMessageJob($task->id, $user->id))->all();

        Bus::batch($jobs)
            ->name("broadcast-task-{$task->id}")
            ->finally(function () use ($task) {
                BroadcastTask::where('id', $task->id)
                    ->where('status', BroadcastStatus::Sending)
                    ->update(['status' => BroadcastStatus::Completed]);
            })
            ->dispatch();
    }
}
