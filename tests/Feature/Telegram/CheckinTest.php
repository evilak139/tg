<?php

namespace Tests\Feature\Telegram;

use App\Enums\IdentityLevel;
use App\Models\Checkin;
use App\Models\PointsLedger;
use App\Models\User;
use Illuminate\Support\Carbon;

/**
 * 对应02文档"签到"一节 + 06文档"邀请奖励不在关注机器人时发放，必须等被邀请人完成首次签到"。
 */
class CheckinTest extends TelegramTestCase
{
    public function test_first_checkin_awards_points_and_sets_streak_to_one(): void
    {
        $this->start(6001, firstName: 'Alice');
        $user = User::query()->where('tg_user_id', 6001)->first();

        $this->pressButton(6001, 'menu:checkin');

        $user->refresh();
        $this->assertSame(1, $user->checkin_streak);
        $this->assertSame(1, Checkin::query()->where('user_id', $user->id)->count());
        $this->assertGreaterThan(0, $user->points_balance);
    }

    public function test_second_checkin_same_day_does_not_award_points_again(): void
    {
        $this->start(6002, firstName: 'Bob');
        $user = User::query()->where('tg_user_id', 6002)->first();

        $this->pressButton(6002, 'menu:checkin');
        $user->refresh();
        $balanceAfterFirst = $user->points_balance;

        $this->pressButton(6002, 'menu:checkin');
        $user->refresh();

        $this->assertSame($balanceAfterFirst, $user->points_balance);
        $this->assertSame(1, Checkin::query()->where('user_id', $user->id)->count());
    }

    public function test_first_checkin_triggers_three_level_commission(): void
    {
        $this->start(7001, firstName: 'L3');
        $l3 = User::query()->where('tg_user_id', 7001)->first();

        $this->start(7002, payload: (string) $l3->id, firstName: 'L2');
        $l2 = User::query()->where('tg_user_id', 7002)->first();

        $this->start(7003, payload: (string) $l2->id, firstName: 'L1');
        $l1 = User::query()->where('tg_user_id', 7003)->first();

        $this->start(7004, payload: (string) $l1->id, firstName: 'Invitee');

        $this->pressButton(7004, 'menu:checkin');

        $this->assertDatabaseHas('points_ledger', ['user_id' => $l1->id, 'change_type' => '邀请一级返佣', 'amount' => 10]);
        $this->assertDatabaseHas('points_ledger', ['user_id' => $l2->id, 'change_type' => '邀请二级返佣', 'amount' => 3]);
        $this->assertDatabaseHas('points_ledger', ['user_id' => $l3->id, 'change_type' => '邀请三级返佣', 'amount' => 1]);
    }

    public function test_second_checkin_of_invitee_does_not_trigger_commission_again(): void
    {
        $this->start(8001, firstName: 'Inviter');
        $inviter = User::query()->where('tg_user_id', 8001)->first();

        $this->start(8002, payload: (string) $inviter->id, firstName: 'Invitee');

        $this->pressButton(8002, 'menu:checkin');
        $this->assertSame(
            1,
            PointsLedger::query()->where('user_id', $inviter->id)->where('change_type', '邀请一级返佣')->count()
        );

        // 第二天再签到一次（模拟：直接改last_checkin_date为昨天以形成连续），返佣不应该再次触发
        $invitee = User::query()->where('tg_user_id', 8002)->first();
        $invitee->update(['last_checkin_date' => now()->subDay()->toDateString()]);
        Carbon::setTestNow(now()->addDay());

        $this->pressButton(8002, 'menu:checkin');

        Carbon::setTestNow();

        $this->assertSame(
            1,
            PointsLedger::query()->where('user_id', $inviter->id)->where('change_type', '邀请一级返佣')->count()
        );
    }

    public function test_milestone_bonus_fires_when_direct_invite_count_reaches_five(): void
    {
        $this->start(9000, firstName: 'Inviter');
        $inviter = User::query()->where('tg_user_id', 9000)->first();

        for ($i = 1; $i <= 5; $i++) {
            $this->start(9000 + $i, payload: (string) $inviter->id, firstName: "Invitee{$i}");
        }

        // 5人都已注册，其中一人首次签到时累计邀请人数已经是5，应该触发里程碑奖励
        $this->pressButton(9001, 'menu:checkin');

        $inviter->refresh();
        $this->assertContains(5, $inviter->milestones_claimed ?? []);
        $this->assertDatabaseHas('points_ledger', [
            'user_id' => $inviter->id,
            'change_type' => '里程碑奖励',
            'amount' => 50,
        ]);

        // 换第二个人签到，不应该重复发放5人档位的里程碑奖励
        $this->pressButton(9002, 'menu:checkin');
        $this->assertSame(
            1,
            PointsLedger::query()->where('user_id', $inviter->id)->where('change_type', '里程碑奖励')->count()
        );
    }

    public function test_identity_level_upgrades_after_milestone(): void
    {
        $this->start(9500, firstName: 'Inviter');
        $inviter = User::query()->where('tg_user_id', 9500)->first();

        for ($i = 1; $i <= 5; $i++) {
            $this->start(9500 + $i, payload: (string) $inviter->id, firstName: "Invitee{$i}");
        }

        $this->pressButton(9501, 'menu:checkin');

        $inviter->refresh();
        $this->assertSame(IdentityLevel::InviteExpert, $inviter->identity_level);
    }
}
