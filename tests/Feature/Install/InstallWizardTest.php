<?php

namespace Tests\Feature\Install;

use App\Services\EnvFileWriter;
use Illuminate\Support\Facades\File;
use PDO;
use Tests\TestCase;

/**
 * 对应07文档"安装向导规格"全流程。
 *
 * 安全注意：
 * - installed.lock 路径通过 config('install.lock_path') 注入成临时文件（见
 *   tests/TestCase.php 和 config/install.php），完全不碰真实的
 *   storage/app/installed.lock，测试进程崩溃也不会误锁/误解锁开发机的安装状态。
 * - .env 通过EnvFileWriter注入临时文件路径，绝不写真实的.env。
 * - 数据库配置步骤用一个独立的一次性数据库（install_wizard_test），不碰项目实际用的
 *   tg_bot库，跑完在tearDown里直接DROP掉。
 * - EnvFileWriter::update()会用putenv()/$_ENV改写DB_*，这些是进程级副作用，PHPUnit
 *   同一进程内跑全部测试类，必须在tearDown里把它们还原，否则会污染同一次运行里
 *   跑在后面的其他测试的数据库连接。
 */
class InstallWizardTest extends TestCase
{
    protected string $tempEnvPath;

    protected string $notInstalledLockPath;

    protected string $testDatabase = 'install_wizard_test';

    /** @var array<string, string|false> */
    protected array $envSnapshot = [];

    protected const ENV_KEYS = ['DB_CONNECTION', 'DB_HOST', 'DB_PORT', 'DB_DATABASE', 'DB_USERNAME', 'DB_PASSWORD'];

    protected function setUp(): void
    {
        parent::setUp();

        // 基类默认把install.lock_path指向一个已存在的临时文件（"已安装"状态），
        // 这个测试类整体是在测"从未安装到安装完成"的流程，所以要改指向一个
        // 不存在的临时路径，模拟"未安装"。
        $this->notInstalledLockPath = sys_get_temp_dir().'/install_wizard_test_lock_'.uniqid();
        config(['install.lock_path' => $this->notInstalledLockPath]);

        $this->tempEnvPath = sys_get_temp_dir().'/install_wizard_test_'.uniqid().'.env';
        File::put($this->tempEnvPath, "APP_NAME=Test\n");

        $this->app->instance(EnvFileWriter::class, new EnvFileWriter($this->tempEnvPath));

        foreach (self::ENV_KEYS as $key) {
            $this->envSnapshot[$key] = getenv($key);
        }

        $pdo = new PDO('mysql:host=127.0.0.1;port=3306', 'root', 'admin889');
        $pdo->exec("DROP DATABASE IF EXISTS {$this->testDatabase}");
        $pdo->exec("CREATE DATABASE {$this->testDatabase} CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    }

    protected function tearDown(): void
    {
        File::delete($this->tempEnvPath);

        if (File::exists($this->notInstalledLockPath)) {
            File::delete($this->notInstalledLockPath);
        }

        $pdo = new PDO('mysql:host=127.0.0.1;port=3306', 'root', 'admin889');
        $pdo->exec("DROP DATABASE IF EXISTS {$this->testDatabase}");

        foreach ($this->envSnapshot as $key => $value) {
            if ($value === false) {
                putenv($key);
                unset($_ENV[$key], $_SERVER[$key]);
            } else {
                putenv("{$key}={$value}");
                $_ENV[$key] = $value;
                $_SERVER[$key] = $value;
            }
        }

        parent::tearDown();
    }

    public function test_unvisited_root_redirects_to_install_when_not_installed(): void
    {
        $this->get('/')->assertRedirect('/install');
    }

    public function test_install_routes_are_accessible_when_not_installed(): void
    {
        $this->get('/install/environment')->assertOk();
    }

    public function test_install_routes_redirect_to_admin_when_already_installed(): void
    {
        File::put($this->notInstalledLockPath, 'installed');

        $this->get('/install/environment')->assertRedirect('/admin');
    }

    public function test_cannot_skip_ahead_to_database_step_without_passing_environment_step(): void
    {
        $this->get('/install/database')->assertRedirect('/install/environment');
    }

    public function test_cannot_skip_ahead_to_migrate_step_without_completing_database_step(): void
    {
        $this->withSession(['install.step' => 'database']);

        $this->get('/install/migrate')->assertRedirect('/install/database');
    }

    public function test_cannot_skip_ahead_to_admin_step_without_completing_migrate_step(): void
    {
        $this->withSession(['install.step' => 'migrate']);

        $this->get('/install/admin')->assertRedirect('/install/migrate');
    }

    public function test_database_step_rejects_invalid_credentials(): void
    {
        $this->withSession(['install.step' => 'database']);

        $response = $this->post('/install/database', [
            'host' => '127.0.0.1',
            'port' => 3306,
            'database' => $this->testDatabase,
            'username' => 'root',
            'password' => 'definitely-wrong-password',
        ]);

        $response->assertSessionHasErrors('connection');
        $this->assertSame('database', session('install.step'));
    }

    public function test_full_wizard_happy_path(): void
    {
        // 第1步
        $this->withSession(['install.step' => 'environment']);
        $this->post('/install/environment')->assertRedirect('/install/database');
        $this->assertSame('database', session('install.step'));

        // 第2步
        $response = $this->post('/install/database', [
            'host' => '127.0.0.1',
            'port' => 3306,
            'database' => $this->testDatabase,
            'username' => 'root',
            'password' => 'admin889',
        ]);
        $response->assertRedirect('/install/migrate');
        $this->assertSame('migrate', session('install.step'));
        $this->assertStringContainsString('DB_DATABASE='.$this->testDatabase, File::get($this->tempEnvPath));

        // 第3步
        $this->post('/install/migrate')->assertRedirect('/install/admin');
        $this->assertSame('admin', session('install.step'));
        $this->assertDatabaseHas('points_config', ['key' => 'checkin_base_points']);
        $this->assertDatabaseHas('message_templates', ['type' => '欢迎']);

        // 第4/5步
        $response = $this->post('/install/admin', [
            'username' => 'wizardadmin',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);
        $response->assertRedirect('/admin');

        $this->assertDatabaseHas('admin_users', ['username' => 'wizardadmin', 'role' => '超级管理员']);
        $this->assertFileExists($this->notInstalledLockPath);
        $this->assertAuthenticated('admin');

        // 装完之后 /install 应该整体失效
        $this->get('/install/environment')->assertRedirect('/admin');
    }
}
