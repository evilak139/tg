<?php

namespace Tests\Feature\Telegram;

use App\Models\PointsConfig;
use App\Telegram\Support\MainMenu;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * 对应本次需求："主菜单内联按钮文案后台可配置"。
 */
class MainMenuTest extends TestCase
{
    use RefreshDatabase;

    public function test_button_labels_reflect_points_config(): void
    {
        PointsConfig::create(['key' => 'menu_button_invite', 'value' => '拉新']);
        PointsConfig::create(['key' => 'menu_button_checkin', 'value' => '打卡']);
        PointsConfig::create(['key' => 'menu_button_withdraw', 'value' => '兑换']);
        PointsConfig::create(['key' => 'menu_button_profile', 'value' => '个人中心']);

        $keyboard = MainMenu::keyboard();

        $labels = collect($keyboard->inline_keyboard)->flatten()->map(fn ($button) => $button->text);

        $this->assertTrue($labels->contains('拉新'));
        $this->assertTrue($labels->contains('打卡'));
        $this->assertTrue($labels->contains('兑换'));
        $this->assertTrue($labels->contains('个人中心'));
    }

    public function test_falls_back_to_defaults_when_not_configured(): void
    {
        $keyboard = MainMenu::keyboard();

        $labels = collect($keyboard->inline_keyboard)->flatten()->map(fn ($button) => $button->text);

        $this->assertTrue($labels->contains('邀请'));
        $this->assertTrue($labels->contains('签到'));
        $this->assertTrue($labels->contains('提现'));
        $this->assertTrue($labels->contains('我的'));
    }
}
