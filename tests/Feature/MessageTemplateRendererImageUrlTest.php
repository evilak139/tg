<?php

namespace Tests\Feature;

use App\Enums\EnableStatus;
use App\Enums\MessageTemplateType;
use App\Models\Bot;
use App\Models\Domain;
use App\Models\MessageTemplate;
use App\Models\User;
use App\Services\MessageTemplateRenderer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * 对应生产环境报过的bug："上传图片后生成的链接404"。根因有两层：
 * 1) FileUpload没显式指定disk，Laravel 11起local磁盘默认是private，存进去的文件
 *    没法通过/storage访问；
 * 2) 就算磁盘对了，MessageTemplate.image_url存的也只是相对路径（不是完整URL），
 *    直接传给Telegram的sendPhoto不可用，必须转成完整URL。
 * 这里测第2点：render()返回的image_url必须是能直接访问的完整URL。
 */
class MessageTemplateRendererImageUrlTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Domain::create(['domain' => 'go.example.com', 'status' => EnableStatus::Enabled]);
        Bot::create([
            'token' => '123456:test-bot-token',
            'bot_username' => 'test_bot',
            'status' => EnableStatus::Enabled,
            'is_active' => true,
        ]);
    }

    public function test_render_resolves_stored_path_to_a_full_public_url(): void
    {
        Storage::fake('public');

        $template = MessageTemplate::create([
            'type' => MessageTemplateType::Welcome,
            'title' => '欢迎消息',
            'content' => '欢迎 {昵称}！',
            'image_url' => 'message-templates/test.png',
        ]);

        $user = User::factory()->create();

        $rendered = app(MessageTemplateRenderer::class)->render($template->type, $user);

        $this->assertSame(
            Storage::disk('public')->url('message-templates/test.png'),
            $rendered['image_url']
        );
        $this->assertStringContainsString('/storage/message-templates/test.png', $rendered['image_url']);
    }

    public function test_render_returns_null_image_url_when_template_has_no_image(): void
    {
        $template = MessageTemplate::create([
            'type' => MessageTemplateType::Welcome,
            'title' => '欢迎消息',
            'content' => '欢迎 {昵称}！',
            'image_url' => null,
        ]);

        $user = User::factory()->create();

        $rendered = app(MessageTemplateRenderer::class)->render($template->type, $user);

        $this->assertNull($rendered['image_url']);
    }
}
