<?php

namespace App\Telegram\Handlers;

use App\Enums\MessageTemplateType;
use App\Services\MessageTemplateRenderer;
use App\Services\RegistrationService;
use App\Telegram\Support\MainMenu;
use SergiX44\Nutgram\Nutgram;

/**
 * 对应02文档"/start 处理逻辑（关注注册 + 邀请关系建立）"。
 */
class StartHandler
{
    public function __construct(
        private readonly RegistrationService $registrationService,
        private readonly MessageTemplateRenderer $renderer,
    ) {}

    public function __invoke(Nutgram $bot): void
    {
        $tgUser = $bot->user();

        if ($tgUser === null) {
            return;
        }

        $nickname = trim(($tgUser->first_name ?? '').' '.($tgUser->last_name ?? ''));
        $nickname = $nickname !== '' ? $nickname : ($tgUser->username ?? "Usuario{$tgUser->id}");

        $result = $this->registrationService->handleStart(
            tgUserId: $tgUser->id,
            tgUsername: $tgUser->username,
            nickname: $nickname,
            payload: $this->extractPayload($bot->message()?->text),
        );

        $rendered = $this->renderer->render(MessageTemplateType::Welcome, $result['user']);

        $bot->sendMessage($rendered['text'], reply_markup: MainMenu::keyboard());
    }

    /**
     * 解析 "/start {payload}" 中的 payload，对应02文档：
     * payload 为空表示无邀请人直接关注；payload 为邀请人的 users.id。
     */
    protected function extractPayload(?string $text): ?string
    {
        if ($text === null) {
            return null;
        }

        $parts = preg_split('/\s+/', trim($text), 2);

        return $parts[1] ?? null;
    }
}
