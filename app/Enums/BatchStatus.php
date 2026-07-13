<?php

namespace App\Enums;

enum BatchStatus: string
{
    case Active = '有效';
    case Expired = '已过期';
}
