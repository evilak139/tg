<?php

namespace App\Enums;

enum MessageTemplateType: string
{
    case Welcome = '欢迎';
    case Invite = '邀请';
    case Checkin = '签到';
    case Profile = '我的';
    case Wakeup = '唤醒';
    case PointsExpiry = '积分到期提醒';
    case MonthlyLeaderboard = '月度排行榜';
    case Custom = '自定义';

    /** @return string[] 通用变量，见05文档"通用变量" */
    public static function commonVariables(): array
    {
        return [
            '昵称', '用户ID', '当前积分', '邀请链接', '直接邀请人数',
            '间接邀请人数', '连续签到天数', '注册时间', '今日获得积分',
        ];
    }

    /** @return string[] 专属变量，见05文档"专属变量" */
    public function specificVariables(): array
    {
        return match ($this) {
            self::Invite => ['邀请奖励值', '里程碑进度', '本月排名'],
            self::Profile => ['身份等级'],
            self::PointsExpiry => ['到期积分数', '到期日期'],
            self::MonthlyLeaderboard => ['本月邀请排行榜'],
            self::Welcome, self::Checkin, self::Wakeup, self::Custom => [],
        };
    }

    /** @return string[] 该模板类型允许使用的全部变量（通用+专属） */
    public function allowedVariables(): array
    {
        return [...self::commonVariables(), ...$this->specificVariables()];
    }
}
