<?php

namespace App\Console\Commands;

use App\Enums\EnableStatus;
use App\Models\Domain;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Throwable;

/**
 * 对应04文档"2. 域名健康检测"：对每个域名发起可访问性检测，更新status/last_check_time/
 * last_check_result。异常域名自动从InviteLinkService的可分配池（status=启用）排除，
 * 不需要额外代码——InviteLinkService选域名时本来就只挑status=启用的。
 *
 * 手动"禁用"的域名不参与检测，避免健康检测把管理员主动禁用的域名又扒回"启用"。
 */
#[Signature('app:check-domain-health')]
#[Description('检测域名池可访问性')]
class CheckDomainHealth extends Command
{
    public function handle(): void
    {
        $domains = Domain::query()->where('status', '!=', EnableStatus::Disabled)->get();

        foreach ($domains as $domain) {
            $this->checkOne($domain);
        }

        $this->info("已检测 {$domains->count()} 个域名。");
    }

    protected function checkOne(Domain $domain): void
    {
        [$healthy, $result] = $this->ping($domain->domain);

        $domain->update([
            'status' => $healthy ? EnableStatus::Enabled : EnableStatus::Abnormal,
            'last_check_time' => now(),
            'last_check_result' => $result,
        ]);
    }

    /**
     * @return array{0: bool, 1: string}
     */
    protected function ping(string $domain): array
    {
        try {
            $response = Http::timeout(5)->get("https://{$domain}/");

            return [$response->successful() || $response->redirect(), "HTTP {$response->status()}"];
        } catch (Throwable $e) {
            return [false, $e->getMessage()];
        }
    }
}
