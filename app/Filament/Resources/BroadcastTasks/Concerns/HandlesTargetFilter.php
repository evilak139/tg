<?php

namespace App\Filament\Resources\BroadcastTasks\Concerns;

trait HandlesTargetFilter
{
    /**
     * 把表单里的虚拟字段（target_scope等）组装成真正要存的target_filter JSON，
     * 并从$data里去掉这些虚拟字段（它们不是broadcast_tasks表的列）。
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function packTargetFilter(array $data): array
    {
        $scope = $data['target_scope'] ?? 'all';

        $data['target_filter'] = match ($scope) {
            'activity_tag' => ['scope' => 'activity_tag', 'values' => $data['target_activity_tags'] ?? []],
            'identity_level' => ['scope' => 'identity_level', 'values' => $data['target_identity_levels'] ?? []],
            'custom' => ['scope' => 'custom', 'user_ids' => array_map('intval', $data['target_user_ids'] ?? [])],
            default => ['scope' => 'all'],
        };

        unset($data['target_scope'], $data['target_activity_tags'], $data['target_identity_levels'], $data['target_user_ids']);

        return $data;
    }

    /**
     * 编辑页面回填：把已保存的target_filter JSON拆回表单虚拟字段。
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function unpackTargetFilter(array $data): array
    {
        $filter = $data['target_filter'] ?? ['scope' => 'all'];
        $data['target_scope'] = $filter['scope'] ?? 'all';
        $data['target_activity_tags'] = $filter['values'] ?? [];
        $data['target_identity_levels'] = $filter['values'] ?? [];
        $data['target_user_ids'] = $filter['user_ids'] ?? [];

        return $data;
    }
}
