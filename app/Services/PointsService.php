<?php

namespace App\Services;

use App\Enums\BatchStatus;
use App\Enums\PointsChangeType;
use App\Models\PointsLedger;
use App\Models\PointsMonthlyBatch;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use RuntimeException;

/**
 * 积分发放/扣减的唯一入口，对应01文档 points_ledger + points_monthly_batches 的联动逻辑，
 * 以及04文档"5. 积分消费的FIFO扣减逻辑"。任何改变用户积分余额的地方都应该走这里，
 * 不要直接改 users.points_balance。
 */
class PointsService
{
    public function __construct(private readonly PointsConfigRepository $config) {}

    public function award(
        User $user,
        PointsChangeType $type,
        int $amount,
        ?User $relatedUser = null,
        ?string $operator = null,
    ): PointsLedger {
        if ($amount <= 0) {
            throw new InvalidArgumentException('award amount must be positive');
        }

        $ledger = DB::transaction(function () use ($user, $type, $amount, $relatedUser, $operator) {
            $locked = User::query()->lockForUpdate()->findOrFail($user->id);
            $balanceAfter = $locked->points_balance + $amount;

            $ledger = PointsLedger::create([
                'user_id' => $locked->id,
                'change_type' => $type,
                'amount' => $amount,
                'balance_after' => $balanceAfter,
                'related_user_id' => $relatedUser?->id,
                'operator' => $operator,
            ]);

            $locked->update(['points_balance' => $balanceAfter]);

            $this->addToCurrentMonthlyBatch($locked, $amount);

            return $ledger;
        });

        $user->refresh();

        return $ledger;
    }

    /**
     * 按批次月份升序（最早月份优先）FIFO扣减，对应04文档"5."。
     * 扣减数量超过所有有效批次剩余总和时抛异常触发对账告警，不允许透支。
     */
    public function deduct(User $user, PointsChangeType $type, int $amount, ?string $operator = null): PointsLedger
    {
        if ($amount <= 0) {
            throw new InvalidArgumentException('deduct amount must be positive');
        }

        $ledger = DB::transaction(function () use ($user, $type, $amount, $operator) {
            $locked = User::query()->lockForUpdate()->findOrFail($user->id);

            if ($locked->points_balance < $amount) {
                throw new RuntimeException(
                    "用户 {$locked->id} 积分不足，无法扣减 {$amount}（当前余额 {$locked->points_balance}）"
                );
            }

            $this->consumeFromBatchesFifo($locked, $amount);

            $balanceAfter = $locked->points_balance - $amount;

            $ledger = PointsLedger::create([
                'user_id' => $locked->id,
                'change_type' => $type,
                'amount' => -$amount,
                'balance_after' => $balanceAfter,
                'operator' => $operator,
            ]);

            $locked->update(['points_balance' => $balanceAfter]);

            return $ledger;
        });

        $user->refresh();

        return $ledger;
    }

    /**
     * 对应04文档"4. 积分月度批次过期处理"：把一个已到期批次的剩余额度清零，
     * 写入过期清零流水，扣减用户余额。
     */
    public function expireBatch(PointsMonthlyBatch $batch): void
    {
        DB::transaction(function () use ($batch) {
            $locked = PointsMonthlyBatch::query()->lockForUpdate()->findOrFail($batch->id);

            if ($locked->status !== BatchStatus::Active) {
                return;
            }

            $remaining = $locked->points_earned_total - $locked->points_consumed_total;

            if ($remaining > 0) {
                $user = User::query()->lockForUpdate()->findOrFail($locked->user_id);
                $balanceAfter = $user->points_balance - $remaining;

                PointsLedger::create([
                    'user_id' => $user->id,
                    'change_type' => PointsChangeType::Expiration,
                    'amount' => -$remaining,
                    'balance_after' => $balanceAfter,
                ]);

                $user->update(['points_balance' => $balanceAfter]);
            }

            $locked->update([
                'points_consumed_total' => $locked->points_earned_total,
                'status' => BatchStatus::Expired,
            ]);
        });
    }

    protected function addToCurrentMonthlyBatch(User $user, int $amount): void
    {
        $batchMonth = now()->format('Y-m');

        $batch = PointsMonthlyBatch::query()
            ->where('user_id', $user->id)
            ->where('batch_month', $batchMonth)
            ->lockForUpdate()
            ->first();

        if ($batch === null) {
            $batch = new PointsMonthlyBatch([
                'user_id' => $user->id,
                'batch_month' => $batchMonth,
                'points_earned_total' => 0,
                'points_consumed_total' => 0,
                'status' => BatchStatus::Active,
                'expire_at' => $this->batchExpiry(),
            ]);
        }

        $batch->points_earned_total += $amount;
        $batch->save();
    }

    protected function consumeFromBatchesFifo(User $user, int $amount): void
    {
        $remaining = $amount;

        $batches = PointsMonthlyBatch::query()
            ->where('user_id', $user->id)
            ->where('status', BatchStatus::Active)
            ->orderBy('batch_month')
            ->lockForUpdate()
            ->get();

        foreach ($batches as $batch) {
            if ($remaining <= 0) {
                break;
            }

            $available = $batch->points_earned_total - $batch->points_consumed_total;

            if ($available <= 0) {
                continue;
            }

            $take = min($available, $remaining);
            $batch->points_consumed_total += $take;
            $batch->save();
            $remaining -= $take;
        }

        if ($remaining > 0) {
            throw new RuntimeException(
                "用户 {$user->id} 有效积分批次余额不足以覆盖扣减，差 {$remaining} 分，points_balance 与批次表数据不一致，需要人工对账"
            );
        }
    }

    /**
     * 对应01文档：expire_at 固定为 batch_month 当月最后一天 + N个月（N=points_expire_months）。
     */
    protected function batchExpiry(): Carbon
    {
        $months = $this->config->getInt('points_expire_months', 12);

        return now()->endOfMonth()->addMonthsNoOverflow($months);
    }
}
