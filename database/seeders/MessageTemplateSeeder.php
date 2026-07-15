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
                'content' => "🎉 Bem-vindo(a), {nome}!\nSeu link de convite exclusivo já foi gerado. Convide amigos para se cadastrarem e fazerem o primeiro check-in, e você receberá sua comissão de indicação:\n{link_convite}\n\n📅 Faça check-in todo dia para ganhar pontos, quanto mais dias seguidos, maior a recompensa\n🎁 Pontos atuais: {pontos_atuais}\n\nToque em 【Check-in】abaixo e resgate seus primeiros pontos agora mesmo~",
            ],
            MessageTemplateType::Invite->value => [
                'title' => '邀请消息',
                'content' => "📢 Compartilhe seu link exclusivo e convide amigos para ganharem pontos juntos!\n\nSeu link de convite exclusivo:\n{link_convite}\n\n💰 Recompensa de convite (creditada após o primeiro check-in do amigo): {valor_recompensa_convite}\n👥 Convidados: {convidados_diretos} direto(s) / {convidados_indiretos} indireto(s)\n🏆 {progresso_meta}\n📊 Posição no ranking deste mês: {posicao_mes}\n\nDica: depois que seu amigo se cadastrar, lembre-o de fazer o check-in — só assim você recebe a comissão~",
            ],
            MessageTemplateType::Checkin->value => [
                'title' => '签到消息',
                'content' => "✅ Check-in realizado com sucesso!\nVocê ganhou {pontos_hoje} pontos desta vez, pontos atuais: {pontos_atuais}\n🔥 {dias_checkin_consecutivos} dia(s) seguidos de check-in, volte amanhã — quanto mais dias seguidos, maior a recompensa!\n\nAproveite e convide um amigo para fazer check-in junto: depois do check-in dele, você ainda ganha comissão de indicação:\n{link_convite}",
            ],
            MessageTemplateType::Profile->value => [
                'title' => '我的消息',
                'content' => "👤 {nome}\nNível: {nivel_identidade}\nPontos atuais: {pontos_atuais}\n🔥 Check-ins consecutivos: {dias_checkin_consecutivos} dia(s)\n👥 Convites: {convidados_diretos} direto(s) / {convidados_indiretos} indireto(s)\n📅 Cadastrado em: {data_cadastro}\n\nSeu link de convite exclusivo:\n{link_convite}\nJá fez o check-in de hoje? Continue fazendo check-in e convidando amigos para seus pontos e nível subirem rápido~",
            ],
            MessageTemplateType::Wakeup->value => [
                'title' => '唤醒消息',
                'content' => "{nome}, há quanto tempo! Sentimos sua falta~\n\nSeus pontos continuam aí: {pontos_atuais} pts, e seu recorde de {dias_checkin_consecutivos} dia(s) seguidos de check-in não pode ser desperdiçado!\nVolte e faça check-in para continuar ganhando pontos — eles podem ser trocados diretamente com o atendimento\n\n🎁 Aproveite e convide um amigo para jogar junto: após o check-in dele, você ganha comissão de indicação:\n{link_convite}\n\nToque em 【Check-in】abaixo e resgate seus benefícios exclusivos agora~",
            ],
            MessageTemplateType::PointsExpiry->value => [
                'title' => '积分到期提醒',
                'content' => "⏰ {nome}, você tem {pontos_a_expirar} pontos que vão expirar em {data_expiracao}!\n\nTotal de pontos atuais: {pontos_atuais}\nNão deixe seus pontos conquistados com esforço expirarem à toa — fale com o atendimento agora para trocar, ou convide amigos para ganhar mais pontos:\n{link_convite}\n\nPontos expirados não são reembolsados, use-os a tempo~",
            ],
            MessageTemplateType::MonthlyLeaderboard->value => [
                'title' => '月度排行榜',
                'content' => "🏆 O ranking de convites deste mês saiu!\n\n{ranking_convites_mes}\n\nParabéns a quem entrou no ranking! Se ainda não entrou, não desanime — a contagem do novo mês já começou. Convide amigos para se cadastrarem e fazerem check-in, e você também pode chegar ao topo:\n{link_convite}\n\nPontos podem ser trocados com o atendimento — quanto mais você convida, mais você ganha!",
            ],
        ];
    }
}
