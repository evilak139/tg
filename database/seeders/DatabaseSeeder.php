<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // 管理员账号通过安装向导创建（见07文档），points_config/message_templates 默认值同样由安装向导第3步写入，不在此处 seed。
    }
}
