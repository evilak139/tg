<?php

namespace Tests\Feature;

use App\Enums\AdminRole;
use App\Enums\MessageTemplateType;
use App\Filament\Resources\MessageTemplates\Pages\CreateMessageTemplate;
use App\Filament\Resources\MessageTemplates\Pages\ListMessageTemplates;
use App\Models\AdminUser;
use App\Models\MessageTemplate;
use Database\Seeders\MessageTemplateSeeder;
use Filament\Actions\DeleteAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * 对应用户反馈"消息模板需要可以新增"。7个系统触发类型（欢迎/邀请/签到/我的/唤醒/
 * 积分到期提醒/月度排行榜）依然只能各有一条、只能编辑不能删除——这是渲染时按type
 * 单条查询的前提，不能破坏。新增能力只开放给不挂任何自动触发点的"自定义"类型，
 * 主要给群发消息功能用。
 */
class MessageTemplateManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $admin = AdminUser::create([
            'username' => 'templatemgmttest',
            'password_hash' => 'password',
            'role' => AdminRole::SuperAdmin,
        ]);

        $this->actingAs($admin, 'admin');
    }

    public function test_creating_a_template_always_forces_custom_type(): void
    {
        Livewire::test(CreateMessageTemplate::class)
            ->fillForm([
                'title' => '促销活动',
                'content' => '{昵称}，限时活动来啦！',
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $template = MessageTemplate::query()->where('title', '促销活动')->firstOrFail();

        $this->assertSame(MessageTemplateType::Custom, $template->type);
    }

    public function test_multiple_custom_templates_can_coexist(): void
    {
        MessageTemplate::create([
            'type' => MessageTemplateType::Custom,
            'title' => '促销1',
            'content' => '内容1',
        ]);

        Livewire::test(CreateMessageTemplate::class)
            ->fillForm([
                'title' => '促销2',
                'content' => '内容2',
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertSame(2, MessageTemplate::query()->where('type', MessageTemplateType::Custom)->count());
    }

    public function test_custom_template_can_be_deleted_but_system_template_cannot(): void
    {
        $this->seed(MessageTemplateSeeder::class);

        $custom = MessageTemplate::create([
            'type' => MessageTemplateType::Custom,
            'title' => '促销',
            'content' => '内容',
        ]);

        $welcome = MessageTemplate::query()->where('type', MessageTemplateType::Welcome)->firstOrFail();

        Livewire::test(ListMessageTemplates::class)
            ->callTableAction(DeleteAction::class, $custom);

        $this->assertDatabaseMissing('message_templates', ['id' => $custom->id]);

        Livewire::test(ListMessageTemplates::class)
            ->assertTableActionHidden(DeleteAction::class, $welcome);

        $this->assertDatabaseHas('message_templates', ['id' => $welcome->id]);
    }
}
