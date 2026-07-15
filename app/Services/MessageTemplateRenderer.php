<?php

namespace App\Services;

use App\Enums\MessageTemplateType;
use App\Models\LeaderboardSnapshot;
use App\Models\MessageTemplate;
use App\Models\PointsLedger;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * 消息模板变量渲染，对应05文档"消息模板与变量规范"。
 *
 * 通用变量在这里统一计算；专属变量优先取调用方通过 $extra 传入的值（调用方往往已经
 * 算过一遍，比如签到服务已经知道"今日获得积分"），$extra 未提供时按能力所及自行兜底计算，
 * 实在无法计算的（如到期积分数/到期日期/本月排行榜文案，这些属于04文档定时任务的产物）
 * 缺省显示"-"，不抛错，对应05文档"渲染规则"。
 */
class MessageTemplateRenderer
{
    /** @var int[] 邀请里程碑阈值，见00/02文档 */
    protected const MILESTONES = [5, 20, 100];

    public function __construct(
        private readonly PointsConfigRepository $config,
        private readonly InviteLinkService $inviteLinkService,
    ) {}

    /**
     * @param  array<string, string>  $extra  key 为不带花括号的变量名，如 ['到期积分数' => '30']
     * @return array{text: string, image_url: ?string}
     */
    public function render(MessageTemplateType $type, User $user, array $extra = []): array
    {
        $template = MessageTemplate::query()->where('type', $type)->first();

        if ($template === null) {
            return ['text' => "(modelo de mensagem \"{$type->value}\" ainda não configurado)", 'image_url' => null];
        }

        $variables = array_merge($this->commonVariables($user), $this->specificVariables($type, $user), $extra);

        $text = strtr($template->content, $this->wrapKeys($variables));

        return ['text' => $text, 'image_url' => $this->resolveImageUrl($template->image_url)];
    }

    /**
     * FileUpload存的是public磁盘上的相对路径（如message-templates/xxx.png），
     * 不是完整URL；Telegram的sendPhoto需要能直接抓取的绝对URL，这里转换一下。
     */
    protected function resolveImageUrl(?string $path): ?string
    {
        if (blank($path)) {
            return null;
        }

        return Storage::disk('public')->url($path);
    }

    /**
     * @return array<string, string>
     */
    protected function commonVariables(User $user): array
    {
        $inviteLink = $this->inviteLinkService->getOrCreate($user);

        $directCount = User::query()->where('invited_by_l1', $user->id)->count();
        $indirectCount = User::query()->where('invited_by_l2', $user->id)->count()
            + User::query()->where('invited_by_l3', $user->id)->count();

        $todayPoints = (int) PointsLedger::query()
            ->where('user_id', $user->id)
            ->where('amount', '>', 0)
            ->whereDate('created_at', now()->toDateString())
            ->sum('amount');

        return [
            '昵称' => $user->nickname,
            '用户ID' => (string) $user->id,
            '当前积分' => (string) $user->points_balance,
            '邀请链接' => $this->inviteLinkService->buildUrl($inviteLink),
            '直接邀请人数' => (string) $directCount,
            '间接邀请人数' => (string) $indirectCount,
            '连续签到天数' => (string) $user->checkin_streak,
            '注册时间' => $user->register_time->format('Y-m-d H:i'),
            '今日获得积分' => (string) $todayPoints,
        ];
    }

    /**
     * @return array<string, string>
     */
    protected function specificVariables(MessageTemplateType $type, User $user): array
    {
        return match ($type) {
            MessageTemplateType::Invite => [
                '邀请奖励值' => $this->inviteRewardText(),
                '里程碑进度' => $this->milestoneProgressText($user),
                '本月排名' => $this->currentMonthRankText($user),
            ],
            MessageTemplateType::Profile => [
                '身份等级' => $user->identity_level->label(),
            ],
            MessageTemplateType::PointsExpiry => [
                '到期积分数' => '-',
                '到期日期' => '-',
            ],
            MessageTemplateType::MonthlyLeaderboard => [
                '本月邀请排行榜' => $this->latestLeaderboardText(),
            ],
            default => [],
        };
    }

    protected function inviteRewardText(): string
    {
        $l1 = $this->config->getInt('invite_l1_points', 10);
        $l2 = $this->config->getInt('invite_l2_points', 3);
        $l3 = $this->config->getInt('invite_l3_points', 1);

        return "Nível 1: {$l1} pts / Nível 2: {$l2} pts / Nível 3: {$l3} pts";
    }

    protected function milestoneProgressText(User $user): string
    {
        $directCount = User::query()->where('invited_by_l1', $user->id)->count();

        foreach (self::MILESTONES as $milestone) {
            if ($directCount < $milestone) {
                return 'Faltam '.($milestone - $directCount)." convite(s) para a próxima meta ({$milestone} pessoas)";
            }
        }

        return 'Todas as metas alcançadas';
    }

    /**
     * 对应04文档"6. 月度邀请排行榜结算"落地后的leaderboard_snapshot，取最近一次结算周期
     * 渲染成榜单文案，用于04文档结算后创建的"月度排行榜"群发任务。
     */
    protected function latestLeaderboardText(): string
    {
        $latestPeriod = LeaderboardSnapshot::query()->max('period');

        if ($latestPeriod === null) {
            return 'Ainda não há dados de ranking';
        }

        $rows = LeaderboardSnapshot::query()
            ->where('period', $latestPeriod)
            ->with('user:id,nickname')
            ->orderBy('rank')
            ->get();

        return $rows->map(fn (LeaderboardSnapshot $row) => "#{$row->rank} {$row->user->nickname} - {$row->invite_count_this_period} convite(s)"
        )->implode("\n");
    }

    protected function currentMonthRankText(User $user): string
    {
        $start = now()->startOfMonth();
        $end = now()->endOfMonth();

        $counts = DB::table('users')
            ->select('invited_by_l1', DB::raw('count(*) as cnt'))
            ->whereNotNull('invited_by_l1')
            ->whereBetween('register_time', [$start, $end])
            ->groupBy('invited_by_l1')
            ->orderByDesc('cnt')
            ->get();

        $myCount = (int) ($counts->firstWhere('invited_by_l1', $user->id)->cnt ?? 0);

        if ($myCount === 0) {
            return 'Ainda fora do ranking';
        }

        $rank = $counts->filter(fn ($row) => $row->cnt > $myCount)->count() + 1;

        return "#{$rank} ({$myCount} convite(s) novo(s) este mês)";
    }

    /**
     * @param  array<string, string>  $variables
     * @return array<string, string>
     */
    protected function wrapKeys(array $variables): array
    {
        $wrapped = [];

        foreach ($variables as $key => $value) {
            $wrapped['{'.$key.'}'] = $value;
        }

        return $wrapped;
    }
}
