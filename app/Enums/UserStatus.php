<?php

namespace App\Enums;

enum UserStatus: string
{
    case Normal = '正常';
    case Blacklisted = '拉黑';
}
