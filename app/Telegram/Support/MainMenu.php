<?php

namespace App\Telegram\Support;

use SergiX44\Nutgram\Telegram\Types\Keyboard\InlineKeyboardButton;
use SergiX44\Nutgram\Telegram\Types\Keyboard\InlineKeyboardMarkup;

/**
 * 对应02文档"主菜单"：常驻四个入口 邀请/签到/提现/我的。
 * 各Handler回复消息时都带上这个键盘，让用户始终能回到主菜单，不用重新 /start。
 */
class MainMenu
{
    /**
     * @param  InlineKeyboardButton[][]  $leadingRows  追加在主菜单上方的额外按钮行
     */
    public static function keyboard(array $leadingRows = []): InlineKeyboardMarkup
    {
        $keyboard = InlineKeyboardMarkup::make();

        foreach ($leadingRows as $row) {
            $keyboard->addRow(...$row);
        }

        return $keyboard
            ->addRow(
                InlineKeyboardButton::make(text: '邀请', callback_data: 'menu:invite'),
                InlineKeyboardButton::make(text: '签到', callback_data: 'menu:checkin'),
            )
            ->addRow(
                InlineKeyboardButton::make(text: '提现', callback_data: 'menu:withdraw'),
                InlineKeyboardButton::make(text: '我的', callback_data: 'menu:profile'),
            );
    }
}
