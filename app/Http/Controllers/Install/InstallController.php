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
use Illuminate\View\View;
use PDO;
use PDOException;
use Throwable;

/**
 * 对应07文档"安装流程"全部5步。用session记录已完成到第几步，跳着访问URL会被
 * 打回上一个没完成的步骤（防止绕过数据库配置直接建管理员之类的操作）。
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
        $checks = $this->runEnvironmentChecks();

        if (collect($checks)->contains(fn ($check) => ! $check['pass'])) {
            return back()->withErrors(['environment' => '存在未通过的必需检查项，请先解决。']);
        }

        session(['install.step' => 'database']);

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

    // ---------- 第2步：数据库配置 ----------

    public function database(): View|RedirectResponse
    {
        if (! $this->hasCompletedStep('database')) {
            return redirect()->route('install.environment');
        }

        return view('install.database');
    }

    public function databaseStore(Request $request, EnvFileWriter $envWriter): RedirectResponse
    {
        if (! $this->hasCompletedStep('database')) {
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

        session(['install.step' => 'migrate']);

        return redirect()->route('install.migrate');
    }

    // ---------- 第3步：初始化数据库 ----------

    public function migrate(): View|RedirectResponse
    {
        if (! $this->hasCompletedStep('migrate')) {
            return redirect()->route('install.database');
        }

        return view('install.migrate');
    }

    public function migrateStore(): RedirectResponse
    {
        if (! $this->hasCompletedStep('migrate')) {
            return redirect()->route('install.database');
        }

        try {
            Artisan::call('migrate', ['--force' => true]);
            app(PointsConfigSeeder::class)->run();
            app(MessageTemplateSeeder::class)->run();
        } catch (Throwable $e) {
            return back()->withErrors(['migrate' => '初始化数据库失败：'.$e->getMessage()]);
        }

        session(['install.step' => 'admin']);

        return redirect()->route('install.admin');
    }

    // ---------- 第4/5步：创建管理员 + 完成安装 ----------

    public function admin(): View|RedirectResponse
    {
        if (! $this->hasCompletedStep('admin')) {
            return redirect()->route('install.migrate');
        }

        return view('install.admin');
    }

    public function adminStore(Request $request): RedirectResponse
    {
        if (! $this->hasCompletedStep('admin')) {
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

        session()->forget('install.step');
        Auth::guard('admin')->login($admin);

        return redirect('/admin');
    }

    /**
     * 简单的步骤顺序保护：session里记录的是"当前允许访问的最远步骤"，
     * 请求某一步时只要该步骤已经被放行过（即curStep到达过这一步或之后）就算通过。
     */
    protected function hasCompletedStep(string $step): bool
    {
        $order = ['environment', 'database', 'migrate', 'admin'];
        $current = session('install.step', 'environment');

        return array_search($step, $order, true) <= array_search($current, $order, true);
    }
}
