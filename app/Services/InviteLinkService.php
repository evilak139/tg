<?php

namespace App\Services;

use App\Enums\EnableStatus;
use App\Models\Bot;
use App\Models\Domain;
use App\Models\InviteLink;
use App\Models\User;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * 邀请链接生成/复用，对应01文档 invite_links 表与02文档"邀请"一节。
 *
 * TODO(需确认)：产品要求暂时用Telegram原始深链（https://t.me/{bot}?start={user_id}）
 * 顶替自建短链域名跳转，不再要求先配置启用域名。短链表/short_code/domain_id依旧照常
 * 尝试生成（domain_id现在允许为空），/r/{短码}跳转路由也还在，之后想切回自建短链系统
 * 只需要改buildUrl()这一处即可，不需要重新生成历史数据。
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
            'domain_id' => $this->pickDomain()?->id,
            'short_code' => $this->generateUniqueShortCode(),
        ]);
    }

    /**
     * 暂时直接用Telegram原始深链，不走自建短链域名跳转，见上面TODO。
     */
    public function buildUrl(InviteLink $link): string
    {
        $bot = Bot::query()->where('is_active', true)->where('status', EnableStatus::Enabled)->first();

        if ($bot === null) {
            throw new RuntimeException('没有生效中的机器人，无法生成邀请链接，请先在后台"机器人配置"中启用并激活一个机器人');
        }

        return "https://t.me/{$bot->bot_username}?start={$link->user_id}";
    }

    /**
     * 轮询策略：优先分配给已分配短链数最少的启用域名，实现负载均衡意义上的"轮询"。
     * 没有可用域名时返回null（短链暂不强制要求配置域名，见上面TODO）。
     */
    protected function pickDomain(): ?Domain
    {
        return Domain::query()
            ->where('status', EnableStatus::Enabled)
            ->withCount('inviteLinks')
            ->orderBy('invite_links_count')
            ->orderBy('id')
            ->first();
    }

    protected function generateUniqueShortCode(): string
    {
        do {
            $code = Str::random(8);
        } while (InviteLink::query()->where('short_code', $code)->exists());

        return $code;
    }
}
