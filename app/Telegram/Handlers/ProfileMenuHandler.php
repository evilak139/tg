<?php

namespace App\Telegram\Handlers;

use App\Enums\MessageTemplateType;
use App\Models\User;
use App\Services\InviteLinkService;
use App\Services\MessageTemplateRenderer;
use App\Telegram\Support\MainMenu;
use SergiX44\Nutgram\Nutgram;
use SergiX44\Nutgram\Telegram\Types\Keyboard\CopyTextButton;
use SergiX44\Nutgram\Telegram\Types\Keyboard\InlineKeyboardButton;

/**
 * 对应02文档"我的"一节。
 */
class ProfileMenuHandler
{
    public function __construct(
        private readonly MessageTemplateRenderer $renderer,
        private readonly InviteLinkService $inviteLinkService,
    ) {}

    public function __invoke(Nutgram $bot): void
    {
        $bot->answerCallbackQuery();

        /** @var User $user */
        $user = $bot->get('member');

        $rendered = $this->renderer->render(MessageTemplateType::Profile, $user);

        $inviteUrl = $this->inviteLinkService->buildUrl($this->inviteLinkService->getOrCreate($user));

        $keyboard = MainMenu::keyboard([
            [
                InlineKeyboardButton::make(text: 'Copiar link de convite', copy_text: CopyTextButton::make($inviteUrl)),
                InlineKeyboardButton::make(text: 'Histórico de pontos', callback_data: 'profile:ledger'),
            ],
        ]);

        $bot->sendMessage($rendered['text'], reply_markup: $keyboard);
    }
}
