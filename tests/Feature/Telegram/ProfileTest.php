<?php

namespace Tests\Feature\Telegram;

/**
 * 对应02文档"我的"一节。
 */
class ProfileTest extends TelegramTestCase
{
    public function test_profile_button_sends_a_reply(): void
    {
        $this->start(12001, firstName: 'Alice');

        $this->pressButton(12001, 'menu:profile');

        // /start 的欢迎消息 + "我的"菜单回复，一共2次 sendMessage
        $this->bot()->assertCalled('sendMessage', 2);
    }

    public function test_points_history_button_sends_a_reply(): void
    {
        $this->start(12002, firstName: 'Bob');
        $this->pressButton(12002, 'menu:checkin');

        $this->pressButton(12002, 'profile:ledger');

        // /start欢迎 + 签到回复 + 积分明细回复，一共3次 sendMessage
        $this->bot()->assertCalled('sendMessage', 3);
    }
}
