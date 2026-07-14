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

    public function test_extra_link_buttons_render_as_their_own_rows(): void
    {
        PointsConfig::create(['key' => 'bot_extra_menu_buttons', 'value' => json_encode([
            ['label' => '官方客服', 'url' => 'https://t.me/service'],
            ['label' => '下载APP', 'url' => 'https://example.com/download'],
            ['label' => '进入游戏', 'url' => 'https://example.com/game'],
        ])]);

        $keyboard = MainMenu::keyboard();
        $rows = $keyboard->inline_keyboard;

        $lastThreeRows = array_slice($rows, -3);

        $this->assertCount(1, $lastThreeRows[0]);
        $this->assertSame('官方客服', $lastThreeRows[0][0]->text);
        $this->assertSame('https://t.me/service', $lastThreeRows[0][0]->url);

        $this->assertCount(1, $lastThreeRows[1]);
        $this->assertSame('下载APP', $lastThreeRows[1][0]->text);

        $this->assertCount(1, $lastThreeRows[2]);
        $this->assertSame('进入游戏', $lastThreeRows[2][0]->text);
        $this->assertSame('https://example.com/game', $lastThreeRows[2][0]->url);
    }

    public function test_extra_buttons_missing_label_or_url_are_skipped(): void
    {
        PointsConfig::create(['key' => 'bot_extra_menu_buttons', 'value' => json_encode([
            ['label' => '', 'url' => 'https://example.com'],
            ['label' => '缺链接', 'url' => ''],
        ])]);

        $keyboard = MainMenu::keyboard();

        $labels = collect($keyboard->inline_keyboard)->flatten()->map(fn ($button) => $button->text);

        $this->assertFalse($labels->contains('缺链接'));
        $this->assertCount(2, $keyboard->inline_keyboard);
    }
}
