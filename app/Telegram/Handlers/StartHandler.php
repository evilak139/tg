<?php

namespace App\Telegram\Handlers;

use App\Enums\MessageTemplateType;
use App\Services\MessageTemplateRenderer;
use App\Services\RegistrationService;
use App\Telegram\Support\MainMenu;
use Illuminate\Support\Facades\Log;
use SergiX44\Nutgram\Nutgram;
use Throwable;

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
        // TODO(临时诊断，定位到问题后删除): 排查"/start 无任何反应、无异常、无日志"问题用。
        Log::channel('single')->info('[DIAG] StartHandler entered');

        $tgUser = $bot->user();

        Log::channel('single')->info('[DIAG] bot->user() resolved', ['tgUser' => $tgUser?->toArray()]);

        if ($tgUser === null) {
            Log::channel('single')->info('[DIAG] tgUser is null, returning early');

            return;
        }

        $nickname = trim(($tgUser->first_name ?? '').' '.($tgUser->last_name ?? ''));
        $nickname = $nickname !== '' ? $nickname : ($tgUser->username ?? "用户{$tgUser->id}");

        try {
            $result = $this->registrationService->handleStart(
                tgUserId: $tgUser->id,
                tgUsername: $tgUser->username,
                nickname: $nickname,
                payload: $this->extractPayload($bot->message()?->text),
            );

            Log::channel('single')->info('[DIAG] registrationService->handleStart done', ['userId' => $result['user']->id, 'isNew' => $result['isNew']]);

            $rendered = $this->renderer->render(MessageTemplateType::Welcome, $result['user']);

            Log::channel('single')->info('[DIAG] template rendered, calling sendMessage', ['text' => $rendered['text']]);

            $bot->sendMessage($rendered['text'], reply_markup: MainMenu::keyboard());

            Log::channel('single')->info('[DIAG] sendMessage call returned without throwing');
        } catch (Throwable $e) {
            Log::channel('single')->error('[DIAG] exception caught in StartHandler', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
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
