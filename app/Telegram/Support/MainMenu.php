<?php

namespace App\Telegram\Support;

use App\Services\PointsConfigRepository;
use SergiX44\Nutgram\Telegram\Types\Keyboard\InlineKeyboardButton;
use SergiX44\Nutgram\Telegram\Types\Keyboard\InlineKeyboardMarkup;

/**
 * 对应02文档"主菜单"：常驻四个入口 邀请/签到/提现/我的，下方追加后台可自由增删的
 * 扩展链接按钮（如官方客服/下载APP/进入游戏）。
 * 各Handler回复消息时都带上这个键盘，让用户始终能回到主菜单，不用重新 /start。
 *
 * 主菜单四个按钮文案、扩展链接按钮都从points_config读，后台"机器人菜单"页面可编辑
 * （见ManageBotMenu），不写死在代码里；主菜单四个按钮的callback_data（menu:invite等）
 * 是内部路由标识，不受文案影响，不会因为运营改了按钮文字导致点击失效。扩展按钮是纯
 * url跳转，没有内部路由，改文案/链接下一次发消息立即生效，不需要"一键部署"。
 */
class MainMenu
{
    /**
     * @param  InlineKeyboardButton[][]  $leadingRows  追加在主菜单上方的额外按钮行
     */
    public static function keyboard(array $leadingRows = []): InlineKeyboardMarkup
    {
        $config = app(PointsConfigRepository::class);

        $keyboard = InlineKeyboardMarkup::make();

        foreach ($leadingRows as $row) {
            $keyboard->addRow(...$row);
        }

        $keyboard
            ->addRow(
                InlineKeyboardButton::make(text: $config->get('menu_button_invite', '邀请'), callback_data: 'menu:invite'),
                InlineKeyboardButton::make(text: $config->get('menu_button_checkin', '签到'), callback_data: 'menu:checkin'),
            )
            ->addRow(
                InlineKeyboardButton::make(text: $config->get('menu_button_withdraw', '提现'), callback_data: 'menu:withdraw'),
                InlineKeyboardButton::make(text: $config->get('menu_button_profile', '我的'), callback_data: 'menu:profile'),
            );

        foreach ($config->getJson('bot_extra_menu_buttons') as $button) {
            $label = trim((string) ($button['label'] ?? ''));
            $url = trim((string) ($button['url'] ?? ''));

            if ($label === '' || $url === '') {
                continue;
            }

            $keyboard->addRow(InlineKeyboardButton::make(text: $label, url: $url));
        }

        return $keyboard;
    }
}
