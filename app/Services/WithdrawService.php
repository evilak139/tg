<?php

namespace App\Services;

use App\Enums\PointsChangeType;
use App\Enums\WithdrawStatus;
use App\Models\User;
use App\Models\WithdrawRequest;
use InvalidArgumentException;
use RuntimeException;

/**
 * 对应02文档"提现/兑换"一节。
 *
 * TODO(需确认): 02文档里 points_amount 到底是"用户当前余额"还是"用户输入的兑换数量"写的是
 * "视产品最终确定"，没有拍板。这里先按"整笔余额提交申请"实现（MVP最简单路径，也省去一轮
 * 文本输入的会话状态管理），若产品最终要支持自定义兑换数量，需要在这里加一段等待用户输入
 * 数字的会话交互。
 */
class WithdrawService
{
    public function __construct(
        private readonly PointsConfigRepository $config,
        private readonly PointsService $pointsService,
    ) {}

    public function submit(User $user): WithdrawRequest
    {
        $pointsAmount = $user->points_balance;
        $minThreshold = $this->config->getInt('withdraw_min_threshold', 0);

        if ($pointsAmount < $minThreshold) {
            throw new InvalidArgumentException("积分余额不足最低提现门槛（{$minThreshold}分）");
        }

        $exchangeRate = $this->config->getFloat('exchange_rate', 100);
        $exchangeAmount = $exchangeRate > 0 ? round($pointsAmount / $exchangeRate, 2) : 0;

        return WithdrawRequest::create([
            'user_id' => $user->id,
            'points_amount' => $pointsAmount,
            'exchange_amount' => $exchangeAmount,
            'status' => WithdrawStatus::Pending,
            'risk_flag' => $this->isNewAccount($user),
            'applied_at' => now(),
        ]);
    }

    /**
     * 对应03.7文档"提现申请管理"：管理员核实客服线下兑换完成后点击"标记完成"，
     * 按points_monthly_batches的FIFO逻辑扣减积分，写入ledger兑换扣除记录。
     */
    public function complete(WithdrawRequest $request, string $operator): void
    {
        if ($request->status !== WithdrawStatus::Pending) {
            throw new RuntimeException('该申请不是待处理状态，不能重复标记完成');
        }

        $this->pointsService->deduct($request->user, PointsChangeType::ExchangeDeduction, $request->points_amount, $operator);

        $request->update([
            'status' => WithdrawStatus::Completed,
            'completed_at' => now(),
            'operator' => $operator,
        ]);
    }

    /**
     * TODO(需确认): 06文档"新账号短期内申请大额提现"没有给出"大额"的具体阈值。
     * 这里保守处理为：只要是新账号发起提现就打风控标记，交给人工复核判断是否真的异常，
     * 宁可多标记、不漏判。
     */
    protected function isNewAccount(User $user): bool
    {
        $limitDays = $this->config->getInt('new_account_withdraw_limit_days', 0);

        if ($limitDays <= 0) {
            return false;
        }

        return $user->register_time->diffInDays(now()) < $limitDays;
    }
}
