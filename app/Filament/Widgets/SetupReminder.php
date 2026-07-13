<?php

namespace App\Filament\Widgets;

use App\Enums\EnableStatus;
use App\Models\Bot;
use App\Models\Domain;
use Filament\Widgets\Widget;

/**
 * 对应07文档"关于机器人和域名配置的位置"：安装向导本身不处理机器人Token和域名配置，
 * 跳转到后台首页后要在明显位置提示"请先完成机器人和域名配置"。
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
}
