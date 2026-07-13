<?php

namespace App\Services;

use App\Enums\WithdrawStatus;
use App\Models\User;
use App\Models\WithdrawRequest;
use InvalidArgumentException;

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
    public function __construct(private readonly PointsConfigRepository $config) {}

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
