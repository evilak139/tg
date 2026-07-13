<?php

namespace App\Enums;

enum ActivityTag: string
{
    case Active = '活跃';
    case Dormant = '待唤醒';
    case Churned = '流失';
    case DeeplyChurned = '深度流失';
}
