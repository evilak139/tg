<?php

namespace Database\Seeders;

use App\Enums\MessageTemplateType;
use App\Models\MessageTemplate;
use Illuminate\Database\Seeder;

/**
 * 对应07文档安装向导第3步：写入7类消息模板的默认占位内容，管理员后续在
 * 03文档"消息模板管理"里自行编辑。变量清单见05文档。
 */
class MessageTemplateSeeder extends Seeder
{
    public function run(): void
    {
        foreach ($this->defaults() as $type => $data) {
            MessageTemplate::query()->updateOrCreate(
                ['type' => $type],
                [
                    'title' => $data['title'],
                    'content' => $data['content'],
                    'image_url' => null,
                    'updated_by' => 'system',
                ]
            );
        }
    }

    /**
     * @return array<string, array{title: string, content: string}>
     */
    public function defaults(): array
    {
        return [
            MessageTemplateType::Welcome->value => [
                'title' => '欢迎消息',
                'content' => "🎉 欢迎加入，{昵称}！\n你的专属邀请链接已生成，邀请好友注册并完成首次签到，你就能拿到邀请返佣：\n{邀请链接}\n\n📅 每日签到领积分，连续签到奖励更丰厚\n🎁 当前积分：{当前积分}\n\n点击下方【签到】，领取属于你的第一笔积分吧～",
            ],
            MessageTemplateType::Invite->value => [
                'title' => '邀请消息',
                'content' => "📢 分享专属链接，邀请好友一起赚积分！\n\n你的专属邀请链接：\n{邀请链接}\n\n💰 邀请奖励（好友完成首次签到后到账）：{邀请奖励值}\n👥 已邀请：直接 {直接邀请人数} 人 / 间接 {间接邀请人数} 人\n🏆 {里程碑进度}\n📊 本月邀请排名：{本月排名}\n\n小提示：好友注册后记得提醒TA签到，只有完成首次签到你才能拿到返佣哦～",
            ],
            MessageTemplateType::Checkin->value => [
                'title' => '签到消息',
                'content' => "✅ 签到成功！\n本次获得 {今日获得积分} 积分，当前积分：{当前积分}\n🔥 连续签到 {连续签到天数} 天，明天记得再来，连续天数越长奖励越丰厚！\n\n顺手邀请好友一起签到，好友签到后你还能额外拿邀请返佣：\n{邀请链接}",
            ],
            MessageTemplateType::Profile->value => [
                'title' => '我的消息',
                'content' => "👤 {昵称}\n身份等级：{身份等级}\n当前积分：{当前积分}\n🔥 连续签到：{连续签到天数} 天\n👥 邀请战绩：直接 {直接邀请人数} 人 / 间接 {间接邀请人数} 人\n📅 注册时间：{注册时间}\n\n你的专属邀请链接：\n{邀请链接}\n今天签到了吗？坚持签到 + 邀请好友，积分和等级蹭蹭涨～",
            ],
            MessageTemplateType::Wakeup->value => [
                'title' => '唤醒消息',
                'content' => "{昵称}，好久不见，我们很想你～\n\n你的积分还在：{当前积分} 分，连续签到 {连续签到天数} 天的纪录别浪费了！\n回来签到即可继续领积分，积分可直接联系客服兑换好礼\n\n🎁 顺手邀请好友一起玩，好友签到后你就能拿邀请返佣：\n{邀请链接}\n\n点击下方【签到】，马上找回你的专属福利～",
            ],
            MessageTemplateType::PointsExpiry->value => [
                'title' => '积分到期提醒',
                'content' => "⏰ {昵称}，你有 {到期积分数} 积分将于 {到期日期} 过期！\n\n当前总积分：{当前积分}\n别让辛苦攒的积分白白作废，现在就联系客服兑换，或邀请好友再攒新积分：\n{邀请链接}\n\n积分过期不补发，记得及时使用～",
            ],
            MessageTemplateType::MonthlyLeaderboard->value => [
                'title' => '月度排行榜',
                'content' => "🏆 本月邀请排行榜出炉啦！\n\n{本月邀请排行榜}\n\n恭喜上榜的小伙伴！还没上榜也别灰心，新一月排名已重新开始计算，邀请好友注册并签到，你也能登上榜首：\n{邀请链接}\n\n积分可联系客服兑换福利，邀请越多、拿的越多！",
            ],
        ];
    }
}
