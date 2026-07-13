<?php

namespace Tests\Feature\Telegram;

use App\Models\InviteLink;
use App\Models\User;

/**
 * 对应02文档"邀请"一节：专属短链，从启用域名池分配，长期复用同一条短链。
 */
class InviteTest extends TelegramTestCase
{
    public function test_invite_button_creates_invite_link_with_enabled_domain(): void
    {
        $this->start(10001, firstName: 'Alice');
        $user = User::query()->where('tg_user_id', 10001)->first();

        $this->pressButton(10001, 'menu:invite');

        $link = InviteLink::query()->where('user_id', $user->id)->first();
        $this->assertNotNull($link);
        $this->assertSame('go.example.com', $link->domain->domain);
    }

    public function test_invite_link_is_reused_on_second_click(): void
    {
        $this->start(10002, firstName: 'Bob');
        $user = User::query()->where('tg_user_id', 10002)->first();

        $this->pressButton(10002, 'menu:invite');
        $first = InviteLink::query()->where('user_id', $user->id)->first();

        $this->pressButton(10002, 'menu:invite');

        $this->assertSame(1, InviteLink::query()->where('user_id', $user->id)->count());
        $second = InviteLink::query()->where('user_id', $user->id)->first();
        $this->assertSame($first->short_code, $second->short_code);
    }
}
