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
                'content' => "🎉 Bem-vindo(a), {昵称}!\nSeu link de convite exclusivo já foi gerado. Convide amigos para se cadastrarem e fazerem o primeiro check-in, e você receberá sua comissão de indicação:\n{邀请链接}\n\n📅 Faça check-in todo dia para ganhar pontos, quanto mais dias seguidos, maior a recompensa\n🎁 Pontos atuais: {当前积分}\n\nToque em 【Check-in】abaixo e resgate seus primeiros pontos agora mesmo~",
            ],
            MessageTemplateType::Invite->value => [
                'title' => '邀请消息',
                'content' => "📢 Compartilhe seu link exclusivo e convide amigos para ganharem pontos juntos!\n\nSeu link de convite exclusivo:\n{邀请链接}\n\n💰 Recompensa de convite (creditada após o primeiro check-in do amigo): {邀请奖励值}\n👥 Convidados: {直接邀请人数} direto(s) / {间接邀请人数} indireto(s)\n🏆 {里程碑进度}\n📊 Posição no ranking deste mês: {本月排名}\n\nDica: depois que seu amigo se cadastrar, lembre-o de fazer o check-in — só assim você recebe a comissão~",
            ],
            MessageTemplateType::Checkin->value => [
                'title' => '签到消息',
                'content' => "✅ Check-in realizado com sucesso!\nVocê ganhou {今日获得积分} pontos desta vez, pontos atuais: {当前积分}\n🔥 {连续签到天数} dia(s) seguidos de check-in, volte amanhã — quanto mais dias seguidos, maior a recompensa!\n\nAproveite e convide um amigo para fazer check-in junto: depois do check-in dele, você ainda ganha comissão de indicação:\n{邀请链接}",
            ],
            MessageTemplateType::Profile->value => [
                'title' => '我的消息',
                'content' => "👤 {昵称}\nNível: {身份等级}\nPontos atuais: {当前积分}\n🔥 Check-ins consecutivos: {连续签到天数} dia(s)\n👥 Convites: {直接邀请人数} direto(s) / {间接邀请人数} indireto(s)\n📅 Cadastrado em: {注册时间}\n\nSeu link de convite exclusivo:\n{邀请链接}\nJá fez o check-in de hoje? Continue fazendo check-in e convidando amigos para seus pontos e nível subirem rápido~",
            ],
            MessageTemplateType::Wakeup->value => [
                'title' => '唤醒消息',
                'content' => "{昵称}, há quanto tempo! Sentimos sua falta~\n\nSeus pontos continuam aí: {当前积分} pts, e seu recorde de {连续签到天数} dia(s) seguidos de check-in não pode ser desperdiçado!\nVolte e faça check-in para continuar ganhando pontos — eles podem ser trocados diretamente com o atendimento\n\n🎁 Aproveite e convide um amigo para jogar junto: após o check-in dele, você ganha comissão de indicação:\n{邀请链接}\n\nToque em 【Check-in】abaixo e resgate seus benefícios exclusivos agora~",
            ],
            MessageTemplateType::PointsExpiry->value => [
                'title' => '积分到期提醒',
                'content' => "⏰ {昵称}, você tem {到期积分数} pontos que vão expirar em {到期日期}!\n\nTotal de pontos atuais: {当前积分}\nNão deixe seus pontos conquistados com esforço expirarem à toa — fale com o atendimento agora para trocar, ou convide amigos para ganhar mais pontos:\n{邀请链接}\n\nPontos expirados não são reembolsados, use-os a tempo~",
            ],
            MessageTemplateType::MonthlyLeaderboard->value => [
                'title' => '月度排行榜',
                'content' => "🏆 O ranking de convites deste mês saiu!\n\n{本月邀请排行榜}\n\nParabéns a quem entrou no ranking! Se ainda não entrou, não desanime — a contagem do novo mês já começou. Convide amigos para se cadastrarem e fazerem check-in, e você também pode chegar ao topo:\n{邀请链接}\n\nPontos podem ser trocados com o atendimento — quanto mais você convida, mais você ganha!",
            ],
        ];
    }
}
