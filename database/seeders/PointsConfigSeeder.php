<?php

namespace Database\Seeders;

use App\Models\PointsConfig;
use Illuminate\Database\Seeder;

/**
 * 对应07文档安装向导第3步"初始化数据库"要写入的 points_config 默认值。
 * 本地开发也由 DatabaseSeeder 调用，保证不跑安装向导也能测试业务逻辑。
 *
 * TODO(需确认): 00/01文档只给出了邀请1/2/3级积分(10/3/1)、积分有效期(12个月)、
 * 活跃度层级阈值(7/30/90天)这几个具体数值，其余数值文档未给出，以下为暂定值，
 * 后续可在03文档"积分配置"模块的后台界面里调整。
 */
class PointsConfigSeeder extends Seeder
{
    public function run(): void
    {
        foreach ($this->defaults() as $key => $value) {
            PointsConfig::query()->updateOrCreate(['key' => $key], ['value' => $value]);
        }
    }

    /**
     * @return array<string, string>
     */
    public function defaults(): array
    {
        return [
            // TODO(需确认): 签到基础积分文档未给出具体数值，暂定2分/天
            'checkin_base_points' => '2',
            // TODO(需确认): 连续签到递增规则文档未给出具体形状，暂定连续7/15/30天当日额外加5/10/20分
            'checkin_streak_bonus_rule' => json_encode([
                ['streak' => 7, 'bonus' => 5],
                ['streak' => 15, 'bonus' => 10],
                ['streak' => 30, 'bonus' => 20],
            ]),
            'invite_l1_points' => '10',
            'invite_l2_points' => '3',
            'invite_l3_points' => '1',
            // TODO(需确认): 01文档 points_config 表未列出这个key，但02文档"/start处理逻辑"
            // 明确要求新用户注册即发新人礼包，02/05文档都未给具体分值，暂定10分
            'new_account_gift_points' => '10',
            // TODO(需确认): 里程碑奖励金额文档未给出，暂定50/200/1000
            'milestone_5_bonus' => '50',
            'milestone_20_bonus' => '200',
            'milestone_100_bonus' => '1000',
            'points_expire_months' => '12',
            // TODO(需确认): 兑换比例文档未给出，暂定100积分=1元（此处存"多少积分兑1元"）
            'exchange_rate' => '100',
            // TODO(需确认): 最低提现门槛文档未给出，暂定100积分
            'withdraw_min_threshold' => '100',
            // TODO(需确认): 新账号提现限制天数文档未给出，暂定7天
            'new_account_withdraw_limit_days' => '7',
            'activity_tag_thresholds' => json_encode([
                'active' => 7,
                'dormant' => 30,
                'churned' => 90,
            ]),
            // TODO(需确认): 排行榜奖励覆盖前N名文档未给出，暂定前10名
            'leaderboard_top_n' => '10',
            // TODO(需确认): 01文档points_config表未列出这个key，02文档"提现/兑换"要求展示
            // 客服联系方式但未给出具体值/存储位置，这里作为可配置项处理，暂定占位文案
            'customer_service_contact' => '@your_customer_service',
        ];
    }
}
