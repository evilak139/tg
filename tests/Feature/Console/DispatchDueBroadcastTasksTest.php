<?php

namespace Tests\Feature\Console;

use App\Enums\BroadcastStatus;
use App\Enums\MessageTemplateType;
use App\Jobs\DispatchBroadcastTaskJob;
use App\Models\BroadcastTask;
use App\Models\MessageTemplate;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class DispatchDueBroadcastTasksTest extends TestCase
{
    use RefreshDatabase;

    public function test_only_due_pending_tasks_are_dispatched(): void
    {
        Queue::fake();

        $template = MessageTemplate::create([
            'type' => MessageTemplateType::Wakeup,
            'title' => '唤醒',
            'content' => '好久不见',
        ]);

        $due = BroadcastTask::create([
            'template_id' => $template->id,
            'target_filter' => ['scope' => 'all'],
            'scheduled_time' => now()->subMinute(),
            'status' => BroadcastStatus::Pending,
        ]);

        $future = BroadcastTask::create([
            'template_id' => $template->id,
            'target_filter' => ['scope' => 'all'],
            'scheduled_time' => now()->addHour(),
            'status' => BroadcastStatus::Pending,
        ]);

        $alreadySent = BroadcastTask::create([
            'template_id' => $template->id,
            'target_filter' => ['scope' => 'all'],
            'scheduled_time' => now()->subHour(),
            'status' => BroadcastStatus::Completed,
        ]);

        $this->artisan('app:dispatch-due-broadcast-tasks')->assertSuccessful();

        Queue::assertPushed(DispatchBroadcastTaskJob::class, fn ($job) => $job->broadcastTaskId === $due->id);
        Queue::assertNotPushed(DispatchBroadcastTaskJob::class, fn ($job) => $job->broadcastTaskId === $future->id);
        Queue::assertNotPushed(DispatchBroadcastTaskJob::class, fn ($job) => $job->broadcastTaskId === $alreadySent->id);
    }
}
