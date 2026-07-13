<?php

namespace Tests\Feature;

use App\Enums\EnableStatus;
use App\Models\Bot;
use App\Services\BotDeploymentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use RuntimeException;
use Tests\TestCase;

/**
 * 对应本次需求："一键部署"：把命令菜单说明推送到Telegram。
 * 用明显无效的token测试失败路径（真实网络请求，很快返回401，不mock）；
 * "成功"路径需要真实可用的Bot Token，测试环境没有，不硬编码真token去测。
 */
class BotDeploymentServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_deploy_throws_for_invalid_token(): void
    {
        $bot = Bot::create([
            'token' => '123456:invalid-token-for-testing',
            'status' => EnableStatus::Enabled,
            'is_active' => false,
        ]);

        $this->expectException(RuntimeException::class);

        app(BotDeploymentService::class)->deploy($bot);
    }
}
