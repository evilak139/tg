<?php

namespace Tests\Feature;

use App\Enums\AdminRole;
use App\Enums\MessageTemplateType;
use App\Filament\Resources\BroadcastTasks\Pages\CreateBroadcastTask;
use App\Models\AdminUser;
use App\Models\BroadcastTask;
use App\Models\MessageTemplate;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * 对应需求："群发消息应该有两个选项，定时发送，以及立即发送的选项"。
 */
class BroadcastTaskFormTest extends TestCase
{
    use RefreshDatabase;

    protected MessageTemplate $template;

    protected function setUp(): void
    {
        parent::setUp();

        $this->template = MessageTemplate::create([
            'type' => MessageTemplateType::Wakeup,
            'title' => '唤醒',
            'content' => '好久不见',
        ]);

        $admin = AdminUser::create([
            'username' => 'broadcasttest',
            'password_hash' => 'password',
            'role' => AdminRole::SuperAdmin,
        ]);

        $this->actingAs($admin, 'admin');
    }

    public function test_immediate_send_sets_scheduled_time_to_now(): void
    {
        Livewire::test(CreateBroadcastTask::class)
            ->fillForm([
                'template_id' => $this->template->id,
                'target_scope' => 'all',
                'send_mode' => 'now',
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $task = BroadcastTask::query()->latest('id')->first();

        $this->assertNotNull($task);
        $this->assertTrue($task->scheduled_time->diffInSeconds(now()) < 10);
    }

    public function test_scheduled_send_keeps_the_chosen_time(): void
    {
        $scheduledTime = now()->addDay()->startOfMinute();

        Livewire::test(CreateBroadcastTask::class)
            ->fillForm([
                'template_id' => $this->template->id,
                'target_scope' => 'all',
                'send_mode' => 'scheduled',
                'scheduled_time' => $scheduledTime,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $task = BroadcastTask::query()->latest('id')->first();

        $this->assertNotNull($task);
        $this->assertTrue($task->scheduled_time->equalTo($scheduledTime));
    }

    public function test_scheduled_send_requires_a_time(): void
    {
        Livewire::test(CreateBroadcastTask::class)
            ->fillForm([
                'template_id' => $this->template->id,
                'target_scope' => 'all',
                'send_mode' => 'scheduled',
                'scheduled_time' => null,
            ])
            ->call('create')
            ->assertHasFormErrors(['scheduled_time' => 'required']);
    }
}
