<?php

namespace App\Enums;

/**
 * 域名池（domains）与机器人（bots）共用的启用状态，二者取值范围相同。
 */
enum EnableStatus: string
{
    case Enabled = '启用';
    case Disabled = '禁用';
    case Abnormal = '异常';
}
