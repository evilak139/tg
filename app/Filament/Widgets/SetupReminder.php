<?php

namespace App\Filament\Widgets;

use App\Enums\EnableStatus;
use App\Models\Bot;
use App\Models\Domain;
use Filament\Widgets\Widget;
use Illuminate\Database\Migrations\Migrator;

/**
 * 对应07文档"关于机器人和域名配置的位置"：安装向导本身不处理机器人Token和域名配置，
 * 跳转到后台首页后要在明显位置提示"请先完成机器人和域名配置"。
 *
 * 同时兼管"数据库迁移是否跑齐"的检测：排查过一次线上"git pull了代码但忘了跑
 * migrate"导致某个字段还是旧的NOT NULL约束、机器人处理消息时静默报错的问题
 * （见部署排障记录），单靠管理员自己记得跑migrate不可靠，这里在每次进后台首页
 * 时自动检测一遍，有未执行的迁移就显眼地提示出来，不用等某个具体功能报错才发现。
 */
class SetupReminder extends Widget
{
    protected string $view = 'filament.widgets.setup-reminder';

    protected int|string|array $columnSpan = 'full';

    protected static ?int $sort = -100;

    public function getMissingSetupItems(): array
    {
        $items = [];

        if (! Bot::query()->where('is_active', true)->exists()) {
            $items[] = '尚未设置"当前生效机器人"，请前往"机器人配置"完成设置';
        }

        if (! Domain::query()->where('status', EnableStatus::Enabled)->exists()) {
            $items[] = '尚未启用任何域名，邀请短链无法生成，请前往"域名配置"完成设置';
        }

        return $items;
    }

    public function getPendingMigrationsCount(): int
    {
        $migrator = app(Migrator::class);

        if (! $migrator->repositoryExists()) {
            return 0;
        }

        $ran = $migrator->getRepository()->getRan();

        // 迁移路径 = migrator已注册的额外路径（如第三方包）+ 默认的database/migrations，
        // 跟 migrate:status 命令内部拼路径的逻辑一致（见BaseCommand::getMigrationPaths()）。
        $paths = array_merge($migrator->paths(), [database_path('migrations')]);

        $all = collect($migrator->getMigrationFiles($paths))
            ->map(fn ($file) => $migrator->getMigrationName($file));

        return $all->reject(fn ($name) => in_array($name, $ran, true))->count();
    }
}
