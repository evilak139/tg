<?php

namespace App\Console\Commands;

use App\Enums\BroadcastStatus;
use App\Enums\MessageTemplateType;
use App\Enums\PointsChangeType;
use App\Models\BroadcastTask;
use App\Models\LeaderboardSnapshot;
use App\Models\MessageTemplate;
use App\Models\User;
use App\Services\PointsConfigRepository;
use App\Services\PointsService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * 对应04文档"6. 月度邀请排行榜结算与推送"：每月1号凌晨结算上一自然月。
 */
#[Signature('app:settle-monthly-leaderboard')]
#[Description('结算上一自然月的邀请排行榜并推送给全体会员')]
class SettleMonthlyLeaderboard extends Command
{
    public function handle(PointsService $pointsService, PointsConfigRepository $config): void
    {
        $lastMonth = now()->subMonthNoOverflow();
        $period = $lastMonth->format('Y-m');

        if (LeaderboardSnapshot::query()->where('period', $period)->exists()) {
            $this->warn("排行榜（{$period}）已经结算过，跳过。");

            return;
        }

        $topN = $config->getInt('leaderboard_top_n', 10);
        $rewardPoints = $config->getInt('leaderboard_reward_points', 0);

        $rankings = DB::table('users')
            ->select('invited_by_l1', DB::raw('count(*) as cnt'))
            ->whereNotNull('invited_by_l1')
            ->whereBetween('register_time', [$lastMonth->copy()->startOfMonth(), $lastMonth->copy()->endOfMonth()])
            ->groupBy('invited_by_l1')
            ->orderByDesc('cnt')
            ->limit($topN)
            ->get();

        $rank = 1;

        foreach ($rankings as $row) {
            LeaderboardSnapshot::create([
                'period' => $period,
                'user_id' => $row->invited_by_l1,
                'rank' => $rank,
                'invite_count_this_period' => $row->cnt,
                'reward_points' => $rewardPoints,
            ]);

            if ($rewardPoints > 0) {
                $user = User::find($row->invited_by_l1);

                if ($user !== null) {
                    $pointsService->award($user, PointsChangeType::LeaderboardBonus, $rewardPoints);
                }
            }

            $rank++;
        }

        $this->broadcastToAll();

        $this->info("排行榜（{$period}）结算完成，{$rankings->count()}人上榜。");
    }

    /**
     * 对应04文档："推送给全体会员，不是只通知上榜者"。
     */
    protected function broadcastToAll(): void
    {
        $template = MessageTemplate::query()->where('type', MessageTemplateType::MonthlyLeaderboard)->first();

        if ($template === null) {
            return;
        }

        BroadcastTask::create([
            'template_id' => $template->id,
            'target_filter' => ['scope' => 'all'],
            'scheduled_time' => now(),
            'status' => BroadcastStatus::Pending,
            'created_by' => 'system',
        ]);
    }
}
