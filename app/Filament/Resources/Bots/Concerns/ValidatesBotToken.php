<?php

namespace App\Filament\Resources\Bots\Concerns;

use Filament\Notifications\Notification;
use SergiX44\Nutgram\Nutgram;
use Throwable;

/**
 * 对应03.8文档："保存前调用Telegram getMe接口校验有效性，成功则自动回填bot_username，
 * 失败则提示错误不允许保存"。
 */
trait ValidatesBotToken
{
    protected function validateTokenAndFetchUsername(string $token): string
    {
        try {
            $me = (new Nutgram($token))->getMe();
        } catch (Throwable) {
            $me = null;
        }

        if ($me === null || blank($me->username)) {
            Notification::make()
                ->title('Token无效，无法通过Telegram getMe接口校验，请检查后重试')
                ->danger()
                ->send();

            $this->halt();
        }

        return $me->username;
    }
}
