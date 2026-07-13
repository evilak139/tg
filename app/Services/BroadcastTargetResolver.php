<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

/**
 * 对应03.6文档broadcast_tasks.target_filter："筛选条件：活跃度层级/身份等级/自定义用户ID列表/全体"。
 *
 * target_filter 约定的JSON结构：
 * {"scope": "all"}
 * {"scope": "activity_tag", "values": ["活跃", "待唤醒"]}
 * {"scope": "identity_level", "values": ["邀请达人"]}
 * {"scope": "custom", "user_ids": [1, 2, 3]}
 */
class BroadcastTargetResolver
{
    /**
     * @param  array<string, mixed>  $filter
     * @return Collection<int, User>
     */
    public function resolve(array $filter): Collection
    {
        $scope = $filter['scope'] ?? 'all';

        return match ($scope) {
            'activity_tag' => User::query()->whereIn('activity_tag', $filter['values'] ?? [])->get(),
            'identity_level' => User::query()->whereIn('identity_level', $filter['values'] ?? [])->get(),
            'custom' => User::query()->whereIn('id', $filter['user_ids'] ?? [])->get(),
            default => User::query()->get(),
        };
    }
}
