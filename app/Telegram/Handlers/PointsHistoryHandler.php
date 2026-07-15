<?php

namespace App\Telegram\Handlers;

use App\Models\PointsLedger;
use App\Models\User;
use App\Telegram\Support\MainMenu;
use SergiX44\Nutgram\Nutgram;

/**
 * 对应02文档"我的"一节"查看积分明细"按钮。
 *
 * TODO(需确认): 文档只说"跳转展示该用户points_ledger分页列表"，没有给出每页条数或
 * 具体分页交互方式；这里先展示最近10条，不做翻页，后续如果需要完整分页可以在这基础上
 * 加"下一页"回调按钮。
 */
class PointsHistoryHandler
{
    protected const PAGE_SIZE = 10;

    public function __invoke(Nutgram $bot): void
    {
        $bot->answerCallbackQuery();

        /** @var User $user */
        $user = $bot->get('member');

        $entries = PointsLedger::query()
            ->where('user_id', $user->id)
            ->orderByDesc('created_at')
            ->limit(self::PAGE_SIZE)
            ->get();

        if ($entries->isEmpty()) {
            $bot->sendMessage('Nenhum histórico de pontos ainda', reply_markup: MainMenu::keyboard());

            return;
        }

        $lines = $entries->map(function (PointsLedger $entry) {
            $sign = $entry->amount > 0 ? '+' : '';

            return "{$entry->created_at->format('m-d H:i')} {$entry->change_type->label()} {$sign}{$entry->amount}";
        });

        $bot->sendMessage("Histórico de pontos recente:\n".$lines->implode("\n"), reply_markup: MainMenu::keyboard());
    }
}
