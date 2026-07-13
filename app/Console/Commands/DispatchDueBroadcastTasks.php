<?php

namespace App\Console\Commands;

use App\Enums\BroadcastStatus;
use App\Jobs\DispatchBroadcastTaskJob;
use App\Models\BroadcastTask;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

/**
 * 对应04文档"7. 群发任务队列消费"："持续消费broadcast_tasks中status=待发送
 * 且scheduled_time<=当前时间的任务"。这个命令本身很轻量，只负责"挑出到点的任务
 * 扔进队列"，真正逐条发送、限速的逻辑在DispatchBroadcastTaskJob/SendBroadcastMessageJob。
 */
#[Signature('app:dispatch-due-broadcast-tasks')]
#[Description('扫描到期的群发任务并投递到队列')]
class DispatchDueBroadcastTasks extends Command
{
    public function handle(): void
    {
        $tasks = BroadcastTask::query()
            ->where('status', BroadcastStatus::Pending)
            ->where('scheduled_time', '<=', now())
            ->get();

        foreach ($tasks as $task) {
            DispatchBroadcastTaskJob::dispatch($task->id);
        }

        $this->info("已投递 {$tasks->count()} 个到期群发任务。");
    }
}
