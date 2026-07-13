<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

/**
 * 修复安装向导的一个真实bug：SESSION_DRIVER=database时，Laravel每个请求结束都要往
 * sessions表写数据（哪怕业务代码根本没主动用session），但安装向导第2步刚把数据库
 * 连接切到用户填的新库时，那个库还没跑migrate，sessions表根本不存在，一写就报
 * "Base table or view not found"，整个安装流程直接崩掉。
 *
 * 这里在StartSession之前判断：只要当前配置的mysql连接下sessions表还不存在（库没配好，
 * 或者配好了但还没跑第3步migrate），就强制临时改用file驱动，彻底绕开这张还不存在的表；
 * 一旦migrate跑完、sessions表真实存在了，后续请求自然切回正常的database驱动，
 * 不需要额外收尾逻辑。
 */
class UseFileSessionsUntilMigrated
{
    public function handle(Request $request, Closure $next): Response
    {
        if (config('session.driver') === 'database' && ! $this->sessionsTableReady()) {
            config(['session.driver' => 'file']);
        }

        return $next($request);
    }

    protected function sessionsTableReady(): bool
    {
        try {
            return Schema::connection(config('session.connection'))->hasTable('sessions');
        } catch (Throwable) {
            return false;
        }
    }
}
