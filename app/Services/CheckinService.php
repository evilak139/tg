<?php

namespace App\Services;

use App\Enums\PointsChangeType;
use App\Models\Checkin;
use App\Models\User;

/**
 * 对应02文档"签到"一节：签到积分、连续天数、首次签到触发3级邀请返佣、里程碑奖励、
 * 身份等级更新。
 */
class CheckinService
{
    /** @var int[] 邀请里程碑阈值，见00/02文档 */
    protected const MILESTONES = [5, 20, 100];

    public function __construct(
        private readonly PointsService $pointsService,
        private readonly PointsConfigRepository $config,
        private readonly IdentityLevelService $identityLevelService,
    ) {}

    /**
     * @return array{alreadyCheckedIn: bool, pointsEarned: int, streak: int}
     */
    public function checkin(User $user): array
    {
        $today = now()->toDateString();

        $already = Checkin::query()
            ->where('user_id', $user->id)
            ->whereDate('checkin_date', $today)
            ->exists();

        if ($already) {
            return ['alreadyCheckedIn' => true, 'pointsEarned' => 0, 'streak' => $user->checkin_streak];
        }

        $isFirstCheckin = ! Checkin::query()->where('user_id', $user->id)->exists();

        $streak = $this->nextStreak($user);
        $pointsEarned = $this->calculatePoints($streak);

        Checkin::create([
            'user_id' => $user->id,
            'checkin_date' => $today,
            'streak_at_checkin' => $streak,
            'points_earned' => $pointsEarned,
        ]);

        if ($pointsEarned > 0) {
            $this->pointsService->award($user, PointsChangeType::Checkin, $pointsEarned);
        }

        $user->update([
            'last_active_time' => now(),
            'last_checkin_date' => $today,
            'checkin_streak' => $streak,
        ]);

        if ($isFirstCheckin) {
            $this->triggerInviteCommission($user);
        }

        return ['alreadyCheckedIn' => false, 'pointsEarned' => $pointsEarned, 'streak' => $streak];
    }

    protected function nextStreak(User $user): int
    {
        if ($user->last_checkin_date !== null && $user->last_checkin_date->isYesterday()) {
            return $user->checkin_streak + 1;
        }

        return 1;
    }

    protected function calculatePoints(int $streak): int
    {
        $base = $this->config->getInt('checkin_base_points', 0);
        $rules = $this->config->getJson('checkin_streak_bonus_rule', []);

        $bonus = 0;

        foreach ($rules as $rule) {
            if ((int) ($rule['streak'] ?? 0) === $streak) {
                $bonus = (int) ($rule['bonus'] ?? 0);
                break;
            }
        }

        return $base + $bonus;
    }

    /**
     * 被邀请人完成首次签到后才发放邀请返佣，不做"关注即发放"（对应CLAUDE.md不可违反的设计约束）。
     */
    protected function triggerInviteCommission(User $user): void
    {
        if ($user->invited_by_l1 === null) {
            return;
        }

        $l1 = User::find($user->invited_by_l1);

        if ($l1 === null) {
            return;
        }

        $l2 = $user->invited_by_l2 ? User::find($user->invited_by_l2) : null;
        $l3 = $user->invited_by_l3 ? User::find($user->invited_by_l3) : null;

        $l1Points = $this->config->getInt('invite_l1_points', 0);
        $l2Points = $this->config->getInt('invite_l2_points', 0);
        $l3Points = $this->config->getInt('invite_l3_points', 0);

        if ($l1Points > 0) {
            $this->pointsService->award($l1, PointsChangeType::InviteL1Commission, $l1Points, $user);
        }

        if ($l2 !== null && $l2Points > 0) {
            $this->pointsService->award($l2, PointsChangeType::InviteL2Commission, $l2Points, $user);
        }

        if ($l3 !== null && $l3Points > 0) {
            $this->pointsService->award($l3, PointsChangeType::InviteL3Commission, $l3Points, $user);
        }

        $this->checkMilestone($l1);
        $this->identityLevelService->recompute($l1);
    }

    protected function checkMilestone(User $inviter): void
    {
        $directCount = User::query()->where('invited_by_l1', $inviter->id)->count();
        $claimed = $inviter->milestones_claimed ?? [];
        $newlyClaimed = false;

        foreach (self::MILESTONES as $milestone) {
            if ($directCount >= $milestone && ! in_array($milestone, $claimed, true)) {
                $bonus = $this->config->getInt("milestone_{$milestone}_bonus", 0);

                if ($bonus > 0) {
                    $this->pointsService->award($inviter, PointsChangeType::MilestoneBonus, $bonus);
                }

                $claimed[] = $milestone;
                $newlyClaimed = true;
            }
        }

        if ($newlyClaimed) {
            $inviter->update(['milestones_claimed' => $claimed]);
        }
    }
}
