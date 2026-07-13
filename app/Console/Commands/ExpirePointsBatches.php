<?php

namespace App\Console\Commands;

use App\Enums\BatchStatus;
use App\Jobs\SendPointsExpiryReminderJob;
use App\Models\PointsMonthlyBatch;
use App\Services\PointsService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

/**
 * 对应04文档"4. 积分月度批次过期处理"：过期批次清零 + 提前7天到期提醒。
 */
#[Signature('app:expire-points-batches')]
#[Description('清零已过期的积分批次，并提醒即将到期的批次')]
class ExpirePointsBatches extends Command
{
    public function handle(PointsService $pointsService): void
    {
        $this->expireDueBatches($pointsService);
        $this->sendUpcomingExpiryReminders();
    }

    protected function expireDueBatches(PointsService $pointsService): void
    {
        $batches = PointsMonthlyBatch::query()
            ->where('status', BatchStatus::Active)
            ->where('expire_at', '<=', now())
            ->get();

        foreach ($batches as $batch) {
            $pointsService->expireBatch($batch);
        }

        $this->info("处理了 {$batches->count()} 个过期批次。");
    }

    protected function sendUpcomingExpiryReminders(): void
    {
        $batches = PointsMonthlyBatch::query()
            ->where('status', BatchStatus::Active)
            ->whereBetween('expire_at', [now(), now()->addDays(7)])
            ->whereColumn('points_earned_total', '>', 'points_consumed_total')
            ->get();

        foreach ($batches as $batch) {
            SendPointsExpiryReminderJob::dispatch($batch->user_id, $batch->id);
        }

        $this->info("发送了 {$batches->count()} 条到期提醒。");
    }
}
