<?php

namespace App\Enums;

enum BroadcastStatus: string
{
    case Pending = '待发送';
    case Sending = '发送中';
    case Completed = '已完成';
    case Failed = '失败';
}
