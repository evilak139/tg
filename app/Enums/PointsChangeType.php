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
}
