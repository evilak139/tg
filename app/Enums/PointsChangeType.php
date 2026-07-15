<?php

namespace App\Enums;

enum PointsChangeType: string
{
    case Checkin = '签到';
    case InviteL1Commission = '邀请一级返佣';
    case InviteL2Commission = '邀请二级返佣';
    case InviteL3Commission = '邀请三级返佣';
    case MilestoneBonus = '里程碑奖励';
    case LeaderboardBonus = '排行榜奖励';
    case NewAccountGift = '新人礼包';
    case ExchangeDeduction = '兑换扣除';
    case AdminAdjustment = '后台调整';
    case Expiration = '过期清零';

    /**
     * 面向客户端（巴西用户）展示用的葡语文案。value本身保持中文不变——它是
     * points_ledger.change_type持久化存储的值，改动会导致历史行数据与新枚举
     * case对不上（Eloquent enum cast按value反查，无法识别改名前的旧值）。
     * 后台管理界面继续展示中文原始value，只有发给Telegram用户的文案走这个方法。
     */
    public function label(): string
    {
        return match ($this) {
            self::Checkin => 'Check-in',
            self::InviteL1Commission => 'Comissão de convite Nível 1',
            self::InviteL2Commission => 'Comissão de convite Nível 2',
            self::InviteL3Commission => 'Comissão de convite Nível 3',
            self::MilestoneBonus => 'Bônus de meta',
            self::LeaderboardBonus => 'Bônus de ranking',
            self::NewAccountGift => 'Presente de boas-vindas',
            self::ExchangeDeduction => 'Troca de pontos',
            self::AdminAdjustment => 'Ajuste administrativo',
            self::Expiration => 'Expiração de pontos',
        };
    }
}
