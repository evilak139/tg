<?php

namespace Tests\Feature\Filament;

use App\Filament\Widgets\SetupReminder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * 对应"数据库迁移是否跑齐"检测：排查过一次线上忘了跑migrate导致某个字段
 * 还是旧约束、机器人处理消息静默报错的问题（见部署排障记录），SetupReminder
 * 现在会在后台首页自动检测有没有未执行的迁移。
 */
class SetupReminderTest extends TestCase
{
    use RefreshDatabase;

    public function test_reports_zero_pending_migrations_when_fully_migrated(): void
    {
        $widget = new SetupReminder;

        $this->assertSame(0, $widget->getPendingMigrationsCount());
    }
}
