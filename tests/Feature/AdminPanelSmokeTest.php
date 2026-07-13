<?php

namespace Tests\Feature;

use App\Enums\AdminRole;
use App\Enums\EnableStatus;
use App\Models\AdminUser;
use App\Models\Domain;
use App\Models\MessageTemplate;
use Database\Seeders\MessageTemplateSeeder;
use Database\Seeders\PointsConfigSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminPanelSmokeTest extends TestCase
{
    use RefreshDatabase;

    public function test_all_admin_pages_render_for_super_admin(): void
    {
        $this->seed(PointsConfigSeeder::class);
        $this->seed(MessageTemplateSeeder::class);
        Domain::create(['domain' => 'go.example.com', 'status' => EnableStatus::Enabled]);

        $admin = AdminUser::create([
            'username' => 'smoketest',
            'password_hash' => 'password',
            'role' => AdminRole::SuperAdmin,
        ]);

        $this->actingAs($admin, 'admin');

        $pages = [
            '/admin',
            '/admin/users',
            '/admin/bots',
            '/admin/bots/create',
            '/admin/domains',
            '/admin/domains/create',
            '/admin/message-templates',
            '/admin/message-templates/create',
            '/admin/points-ledgers',
            '/admin/withdraw-requests',
            '/admin/broadcast-tasks',
            '/admin/broadcast-tasks/create',
            '/admin/manage-points-config',
            '/admin/admin-users',
            '/admin/admin-users/create',
        ];

        foreach ($pages as $page) {
            $response = $this->get($page);
            $response->assertSuccessful();
        }
    }

    /**
     * 对应真实生产环境报过的bug：编辑页从模型读出的type是Eloquent enum cast之后的
     * 枚举实例，不是字符串，MessageTemplateForm里两处用到$get('type')的地方都要能
     * 处理这种情况。列表页（上面那个测试已覆盖）不会触发，必须真的打开一条已存在
     * 记录的编辑页才能复现，所以单独测，覆盖全部7种类型（各自的专属变量分支都过一遍）。
     */
    public function test_every_message_template_edit_page_renders(): void
    {
        $this->seed(MessageTemplateSeeder::class);

        $admin = AdminUser::create([
            'username' => 'smoketest2',
            'password_hash' => 'password',
            'role' => AdminRole::SuperAdmin,
        ]);

        $this->actingAs($admin, 'admin');

        foreach (MessageTemplate::query()->pluck('id') as $id) {
            $this->get("/admin/message-templates/{$id}/edit")->assertSuccessful();
        }
    }
}
