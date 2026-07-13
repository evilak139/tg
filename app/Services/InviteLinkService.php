<?php

namespace App\Services;

use App\Enums\EnableStatus;
use App\Models\Domain;
use App\Models\InviteLink;
use App\Models\User;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * 邀请短链生成/复用，对应01文档 invite_links 表与02文档"邀请"一节：
 * 每用户一条短链，长期复用；生成时从启用域名池随机/轮询分配。
 */
class InviteLinkService
{
    public function getOrCreate(User $user): InviteLink
    {
        $existing = InviteLink::query()->where('user_id', $user->id)->first();

        if ($existing !== null) {
            return $existing;
        }

        return InviteLink::create([
            'user_id' => $user->id,
            'domain_id' => $this->pickDomain()->id,
            'short_code' => $this->generateUniqueShortCode(),
        ]);
    }

    /**
     * 对应01文档：短链格式为 {域名}/r/{短码}，302跳转到当前生效机器人（跳转路由本身
     * 属于Web层，见后续短链跳转服务的实现）。
     */
    public function buildUrl(InviteLink $link): string
    {
        $link->loadMissing('domain');

        return "https://{$link->domain->domain}/r/{$link->short_code}";
    }

    /**
     * 轮询策略：优先分配给已分配短链数最少的启用域名，实现负载均衡意义上的"轮询"。
     */
    protected function pickDomain(): Domain
    {
        $domain = Domain::query()
            ->where('status', EnableStatus::Enabled)
            ->withCount('inviteLinks')
            ->orderBy('invite_links_count')
            ->orderBy('id')
            ->first();

        if ($domain === null) {
            throw new RuntimeException('没有可用的启用域名，无法生成邀请短链，请先在后台"域名配置"中添加并启用至少一个域名');
        }

        return $domain;
    }

    protected function generateUniqueShortCode(): string
    {
        do {
            $code = Str::random(8);
        } while (InviteLink::query()->where('short_code', $code)->exists());

        return $code;
    }
}
