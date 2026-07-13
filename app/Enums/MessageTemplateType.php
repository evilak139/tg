<?php

namespace App\Enums;

enum MessageTemplateType: string
{
    case Welcome = '欢迎';
    case Invite = '邀请';
    case Checkin = '签到';
    case Profile = '我的';
    case Wakeup = '唤醒';
    case PointsExpiry = '积分到期提醒';
    case MonthlyLeaderboard = '月度排行榜';
}
