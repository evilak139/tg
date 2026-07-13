<?php

namespace Tests\Feature\Telegram;

use App\Enums\WithdrawStatus;
use App\Models\User;
use App\Models\WithdrawRequest;

/**
 * 对应02文档"提现/兑换"一节：提交申请不立即扣积分，等03.7后台核实标记完成才扣减。
 */
class WithdrawTest extends TelegramTestCase
{
    public function test_withdraw_submit_creates_pending_request_without_deducting_balance(): void
    {
        $this->start(11001, firstName: 'Alice');
        $user = User::query()->where('tg_user_id', 11001)->first();
        $user->update(['points_balance' => 150]);

        $this->pressButton(11001, 'menu:withdraw');
        $this->pressButton(11001, 'withdraw:submit');

        $user->refresh();
        $this->assertSame(150, $user->points_balance);

        $request = WithdrawRequest::query()->where('user_id', $user->id)->first();
        $this->assertNotNull($request);
        $this->assertSame(150, $request->points_amount);
        $this->assertSame(WithdrawStatus::Pending, $request->status);
    }

    public function test_withdraw_blocked_when_balance_below_min_threshold(): void
    {
        $this->start(11002, firstName: 'Bob');
        $user = User::query()->where('tg_user_id', 11002)->first();
        // 新人礼包默认10分，低于默认最低提现门槛100分

        $this->pressButton(11002, 'menu:withdraw');
        $this->pressButton(11002, 'withdraw:submit');

        $this->assertDatabaseMissing('withdraw_requests', ['user_id' => $user->id]);
    }

    public function test_new_account_withdrawal_is_flagged_as_risk(): void
    {
        $this->start(11003, firstName: 'Carol');
        $user = User::query()->where('tg_user_id', 11003)->first();
        $user->update(['points_balance' => 150]);

        $this->pressButton(11003, 'menu:withdraw');
        $this->pressButton(11003, 'withdraw:submit');

        $request = WithdrawRequest::query()->where('user_id', $user->id)->first();
        $this->assertTrue($request->risk_flag);
    }
}
