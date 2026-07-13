<?php

namespace App\Console\Commands;

use App\Enums\ActivityTag;
use App\Enums\BroadcastStatus;
use App\Enums\MessageTemplateType;
use App\Models\BroadcastTask;
use App\Models\MessageTemplate;
use App\Models\User;
use App\Services\PointsConfigRepository;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

/**
 * 对应04文档"3. 会员活跃度层级更新"：按activity_tag_thresholds重新判定activity_tag，
 * 新进入"待唤醒"的用户（上次活跃这次变待唤醒）自动建一条"唤醒"群发任务。
 */
#[Signature('app:update-activity-tags')]
#[Description('按活跃度阈值重新判定会员的activity_tag')]
class UpdateActivityTags extends Command
{
    public function handle(PointsConfigRepository $config): void
    {
        $thresholds = $config->getJson('activity_tag_thresholds', [
            'active' => 7,
            'dormant' => 30,
            'churned' => 90,
        ]);

        $newlyDormantUserIds = [];
        $updatedCount = 0;

        User::query()->orderBy('id')->chunkById(500, function ($users) use ($thresholds, &$newlyDormantUserIds, &$updatedCount) {
            foreach ($users as $user) {
                $newTag = $this->resolveTag($user, $thresholds);

                if ($newTag === $user->activity_tag) {
                    continue;
                }

                if ($user->activity_tag === ActivityTag::Active && $newTag === ActivityTag::Dormant) {
                    $newlyDormantUserIds[] = $user->id;
                }

                $user->update(['activity_tag' => $newTag]);
                $updatedCount++;
            }
        });

        if (! empty($newlyDormantUserIds)) {
            $this->createWakeupBroadcast($newlyDormantUserIds);
        }

        $this->info("更新了 {$updatedCount} 个会员的活跃度层级，新增待唤醒 ".count($newlyDormantUserIds).' 人。');
    }

    /**
     * @param  array<string, int>  $thresholds
     */
    protected function resolveTag(User $user, array $thresholds): ActivityTag
    {
        $days = $user->last_active_time->diffInDays(now());

        return match (true) {
            $days <= ($thresholds['active'] ?? 7) => ActivityTag::Active,
            $days <= ($thresholds['dormant'] ?? 30) => ActivityTag::Dormant,
            $days <= ($thresholds['churned'] ?? 90) => ActivityTag::Churned,
            default => ActivityTag::DeeplyChurned,
        };
    }

    /**
     * @param  int[]  $userIds
     */
    protected function createWakeupBroadcast(array $userIds): void
    {
        $template = MessageTemplate::query()->where('type', MessageTemplateType::Wakeup)->first();

        if ($template === null) {
            return;
        }

        BroadcastTask::create([
            'template_id' => $template->id,
            'target_filter' => ['scope' => 'custom', 'user_ids' => $userIds],
            'scheduled_time' => now(),
            'status' => BroadcastStatus::Pending,
            'created_by' => 'system',
        ]);
    }
}
