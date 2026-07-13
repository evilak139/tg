<?php

namespace App\Telegram\Handlers;

use App\Enums\MessageTemplateType;
use App\Models\User;
use App\Services\MessageTemplateRenderer;
use App\Services\PosterService;
use App\Telegram\Support\MainMenu;
use RuntimeException;
use SergiX44\Nutgram\Nutgram;
use SergiX44\Nutgram\Telegram\Types\Internal\InputFile;

/**
 * 对应02文档"邀请"一节：专属短链 + 带二维码的海报 + 奖励规则 + 里程碑进度 + 本月排名。
 */
class InviteMenuHandler
{
    public function __construct(
        private readonly MessageTemplateRenderer $renderer,
        private readonly PosterService $posterService,
    ) {}

    public function __invoke(Nutgram $bot): void
    {
        $bot->answerCallbackQuery();

        /** @var User $user */
        $user = $bot->get('member');

        $rendered = $this->renderer->render(MessageTemplateType::Invite, $user);

        try {
            $posterPng = $this->posterService->generate($user);
        } catch (RuntimeException $e) {
            // 例如域名池为空，此时仍然把文案（含短链失败提示）发出去，而不是让整个交互卡死
            $bot->sendMessage($rendered['text']."\n\n{$e->getMessage()}", reply_markup: MainMenu::keyboard());

            return;
        }

        $stream = fopen('php://temp', 'r+');
        fwrite($stream, $posterPng);
        rewind($stream);

        $bot->sendPhoto(
            photo: InputFile::make($stream, 'invite-poster.png'),
            caption: $rendered['text'],
            reply_markup: MainMenu::keyboard(),
        );
    }
}
