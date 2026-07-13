<?php

namespace App\Http\Controllers;

use App\Enums\EnableStatus;
use App\Models\Bot;
use App\Models\InviteLink;
use Illuminate\Http\RedirectResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\ServiceUnavailableHttpException;

/**
 * 对应00文档"邀请链接形式：后台自建短链...格式为{域名}/r/{短码}，302跳转到当前生效机器人"
 * 和"机器人容灾：切换当前生效机器人后，历史短链自动生效，无需重新生成"。跳转目标每次
 * 请求时实时查is_active的机器人，不把机器人信息写死进短链记录，这样切换生效机器人时
 * 所有历史短链自动跟着切换，不需要批量改数据。
 */
class ShortLinkController extends Controller
{
    public function redirect(string $shortCode): RedirectResponse
    {
        $link = InviteLink::query()->where('short_code', $shortCode)->first();

        if ($link === null) {
            throw new NotFoundHttpException;
        }

        $bot = Bot::query()->where('is_active', true)->where('status', EnableStatus::Enabled)->first();

        if ($bot === null) {
            throw new ServiceUnavailableHttpException(message: '机器人暂时不可用，请稍后再试');
        }

        InviteLink::where('id', $link->id)->increment('click_count');

        return redirect()->away("https://t.me/{$bot->bot_username}?start={$link->user_id}");
    }
}
