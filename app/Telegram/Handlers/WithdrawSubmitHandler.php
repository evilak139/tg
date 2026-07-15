<?php

namespace App\Telegram\Handlers;

use App\Models\User;
use App\Services\WithdrawService;
use App\Telegram\Support\MainMenu;
use InvalidArgumentException;
use SergiX44\Nutgram\Nutgram;

/**
 * 对应02文档"提现/兑换"一节第2步：提交兑换申请。
 */
class WithdrawSubmitHandler
{
    public function __construct(private readonly WithdrawService $withdrawService) {}

    public function __invoke(Nutgram $bot): void
    {
        /** @var User $user */
        $user = $bot->get('member');

        try {
            $request = $this->withdrawService->submit($user);
        } catch (InvalidArgumentException $e) {
            $bot->answerCallbackQuery(text: $e->getMessage(), show_alert: true);

            return;
        }

        $bot->answerCallbackQuery(text: 'Solicitação enviada');

        $bot->sendMessage(
            "Solicitação de troca gerada ({$request->points_amount} pontos, aprox. R$ {$request->exchange_amount}). Fale com o atendimento para concluir a troca.",
            reply_markup: MainMenu::keyboard(),
        );
    }
}
