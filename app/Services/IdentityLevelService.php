<?php

namespace App\Services;

use App\Enums\IdentityLevel;
use App\Models\User;

/**
 * 对应01/02文档："identity_level 基于累计邀请人数计算"、"按累计邀请人数阶梯判定"。
 *
 * TODO(需确认): 01/02文档都没有给出 注册会员→邀请达人→团队长→VIP大使 具体的人数阈值，
 * 这里复用邀请里程碑奖励的 5/20/100 人阈值作为身份等级阶梯分界，是本实现的假设，需产品确认。
 */
class IdentityLevelService
{
    public function recompute(User $inviter): void
    {
        $directCount = User::query()->where('invited_by_l1', $inviter->id)->count();

        $level = match (true) {
            $directCount >= 100 => IdentityLevel::VipAmbassador,
            $directCount >= 20 => IdentityLevel::TeamLeader,
            $directCount >= 5 => IdentityLevel::InviteExpert,
            default => IdentityLevel::RegisteredMember,
        };

        if ($inviter->identity_level !== $level) {
            $inviter->update(['identity_level' => $level]);
        }
    }
}
