<?php

namespace Tests\Feature\Telegram;

use App\Models\PointsLedger;
use App\Models\User;

/**
 * 对应02文档"/start 处理逻辑（关注注册 + 邀请关系建立）"。
 */
class RegistrationTest extends TelegramTestCase
{
    public function test_start_creates_new_user_and_grants_new_account_gift(): void
    {
        $this->start(1001, firstName: 'Alice');

        $user = User::query()->where('tg_user_id', 1001)->first();

        $this->assertNotNull($user);
        $this->assertSame('Alice', $user->nickname);
        $this->assertNull($user->invited_by_l1);
        $this->assertGreaterThan(0, $user->points_balance);

        $this->assertDatabaseHas('points_ledger', [
            'user_id' => $user->id,
            'change_type' => '新人礼包',
        ]);
    }

    public function test_start_with_inviter_payload_builds_three_level_chain(): void
    {
        $this->start(2001, firstName: 'A');
        $a = User::query()->where('tg_user_id', 2001)->first();

        $this->start(2002, payload: (string) $a->id, firstName: 'B');
        $b = User::query()->where('tg_user_id', 2002)->first();
        $this->assertSame($a->id, $b->invited_by_l1);
        $this->assertNull($b->invited_by_l2);

        $this->start(2003, payload: (string) $b->id, firstName: 'C');
        $c = User::query()->where('tg_user_id', 2003)->first();
        $this->assertSame($b->id, $c->invited_by_l1);
        $this->assertSame($a->id, $c->invited_by_l2);
        $this->assertNull($c->invited_by_l3);

        $this->start(2004, payload: (string) $c->id, firstName: 'D');
        $d = User::query()->where('tg_user_id', 2004)->first();
        $this->assertSame($c->id, $d->invited_by_l1);
        $this->assertSame($b->id, $d->invited_by_l2);
        $this->assertSame($a->id, $d->invited_by_l3);
    }

    public function test_start_does_not_trigger_commission_before_first_checkin(): void
    {
        $this->start(3001, firstName: 'Inviter');
        $inviter = User::query()->where('tg_user_id', 3001)->first();

        $this->start(3002, payload: (string) $inviter->id, firstName: 'Invitee');

        $this->assertDatabaseMissing('points_ledger', [
            'user_id' => $inviter->id,
            'change_type' => '邀请一级返佣',
        ]);
    }

    public function test_returning_user_does_not_get_a_second_gift(): void
    {
        $this->start(4001, firstName: 'Alice');
        $user = User::query()->where('tg_user_id', 4001)->first();
        $initialBalance = $user->points_balance;

        $this->start(4001, firstName: 'Alice');

        $user->refresh();
        $this->assertSame($initialBalance, $user->points_balance);
        $this->assertSame(
            1,
            PointsLedger::query()->where('user_id', $user->id)->where('change_type', '新人礼包')->count()
        );
    }
}
