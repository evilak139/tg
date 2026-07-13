<?php

namespace Tests\Feature;

use App\Enums\AdminRole;
use App\Enums\EnableStatus;
use App\Models\AdminUser;
use App\Models\Domain;
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
}
