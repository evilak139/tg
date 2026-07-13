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
                'content' => "欢迎 {昵称}！\n签到、邀请好友均可获得积分，积分可联系客服兑换。",
            ],
            MessageTemplateType::Invite->value => [
                'title' => '邀请消息',
                'content' => "你的专属邀请链接：\n{邀请链接}\n\n邀请奖励：{邀请奖励值}\n当前已邀请 {直接邀请人数} 人，{里程碑进度}\n本月排名：{本月排名}",
            ],
            MessageTemplateType::Checkin->value => [
                'title' => '签到消息',
                'content' => "签到成功！本次获得 {今日获得积分} 积分\n连续签到 {连续签到天数} 天\n当前积分：{当前积分}",
            ],
            MessageTemplateType::Profile->value => [
                'title' => '我的消息',
                'content' => "{昵称}\n身份等级：{身份等级}\n当前积分：{当前积分}\n直接邀请：{直接邀请人数} 人\n间接邀请：{间接邀请人数} 人\n注册时间：{注册时间}",
            ],
            MessageTemplateType::Wakeup->value => [
                'title' => '唤醒消息',
                'content' => '{昵称}，好久不见！回来签到领积分吧，当前积分：{当前积分}',
            ],
            MessageTemplateType::PointsExpiry->value => [
                'title' => '积分到期提醒',
                'content' => '{昵称}，你有 {到期积分数} 积分将于 {到期日期} 过期，记得及时使用～',
            ],
            MessageTemplateType::MonthlyLeaderboard->value => [
                'title' => '月度排行榜',
                'content' => "本月邀请排行榜出炉啦！\n{本月邀请排行榜}",
            ],
        ];
    }
}
