<?php

namespace App\Http\Controllers\Install;

use App\Enums\AdminRole;
use App\Http\Controllers\Controller;
use App\Models\AdminUser;
use App\Services\EnvFileWriter;
use Database\Seeders\MessageTemplateSeeder;
use Database\Seeders\PointsConfigSeeder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;
use PDO;
use PDOException;
use Throwable;

/**
 * 对应07文档"安装流程"全部5步。
 *
 * 步骤间的先后顺序保护不用session记录（早期版本这么做过，但第2步一旦把数据库连接
 * 切到用户刚填的新库，那个库在第3步migrate之前是没有sessions表的——SESSION_DRIVER=
 * database时Laravel每个请求结束都要写session，装向导自己还没主动碰session就先崩了。
 * 现在改成直接从"数据库实际处于什么状态"推导每一步是否可以访问：环境检测是否通过、
 * mysql连接是否能连上、核心表是否已经migrate。这样不管session用的是file还是database
 * 驱动、请求之间有没有连续性，都不影响流程判断是否正确，天然更健壮。
 */
class InstallController extends Controller
{
    /** @var string[] Laravel/项目实际用到的必需扩展，见00文档技术栈 */
    protected const REQUIRED_EXTENSIONS = ['pdo_mysql', 'bcmath', 'mbstring', 'openssl', 'tokenizer', 'xml', 'ctype', 'json', 'curl'];

    public function index(): RedirectResponse
    {
        return redirect()->route('install.environment');
    }

    // ---------- 第1步：环境检测 ----------

    public function environment(): View
    {
        return view('install.environment', ['checks' => $this->runEnvironmentChecks()]);
    }

    public function environmentContinue(): RedirectResponse
    {
        if (! $this->environmentPassed()) {
            return back()->withErrors(['environment' => '存在未通过的必需检查项，请先解决。']);
        }

        return redirect()->route('install.database');
    }

    /**
     * @return array<int, array{label: string, pass: bool, detail: string}>
     */
    protected function runEnvironmentChecks(): array
    {
        $checks = [];

        $checks[] = [
            'label' => 'PHP 版本 >= 8.3',
            'pass' => version_compare(PHP_VERSION, '8.3.0', '>='),
            'detail' => '当前版本：'.PHP_VERSION,
        ];

        foreach (self::REQUIRED_EXTENSIONS as $extension) {
            $checks[] = [
                'label' => "PHP 扩展：{$extension}",
                'pass' => extension_loaded($extension),
                'detail' => extension_loaded($extension) ? '已安装' : '未安装',
            ];
        }

        $checks[] = [
            'label' => 'PHP 扩展：gd 或 imagick（海报生成需要）',
            'pass' => extension_loaded('gd') || extension_loaded('imagick'),
            'detail' => extension_loaded('gd') ? 'gd 已安装' : (extension_loaded('imagick') ? 'imagick 已安装' : '均未安装'),
        ];

        foreach (['storage', 'bootstrap/cache'] as $dir) {
            $path = base_path($dir);
            $checks[] = [
                'label' => "目录可写：{$dir}",
                'pass' => is_writable($path),
                'detail' => $path,
            ];
        }

        return $checks;
    }

    protected function environmentPassed(): bool
    {
        return collect($this->runEnvironmentChecks())->every(fn ($check) => $check['pass']);
    }

    // ---------- 第2步：数据库配置 ----------

    public function database(): View|RedirectResponse
    {
        if (! $this->environmentPassed()) {
            return redirect()->route('install.environment');
        }

        return view('install.database');
    }

    public function databaseStore(Request $request, EnvFileWriter $envWriter): RedirectResponse
    {
        if (! $this->environmentPassed()) {
            return redirect()->route('install.environment');
        }

        $data = $request->validate([
            'host' => ['required', 'string'],
            'port' => ['required', 'integer'],
            'database' => ['required', 'string'],
            'username' => ['required', 'string'],
            'password' => ['nullable', 'string'],
        ]);

        try {
            new PDO(
                "mysql:host={$data['host']};port={$data['port']};dbname={$data['database']}",
                $data['username'],
                $data['password'] ?? '',
                [PDO::ATTR_TIMEOUT => 5]
            );
        } catch (PDOException $e) {
            return back()->withInput()->withErrors(['connection' => '数据库连接失败：'.$e->getMessage()]);
        }

        $envWriter->update([
            'DB_CONNECTION' => 'mysql',
            'DB_HOST' => $data['host'],
            'DB_PORT' => (string) $data['port'],
            'DB_DATABASE' => $data['database'],
            'DB_USERNAME' => $data['username'],
            'DB_PASSWORD' => $data['password'] ?? '',
        ]);

        config([
            'database.default' => 'mysql',
            'database.connections.mysql.host' => $data['host'],
            'database.connections.mysql.port' => $data['port'],
            'database.connections.mysql.database' => $data['database'],
            'database.connections.mysql.username' => $data['username'],
            'database.connections.mysql.password' => $data['password'] ?? '',
        ]);
        DB::purge('mysql');

        return redirect()->route('install.migrate');
    }

    // ---------- 第3步：初始化数据库 ----------

    public function migrate(): View|RedirectResponse
    {
        if (! $this->databaseConnected()) {
            return redirect()->route('install.database');
        }

        return view('install.migrate');
    }

    public function migrateStore(): RedirectResponse
    {
        if (! $this->databaseConnected()) {
            return redirect()->route('install.database');
        }

        try {
            Artisan::call('migrate', ['--force' => true]);
            app(PointsConfigSeeder::class)->run();
            app(MessageTemplateSeeder::class)->run();
        } catch (Throwable $e) {
            return back()->withErrors(['migrate' => '初始化数据库失败：'.$e->getMessage()]);
        }

        return redirect()->route('install.admin');
    }

    protected function databaseConnected(): bool
    {
        try {
            DB::connection('mysql')->getPdo();

            return true;
        } catch (Throwable) {
            return false;
        }
    }

    // ---------- 第4/5步：创建管理员 + 完成安装 ----------

    public function admin(): View|RedirectResponse
    {
        if (! $this->databaseMigrated()) {
            return redirect()->route('install.migrate');
        }

        return view('install.admin');
    }

    public function adminStore(Request $request): RedirectResponse
    {
        if (! $this->databaseMigrated()) {
            return redirect()->route('install.migrate');
        }

        $data = $request->validate([
            'username' => ['required', 'string', 'max:255'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $admin = AdminUser::create([
            'username' => $data['username'],
            'password_hash' => $data['password'],
            'role' => AdminRole::SuperAdmin,
        ]);

        $lockPath = config('install.lock_path');
        File::ensureDirectoryExists(dirname($lockPath));
        File::put($lockPath, '安装完成时间：'.now()->toDateTimeString());

        Auth::guard('admin')->login($admin);

        return redirect('/admin');
    }

    protected function databaseMigrated(): bool
    {
        try {
            return Schema::connection('mysql')->hasTable('admin_users');
        } catch (Throwable) {
            return false;
        }
    }
}
