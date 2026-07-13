<?php

namespace Tests\Feature;

use App\Enums\AdminRole;
use App\Enums\BroadcastStatus;
use App\Enums\MessageTemplateType;
use App\Filament\Resources\BroadcastTasks\Pages\ListBroadcastTasks;
use App\Filament\Resources\BroadcastTasks\Tables\BroadcastTasksTable;
use App\Models\AdminUser;
use App\Models\BroadcastTask;
use App\Models\MessageTemplate;
use Filament\Actions\DeleteAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * 对应用户反馈："无论选立即发送还是定时发送，都会变成定时发送"（列表页看不出区别）
 * 和"定时发送的任务新增一个删除功能"。
 */
class BroadcastTaskTableTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $admin = AdminUser::create([
            'username' => 'broadcasttabletest',
            'password_hash' => 'password',
            'role' => AdminRole::SuperAdmin,
        ]);

        $this->actingAs($admin, 'admin');
    }

    protected function makeTemplate(): MessageTemplate
    {
        return MessageTemplate::create([
            'type' => MessageTemplateType::Wakeup,
            'title' => '唤醒',
            'content' => '好久不见',
        ]);
    }

    public function test_immediate_and_scheduled_tasks_are_visually_distinguishable(): void
    {
        $template = $this->makeTemplate();

        $immediate = BroadcastTask::create([
            'template_id' => $template->id,
            'target_filter' => ['scope' => 'all'],
            'scheduled_time' => now(),
            'status' => BroadcastStatus::Pending,
        ]);

        $scheduled = BroadcastTask::create([
            'template_id' => $template->id,
            'target_filter' => ['scope' => 'all'],
            'scheduled_time' => now()->addDays(3),
            'status' => BroadcastStatus::Pending,
        ]);

        // 列表页确实要渲染成功（不抛异常），具体文案断言走下面的直接调用——
        // Livewire的wire:snapshot把页面状态JSON化内嵌进HTML时会把中文转成\uXXXX
        // 转义序列，assertSee()按字面UTF-8子串找，找不到转义后的文本，属于断言
        // 手法的问题，不代表功能没生效（用grep在原始响应里确认过转义后的内容
        // 确实是"立即发送（...）"）。
        Livewire::test(ListBroadcastTasks::class)->assertSuccessful();

        $this->assertStringContainsString('立即发送', BroadcastTasksTable::describeSchedule($immediate->fresh()));
        $this->assertStringContainsString('定时：', BroadcastTasksTable::describeSchedule($scheduled->fresh()));
    }

    public function test_pending_task_can_be_deleted_from_the_list(): void
    {
        $template = $this->makeTemplate();

        $task = BroadcastTask::create([
            'template_id' => $template->id,
            'target_filter' => ['scope' => 'all'],
            'scheduled_time' => now(),
            'status' => BroadcastStatus::Pending,
        ]);

        Livewire::test(ListBroadcastTasks::class)
            ->callTableAction(DeleteAction::class, $task);

        $this->assertDatabaseMissing('broadcast_tasks', ['id' => $task->id]);
    }

    public function test_non_pending_task_can_also_be_deleted(): void
    {
        $template = $this->makeTemplate();

        $task = BroadcastTask::create([
            'template_id' => $template->id,
            'target_filter' => ['scope' => 'all'],
            'scheduled_time' => now(),
            'status' => BroadcastStatus::Completed,
        ]);

        Livewire::test(ListBroadcastTasks::class)
            ->callTableAction(DeleteAction::class, $task);

        $this->assertDatabaseMissing('broadcast_tasks', ['id' => $task->id]);
    }
}
