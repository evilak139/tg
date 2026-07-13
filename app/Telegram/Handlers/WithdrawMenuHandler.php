<?php

namespace App\Telegram\Handlers;

use App\Models\User;
use App\Services\PointsConfigRepository;
use App\Telegram\Support\MainMenu;
use SergiX44\Nutgram\Nutgram;
use SergiX44\Nutgram\Telegram\Types\Keyboard\InlineKeyboardButton;

/**
 * 对应02文档"提现/兑换"一节第1步：展示余额、可兑换金额、客服联系方式，提供提交入口。
 */
class WithdrawMenuHandler
{
    public function __construct(private readonly PointsConfigRepository $config) {}

    public function __invoke(Nutgram $bot): void
    {
        $bot->answerCallbackQuery();

        /** @var User $user */
        $user = $bot->get('member');

        $exchangeRate = $this->config->getFloat('exchange_rate', 100);
        $exchangeAmount = $exchangeRate > 0 ? round($user->points_balance / $exchangeRate, 2) : 0;
        $contact = $this->config->get('customer_service_contact', '-');

        $text = "当前积分：{$user->points_balance}\n预计可兑换：{$exchangeAmount} 元\n客服联系方式：{$contact}";

        $keyboard = MainMenu::keyboard([
            [InlineKeyboardButton::make(text: '提交兑换申请', callback_data: 'withdraw:submit')],
        ]);

        $bot->sendMessage($text, reply_markup: $keyboard);
    }
}
