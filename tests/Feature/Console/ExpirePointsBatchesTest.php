<?php

namespace Tests\Feature\Console;

use App\Enums\BatchStatus;
use App\Jobs\SendPointsExpiryReminderJob;
use App\Models\PointsMonthlyBatch;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class ExpirePointsBatchesTest extends TestCase
{
    use RefreshDatabase;

    public function test_expired_batch_with_remaining_points_is_zeroed_out(): void
    {
        $user = User::factory()->create(['points_balance' => 100]);

        $batch = PointsMonthlyBatch::create([
            'user_id' => $user->id,
            'batch_month' => now()->subYear()->format('Y-m'),
            'points_earned_total' => 50,
            'points_consumed_total' => 20,
            'status' => BatchStatus::Active,
            'expire_at' => now()->subDay(),
        ]);

        $this->artisan('app:expire-points-batches')->assertSuccessful();

        $user->refresh();
        $batch->refresh();

        $this->assertSame(70, $user->points_balance); // 100 - (50-20)
        $this->assertSame(BatchStatus::Expired, $batch->status);
        $this->assertSame(50, $batch->points_consumed_total);

        $this->assertDatabaseHas('points_ledger', [
            'user_id' => $user->id,
            'change_type' => '过期清零',
            'amount' => -30,
        ]);
    }

    public function test_batch_expiring_within_seven_days_triggers_reminder_job(): void
    {
        Queue::fake();

        $user = User::factory()->create();

        $batch = PointsMonthlyBatch::create([
            'user_id' => $user->id,
            'batch_month' => now()->format('Y-m'),
            'points_earned_total' => 30,
            'points_consumed_total' => 0,
            'status' => BatchStatus::Active,
            'expire_at' => now()->addDays(3),
        ]);

        $this->artisan('app:expire-points-batches')->assertSuccessful();

        Queue::assertPushed(SendPointsExpiryReminderJob::class, fn ($job) => $job->userId === $user->id && $job->batchId === $batch->id
        );
    }
}
