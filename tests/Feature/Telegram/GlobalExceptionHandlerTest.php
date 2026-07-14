<?php

namespace Tests\Feature\Telegram;

use App\Services\RegistrationService;
use Illuminate\Support\Facades\Log;
use RuntimeException;

/**
 * 对应"机器人容灾/可观测性"改进：验证 routes/telegram.php 里注册的全局
 * $bot->onException() 兜底确实生效——handler内部抛出的异常必须落进
 * Laravel日志（而不是像Nutgram默认行为那样只fwrite到STDERR、后台管理员
 * 完全看不到），且不能让整个update处理流程崩溃、用户至少收到一条提示。
 * 排查这个可观测性缺口花了很长时间，见部署排障记录，这里补一条回归测试
 * 防止以后又被悄悄改掉。
 */
class GlobalExceptionHandlerTest extends TelegramTestCase
{
    public function test_handler_exception_is_logged_and_user_gets_fallback_message(): void
    {
        $this->app->bind(RegistrationService::class, fn () => new class extends RegistrationService
        {
            public function __construct() {}

            public function handleStart(int $tgUserId, ?string $tgUsername, string $nickname, ?string $payload): array
            {
                throw new RuntimeException('模拟handler内部异常');
            }
        });

        // channel('null')是Nutgram自身构造client时按nutgram.log_channel默认值调用的，
        // channel('single')是我们在routes/telegram.php里显式指定的，两种都要放行。
        Log::shouldReceive('channel')->andReturnSelf();
        Log::shouldReceive('debug');
        Log::shouldReceive('error')
            ->once()
            ->withArgs(function (string $message, array $context) {
                return $message === '机器人处理消息时出现未捕获异常'
                    && $context['message'] === '模拟handler内部异常';
            });

        $this->start(5001, firstName: 'Boom');

        $this->bot()->assertReplyText('服务暂时出现问题，请稍后再试。');
    }
}
