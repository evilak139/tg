<?php

namespace App\Enums;

enum AdminRole: string
{
    case SuperAdmin = '超级管理员';
    case CustomerService = '客服';
    case Operations = '运营';
}
