<?php

namespace Tests\Feature\Console;

use App\Enums\ActivityTag;
use App\Enums\BroadcastStatus;
use App\Enums\MessageTemplateType;
use App\Models\BroadcastTask;
use App\Models\MessageTemplate;
use App\Models\User;
use Database\Seeders\PointsConfigSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UpdateActivityTagsTest extends TestCase
{
    use RefreshDatabase;

    public function test_activity_tags_are_recalculated_and_wakeup_broadcast_created_for_newly_dormant(): void
    {
        $this->seed(PointsConfigSeeder::class);
        MessageTemplate::create([
            'type' => MessageTemplateType::Wakeup,
            'title' => '唤醒',
            'content' => '好久不见',
        ]);

        // 上次活跃是活跃，现在10天没动，应该变成待唤醒并触发广播
        $becomingDormant = User::factory()->create([
            'tg_user_id' => 1,
            'activity_tag' => ActivityTag::Active,
            'last_active_time' => now()->subDays(10),
        ]);

        // 一直是流失状态，现在还是流失，不应该重复触发广播
        $stillChurned = User::factory()->create([
            'tg_user_id' => 2,
            'activity_tag' => ActivityTag::Churned,
            'last_active_time' => now()->subDays(50),
        ]);

        // 最近活跃，应该保持活跃
        $stillActive = User::factory()->create([
            'tg_user_id' => 3,
            'activity_tag' => ActivityTag::Active,
            'last_active_time' => now()->subDays(1),
        ]);

        $this->artisan('app:update-activity-tags')->assertSuccessful();

        $this->assertSame(ActivityTag::Dormant, $becomingDormant->fresh()->activity_tag);
        $this->assertSame(ActivityTag::Churned, $stillChurned->fresh()->activity_tag);
        $this->assertSame(ActivityTag::Active, $stillActive->fresh()->activity_tag);

        $task = BroadcastTask::query()->where('status', BroadcastStatus::Pending)->first();
        $this->assertNotNull($task);
        $this->assertSame(['scope' => 'custom', 'user_ids' => [$becomingDormant->id]], $task->target_filter);
    }
}
