<?php

namespace App\Enums;

enum IdentityLevel: string
{
    case RegisteredMember = '注册会员';
    case InviteExpert = '邀请达人';
    case TeamLeader = '团队长';
    case VipAmbassador = 'VIP大使';
}
