<?php

namespace App\Telegram\Handlers;

use App\Enums\MessageTemplateType;
use App\Models\User;
use App\Services\CheckinService;
use App\Services\MessageTemplateRenderer;
use App\Telegram\Support\MainMenu;
use SergiX44\Nutgram\Nutgram;

/**
 * 对应02文档"签到"一节。
 */
class CheckinMenuHandler
{
    public function __construct(
        private readonly CheckinService $checkinService,
        private readonly MessageTemplateRenderer $renderer,
    ) {}

    public function __invoke(Nutgram $bot): void
    {
        $bot->answerCallbackQuery();

        /** @var User $user */
        $user = $bot->get('member');

        $result = $this->checkinService->checkin($user);

        if ($result['alreadyCheckedIn']) {
            $bot->sendMessage('今日已签到，明天再来吧～', reply_markup: MainMenu::keyboard());

            return;
        }

        $rendered = $this->renderer->render(MessageTemplateType::Checkin, $user);

        $bot->sendMessage($rendered['text'], reply_markup: MainMenu::keyboard());
    }
}
