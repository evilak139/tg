<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Symfony\Component\HttpFoundation\Response;

/**
 * 对应07文档"是否已安装的判断方式"：不查数据库（环境检测/数据库配置阶段数据库可能
 * 还没配好），用文件锁 storage/app/installed.lock 判断。
 *
 * - 未安装且访问的不是 /install 相关路由 → 强制跳转 /install
 * - 已安装且访问的是 /install 相关路由 → 直接跳转 /admin（防止安装向导被重复触发）
 */
class EnsureInstalled
{
    public function handle(Request $request, Closure $next): Response
    {
        $installed = File::exists(config('install.lock_path'));
        $isInstallRoute = $request->is('install') || $request->is('install/*');

        if (! $installed && ! $isInstallRoute) {
            return redirect('/install');
        }

        if ($installed && $isInstallRoute) {
            return redirect('/admin');
        }

        return $next($request);
    }
}
