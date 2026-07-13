<?php

namespace App\Services;

use App\Enums\PointsChangeType;
use App\Models\User;

/**
 * 对应02文档"/start 处理逻辑（关注注册 + 邀请关系建立）"。
 *
 * 注意：这里不触发邀请返佣，返佣要等被邀请人首次签到才触发（见 CheckinService），
 * 这是刻意的防刷设计，不是遗漏（对应CLAUDE.md"不可违反的设计约束"）。
 */
class RegistrationService
{
    public function __construct(
        private readonly PointsService $pointsService,
        private readonly PointsConfigRepository $config,
    ) {}

    /**
     * @return array{user: User, isNew: bool}
     */
    public function handleStart(int $tgUserId, ?string $tgUsername, string $nickname, ?string $payload): array
    {
        $existing = User::query()->where('tg_user_id', $tgUserId)->first();

        if ($existing !== null) {
            $existing->update(['last_active_time' => now()]);

            return ['user' => $existing, 'isNew' => false];
        }

        $inviter = $this->resolveInviter($payload);

        $user = User::create([
            'tg_user_id' => $tgUserId,
            'tg_username' => $tgUsername,
            'nickname' => $nickname,
            'invited_by_l1' => $inviter?->id,
            'invited_by_l2' => $inviter?->invited_by_l1,
            'invited_by_l3' => $inviter?->invited_by_l2,
            'register_time' => now(),
            'last_active_time' => now(),
            // register_ip / device_fingerprint: Telegram Bot API 不会把这两项交给机器人侧，
            // 留空，后续如接入 WebApp 场景可在那一层补采集再回写。
        ]);

        $giftPoints = $this->config->getInt('new_account_gift_points', 0);

        if ($giftPoints > 0) {
            $this->pointsService->award($user, PointsChangeType::NewAccountGift, $giftPoints);
        }

        return ['user' => $user, 'isNew' => true];
    }

    /**
     * payload 为邀请人的 users.id（见02文档），非数字或找不到对应用户时视为无邀请人。
     */
    protected function resolveInviter(?string $payload): ?User
    {
        if ($payload === null || $payload === '' || ! ctype_digit($payload)) {
            return null;
        }

        return User::query()->find((int) $payload);
    }
}
