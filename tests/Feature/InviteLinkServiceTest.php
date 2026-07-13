<?php

namespace Tests\Feature;

use App\Enums\EnableStatus;
use App\Models\Bot;
use App\Models\Domain;
use App\Models\User;
use App\Services\InviteLinkService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use RuntimeException;
use Tests\TestCase;

/**
 * 对应产品决定："暂时用Telegram原始深链顶替自建短链域名跳转，不要求先配置启用域名"。
 * 短链表/short_code/domain_id仍然照常尝试生成（为以后切回自建短链系统保留数据），
 * 只是buildUrl()展示给用户的链接固定用https://t.me/{bot}?start={id}。
 */
class InviteLinkServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_build_url_uses_telegram_native_deep_link_even_without_any_domain_configured(): void
    {
        Bot::create([
            'token' => '123456:active-bot-token',
            'bot_username' => 'active_bot',
            'status' => EnableStatus::Enabled,
            'is_active' => true,
        ]);

        $user = User::factory()->create();
        $service = app(InviteLinkService::class);

        $link = $service->getOrCreate($user);

        $this->assertNull($link->domain_id);
        $this->assertSame("https://t.me/active_bot?start={$user->id}", $service->buildUrl($link));
    }

    public function test_build_url_ignores_configured_domain_and_still_uses_telegram_link(): void
    {
        Domain::create(['domain' => 'go.example.com', 'status' => EnableStatus::Enabled]);
        Bot::create([
            'token' => '123456:active-bot-token',
            'bot_username' => 'active_bot',
            'status' => EnableStatus::Enabled,
            'is_active' => true,
        ]);

        $user = User::factory()->create();
        $service = app(InviteLinkService::class);

        $link = $service->getOrCreate($user);

        $this->assertNotNull($link->domain_id, '域名配置齐了的话短链记录依旧照常分配域名，为以后切回自建短链保留数据');
        $this->assertSame("https://t.me/active_bot?start={$user->id}", $service->buildUrl($link));
    }

    public function test_build_url_throws_when_no_active_bot(): void
    {
        $user = User::factory()->create();
        $service = app(InviteLinkService::class);
        $link = $service->getOrCreate($user);

        $this->expectException(RuntimeException::class);

        $service->buildUrl($link);
    }
}
