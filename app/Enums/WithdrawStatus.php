<?php

namespace App\Enums;

enum WithdrawStatus: string
{
    case Pending = '待处理';
    case Completed = '已完成';
}
