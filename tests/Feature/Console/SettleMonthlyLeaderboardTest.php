<?php

namespace Tests\Feature\Console;

use App\Enums\BroadcastStatus;
use App\Enums\MessageTemplateType;
use App\Models\BroadcastTask;
use App\Models\LeaderboardSnapshot;
use App\Models\MessageTemplate;
use App\Models\User;
use Database\Seeders\PointsConfigSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SettleMonthlyLeaderboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_settles_last_month_ranking_and_awards_and_broadcasts(): void
    {
        $this->seed(PointsConfigSeeder::class);
        MessageTemplate::create([
            'type' => MessageTemplateType::MonthlyLeaderboard,
            'title' => '排行榜',
            'content' => '{ranking_convites_mes}',
        ]);

        $lastMonth = now()->subMonthNoOverflow();

        $topInviter = User::factory()->create(['points_balance' => 0]);
        $secondInviter = User::factory()->create(['points_balance' => 0]);

        // topInviter 上月邀请了3人，secondInviter 邀请了1人
        User::factory()->count(3)->create([
            'invited_by_l1' => $topInviter->id,
            'register_time' => $lastMonth->copy()->startOfMonth()->addDays(2),
        ]);
        User::factory()->count(1)->create([
            'invited_by_l1' => $secondInviter->id,
            'register_time' => $lastMonth->copy()->startOfMonth()->addDays(5),
        ]);
        // 本月（非上月）的邀请不应该计入上月排行榜
        User::factory()->count(5)->create([
            'invited_by_l1' => $secondInviter->id,
            'register_time' => now(),
        ]);

        $this->artisan('app:settle-monthly-leaderboard')->assertSuccessful();

        $period = $lastMonth->format('Y-m');

        $first = LeaderboardSnapshot::query()->where('period', $period)->where('rank', 1)->first();
        $second = LeaderboardSnapshot::query()->where('period', $period)->where('rank', 2)->first();

        $this->assertSame($topInviter->id, $first->user_id);
        $this->assertSame(3, $first->invite_count_this_period);
        $this->assertSame($secondInviter->id, $second->user_id);
        $this->assertSame(1, $second->invite_count_this_period);

        $topInviter->refresh();
        $this->assertGreaterThan(0, $topInviter->points_balance);

        $this->assertDatabaseHas('broadcast_tasks', [
            'status' => BroadcastStatus::Pending,
        ]);

        $task = BroadcastTask::query()->latest('id')->first();
        $this->assertSame(['scope' => 'all'], $task->target_filter);
    }

    public function test_running_twice_does_not_duplicate_settlement(): void
    {
        $this->seed(PointsConfigSeeder::class);
        MessageTemplate::create([
            'type' => MessageTemplateType::MonthlyLeaderboard,
            'title' => '排行榜',
            'content' => '{ranking_convites_mes}',
        ]);

        $this->artisan('app:settle-monthly-leaderboard')->assertSuccessful();
        $countAfterFirst = LeaderboardSnapshot::query()->count();

        $this->artisan('app:settle-monthly-leaderboard')->assertSuccessful();
        $countAfterSecond = LeaderboardSnapshot::query()->count();

        $this->assertSame($countAfterFirst, $countAfterSecond);
    }
}
