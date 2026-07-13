<?php

namespace Tests\Feature;

use App\Enums\EnableStatus;
use App\Models\Bot;
use App\Models\Domain;
use App\Models\InviteLink;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * 对应00文档"邀请链接形式"：{域名}/r/{短码} 302跳转到当前生效机器人，且机器人容灾
 * 切换后历史短链自动生效（不查短链记录里存的机器人信息，实时查is_active）。
 * InviteLinkService::buildUrl()一直生成这个格式的URL，但跳转路由本身一直没实现，
 * 对应用户反馈"生成的短链接打不开"。
 */
class ShortLinkRedirectTest extends TestCase
{
    use RefreshDatabase;

    public function test_valid_short_code_redirects_to_active_bot_with_inviter_payload(): void
    {
        $domain = Domain::create(['domain' => 'go.example.com', 'status' => EnableStatus::Enabled]);
        $inviter = User::factory()->create();
        $link = InviteLink::create([
            'user_id' => $inviter->id,
            'domain_id' => $domain->id,
            'short_code' => 'abc12345',
        ]);
        Bot::create([
            'token' => '123456:active-bot-token',
            'bot_username' => 'active_bot',
            'status' => EnableStatus::Enabled,
            'is_active' => true,
        ]);

        $response = $this->get('/r/abc12345');

        $response->assertRedirect("https://t.me/active_bot?start={$inviter->id}");
        $this->assertSame(1, $link->fresh()->click_count);
    }

    public function test_switching_active_bot_redirects_existing_short_links_to_new_bot(): void
    {
        $domain = Domain::create(['domain' => 'go.example.com', 'status' => EnableStatus::Enabled]);
        $inviter = User::factory()->create();
        InviteLink::create([
            'user_id' => $inviter->id,
            'domain_id' => $domain->id,
            'short_code' => 'oldcode1',
        ]);
        Bot::create([
            'token' => '123456:old-bot-token',
            'bot_username' => 'old_bot',
            'status' => EnableStatus::Enabled,
            'is_active' => false,
        ]);
        Bot::create([
            'token' => '123456:new-bot-token',
            'bot_username' => 'new_bot',
            'status' => EnableStatus::Enabled,
            'is_active' => true,
        ]);

        $this->get('/r/oldcode1')->assertRedirect("https://t.me/new_bot?start={$inviter->id}");
    }

    public function test_unknown_short_code_returns_404(): void
    {
        $this->get('/r/does-not-exist')->assertNotFound();
    }

    public function test_returns_service_unavailable_when_no_active_bot(): void
    {
        $domain = Domain::create(['domain' => 'go.example.com', 'status' => EnableStatus::Enabled]);
        $inviter = User::factory()->create();
        InviteLink::create([
            'user_id' => $inviter->id,
            'domain_id' => $domain->id,
            'short_code' => 'nobotcode',
        ]);

        $this->get('/r/nobotcode')->assertStatus(503);
    }
}
