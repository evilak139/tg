<x-filament-widgets::widget>
    @php($items = $this->getMissingSetupItems())
    @php($pendingMigrations = $this->getPendingMigrationsCount())

    @if ($pendingMigrations > 0)
        <div class="rounded-xl border border-red-300 bg-red-50 p-4 dark:border-red-600 dark:bg-red-950">
            <p class="font-medium text-red-800 dark:text-red-200">
                检测到 {{ $pendingMigrations }} 个数据库迁移尚未执行
            </p>
            <p class="mt-1 text-sm text-red-700 dark:text-red-300">
                代码已更新但数据库结构没跟上，可能导致部分功能静默报错。请尽快在服务器上执行
                <code class="rounded bg-red-100 px-1 dark:bg-red-900">php artisan migrate --force</code>
                并重启机器人轮询进程。
            </p>
        </div>
    @endif

    @if (count($items))
        <div class="rounded-xl border border-amber-300 bg-amber-50 p-4 dark:border-amber-600 dark:bg-amber-950 mt-4">
            <p class="font-medium text-amber-800 dark:text-amber-200">请先完成机器人和域名配置</p>
            <ul class="mt-2 list-disc pl-5 text-sm text-amber-700 dark:text-amber-300">
                @foreach ($items as $item)
                    <li>{{ $item }}</li>
                @endforeach
            </ul>
        </div>
    @endif
</x-filament-widgets::widget>
