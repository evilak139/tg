<?php

namespace App\Services;

use App\Models\Bot;
use RuntimeException;
use SergiX44\Nutgram\Nutgram;
use SergiX44\Nutgram\Telegram\Types\Command\BotCommand;
use Throwable;

/**
 * "一键部署"：把后台配置好的命令菜单说明推送到Telegram，让这个token对应的机器人
 * 不用手动跑 @BotFather 就能直接使用。命令列表目前只有/start一条（其余邀请/签到/
 * 提现/我的走的是内联键盘按钮，不是slash command，见02文档"主菜单"）。
 */
class BotDeploymentService
{
    public function __construct(private readonly PointsConfigRepository $config) {}

    /**
     * @return string 部署后确认到的bot_username
     */
    public function deploy(Bot $bot): string
    {
        $client = new Nutgram($bot->token);

        try {
            $me = $client->getMe();

            if ($me === null || blank($me->username)) {
                throw new RuntimeException('无法通过Telegram getMe接口确认这个Token有效');
            }

            $description = $this->config->get('start_command_description', 'Começar a usar');

            $client->setMyCommands([
                BotCommand::make('start', $description),
            ]);
        } catch (Throwable $e) {
            throw new RuntimeException('部署失败：'.$e->getMessage(), previous: $e);
        }

        return $me->username;
    }
}
