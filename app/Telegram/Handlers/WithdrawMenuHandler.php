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

        // TODO(需确认): exchange_rate的兑换单位文档未明确（见PointsConfigSeeder的TODO），
        // 这里按面向巴西用户直接展示为雷亚尔(R$)，如果实际兑换目标不是货币需要再调整文案。
        $text = "Pontos atuais: {$user->points_balance}\nValor estimado para troca: R$ {$exchangeAmount}\nContato do atendimento: {$contact}";

        $keyboard = MainMenu::keyboard([
            [InlineKeyboardButton::make(text: 'Solicitar troca', callback_data: 'withdraw:submit')],
        ]);

        $bot->sendMessage($text, reply_markup: $keyboard);
    }
}
