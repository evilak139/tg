<?php

namespace App\Enums;

enum IdentityLevel: string
{
    case RegisteredMember = '注册会员';
    case InviteExpert = '邀请达人';
    case TeamLeader = '团队长';
    case VipAmbassador = 'VIP大使';

    /**
     * 面向客户端（巴西用户）展示用的葡语文案，见PointsChangeType::label()同样的
     * 理由：value是users.identity_level持久化存储的值，不能直接改。
     */
    public function label(): string
    {
        return match ($this) {
            self::RegisteredMember => 'Membro registrado',
            self::InviteExpert => 'Expert em convites',
            self::TeamLeader => 'Líder de equipe',
            self::VipAmbassador => 'Embaixador VIP',
        };
    }
}
