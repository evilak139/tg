<?php

namespace App\Filament\Widgets;

use App\Enums\WithdrawStatus;
use App\Models\Bot;
use App\Models\BroadcastTask;
use App\Models\Checkin;
use App\Models\Domain;
use App\Models\PointsLedger;
use App\Models\User;
use App\Models\WithdrawRequest;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

/**
 * 对应03.1文档"数据面板"的统计卡片部分。
 *
 * TODO(需确认): 次日/7日留存率——01文档没有设计按天记录的活跃日志表，users.last_active_time
 * 只保留"最近一次"，没法反推某个历史用户在"注册后第N天"是否活跃过。这里用checkins表
 * （唯一有精确日期记录的行为表）作为"活跃"的代理口径：cohort用户中，在
 * 注册日+1天/+7天当天有签到记录的人数占比。如果用户只邀请不签到，会被计入"未留存"，
 * 这是数据结构上的近似，不是精确的"任意活跃行为"留存率。
 *
 * TODO(需确认): K因子的"本周期"文档未指明具体周期长度，这里取"本自然月"，与04文档
 * 月度排行榜的统计周期保持一致。
 */
class DashboardStatsOverview extends StatsOverviewWidget
{
    protected function getStats(): array
    {
        return [
            ...$this->memberStats(),
            ...$this->activityStats(),
            ...$this->retentionStats(),
            $this->kFactorStat(),
            ...$this->pointsStats(),
            $this->exchangeStat(),
            $this->broadcastStat(),
            $this->activeBotStat(),
            $this->domainHealthStat(),
        ];
    }

    protected function memberStats(): array
    {
        $today = now()->toDateString();
        $yesterday = now()->subDay()->toDateString();

        return [
            Stat::make('总会员数', User::query()->count()),
            Stat::make('今日新增', User::query()->whereDate('register_time', $today)->count()),
            Stat::make('昨日新增', User::query()->whereDate('register_time', $yesterday)->count()),
        ];
    }

    protected function activityStats(): array
    {
        return [
            Stat::make('日活', User::query()->where('last_active_time', '>=', now()->startOfDay())->count()),
            Stat::make('周活', User::query()->where('last_active_time', '>=', now()->subDays(7))->count()),
            Stat::make('月活', User::query()->where('last_active_time', '>=', now()->subDays(30))->count()),
        ];
    }

    protected function retentionStats(): array
    {
        return [
            Stat::make('次日留存率', $this->retentionRate(1).'%'),
            Stat::make('7日留存率', $this->retentionRate(7).'%'),
        ];
    }

    protected function retentionRate(int $days): string
    {
        $cohortDate = now()->subDays($days + 1)->toDateString();

        $cohortIds = User::query()->whereDate('register_time', $cohortDate)->pluck('id');

        if ($cohortIds->isEmpty()) {
            return '0.0';
        }

        $retainedCount = Checkin::query()
            ->whereIn('user_id', $cohortIds)
            ->whereDate('checkin_date', now()->subDays($days)->toDateString())
            ->distinct('user_id')
            ->count('user_id');

        return number_format(($retainedCount / $cohortIds->count()) * 100, 1);
    }

    protected function kFactorStat(): Stat
    {
        $monthStart = now()->startOfMonth();
        $monthEnd = now()->endOfMonth();

        $inviteesThisMonth = User::query()
            ->whereBetween('register_time', [$monthStart, $monthEnd])
            ->whereNotNull('invited_by_l1')
            ->count();

        $activeOldUsers = User::query()
            ->where('register_time', '<', $monthStart)
            ->where('last_active_time', '>=', $monthStart)
            ->count();

        $kFactor = $activeOldUsers > 0 ? round($inviteesThisMonth / $activeOldUsers, 3) : 0;

        return Stat::make('K因子（本月）', (string) $kFactor);
    }

    protected function pointsStats(): array
    {
        $today = now()->toDateString();
        $yesterday = now()->subDay()->toDateString();

        $base = PointsLedger::query()->where('amount', '>', 0);

        return [
            Stat::make('累计发放积分', (clone $base)->sum('amount')),
            Stat::make('今日发放积分', (clone $base)->whereDate('created_at', $today)->sum('amount')),
            Stat::make('昨日发放积分', (clone $base)->whereDate('created_at', $yesterday)->sum('amount')),
        ];
    }

    protected function exchangeStat(): Stat
    {
        $exchanged = WithdrawRequest::query()->where('status', WithdrawStatus::Completed)->sum('points_amount');
        $outstanding = User::query()->sum('points_balance');

        return Stat::make('已兑换 / 未兑换积分余额', "{$exchanged} / {$outstanding}");
    }

    protected function broadcastStat(): Stat
    {
        $totals = BroadcastTask::query()->selectRaw('sum(sent_count) as sent, sum(total_target_count) as target, sum(click_count) as clicks')->first();

        $deliveryRate = ($totals->target ?? 0) > 0 ? round($totals->sent / $totals->target * 100, 1) : 0;
        $clickRate = ($totals->sent ?? 0) > 0 ? round($totals->clicks / $totals->sent * 100, 1) : 0;

        return Stat::make('群发送达率 / 点击率', "{$deliveryRate}% / {$clickRate}%");
    }

    protected function activeBotStat(): Stat
    {
        $bot = Bot::query()->where('is_active', true)->first();

        if ($bot === null) {
            return Stat::make('当前生效机器人', '未配置')->color('danger');
        }

        return Stat::make('当前生效机器人', "{$bot->bot_username}（{$bot->status->value}）")
            ->color($bot->status->value === '启用' ? 'success' : 'danger');
    }

    protected function domainHealthStat(): Stat
    {
        $counts = Domain::query()->selectRaw('status, count(*) as c')->groupBy('status')->pluck('c', 'status');

        $summary = $counts->map(fn ($c, $status) => "{$status}:{$c}")->implode(' ');

        return Stat::make('域名池健康状态', $summary === '' ? '暂无域名' : $summary);
    }
}
