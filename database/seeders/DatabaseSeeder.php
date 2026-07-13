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
        // 生产环境这两个 Seeder 由安装向导（07文档）第3步调用；本地开发直接跑 db:seed 也能得到同样的默认配置。
        $this->call([
            PointsConfigSeeder::class,
            MessageTemplateSeeder::class,
        ]);

        // 管理员账号通过安装向导第4步创建，不在此处 seed。
    }
}
