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

    /** @return string[] 通用变量，见05文档"通用变量"（变量名为葡语(巴西)，值仍是发给用户的正文一部分） */
    public static function commonVariables(): array
    {
        return [
            'nome', 'ID_usuario', 'pontos_atuais', 'link_convite', 'convidados_diretos',
            'convidados_indiretos', 'dias_checkin_consecutivos', 'data_cadastro', 'pontos_hoje',
        ];
    }

    /** @return string[] 专属变量，见05文档"专属变量" */
    public function specificVariables(): array
    {
        return match ($this) {
            self::Invite => ['valor_recompensa_convite', 'progresso_meta', 'posicao_mes'],
            self::Profile => ['nivel_identidade'],
            self::PointsExpiry => ['pontos_a_expirar', 'data_expiracao'],
            self::MonthlyLeaderboard => ['ranking_convites_mes'],
            self::Welcome, self::Checkin, self::Wakeup, self::Custom => [],
        };
    }

    /** @return string[] 该模板类型允许使用的全部变量（通用+专属） */
    public function allowedVariables(): array
    {
        return [...self::commonVariables(), ...$this->specificVariables()];
    }
}
