<?php

namespace Tests\Feature\Console;

use App\Enums\AdminRole;
use App\Enums\EnableStatus;
use App\Models\AdminUser;
use App\Models\Bot;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * 对应04文档"1. 机器人健康检测"。这里只测失败路径（用一个明显无效的token），
 * 因为"成功"路径需要一个真实可用的Bot Token，测试环境没有，也不应该硬编码真token。
 * 失败路径本身会发起一次真实的Telegram API请求（很快返回401），不mock。
 */
class CheckBotHealthTest extends TestCase
{
    use RefreshDatabase;

    public function test_invalid_active_bot_gets_marked_abnormal_and_alerts_super_admins(): void
    {
        $admin = AdminUser::create([
            'username' => 'super',
            'password_hash' => 'password',
            'role' => AdminRole::SuperAdmin,
        ]);

        $bot = Bot::create([
            'token' => '123456:invalid-token-for-testing',
            'bot_username' => 'test_bot',
            'status' => EnableStatus::Enabled,
            'is_active' => true,
        ]);

        $this->artisan('app:check-bot-health')->assertSuccessful();

        $bot->refresh();
        $this->assertSame(EnableStatus::Abnormal, $bot->status);
        $this->assertNotNull($bot->last_health_check_time);

        $this->assertSame(1, $admin->notifications()->count());
    }

    public function test_invalid_inactive_bot_does_not_alert_anyone(): void
    {
        $admin = AdminUser::create([
            'username' => 'super2',
            'password_hash' => 'password',
            'role' => AdminRole::SuperAdmin,
        ]);

        Bot::create([
            'token' => '123456:invalid-token-for-testing',
            'bot_username' => 'inactive_bot',
            'status' => EnableStatus::Enabled,
            'is_active' => false,
        ]);

        $this->artisan('app:check-bot-health')->assertSuccessful();

        $this->assertSame(0, $admin->notifications()->count());
    }
}
