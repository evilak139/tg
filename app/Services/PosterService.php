<?php

namespace App\Services;

use App\Enums\MessageTemplateType;
use App\Models\MessageTemplate;
use App\Models\User;
use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\PngWriter;
use Intervention\Image\Drivers\Gd\Driver as GdDriver;
use Intervention\Image\ImageManager;
use Throwable;

/**
 * 邀请海报生成，对应00文档"海报生成"选型（Intervention Image + endroid/qr-code）与
 * 02文档"一张带二维码的邀请海报图片（图片模板后台可配置，二维码内容为上述短链）"。
 *
 * TODO(需确认): 00文档提到海报可合成"头像/积分/邀请人数"文字，但03/05文档均未给出具体
 * 排版坐标、字号或字体规范，当前环境也未内置中文字体文件，这部分文字叠加暂缓实现，
 * 先保证"二维码指向邀请短链、可叠加后台配置的背景图"这个明确要求，后续补充设计稿后再加文字层。
 */
class PosterService
{
    public function __construct(private readonly InviteLinkService $inviteLinkService) {}

    /**
     * @return string 海报（或退化为纯二维码）的PNG二进制内容
     */
    public function generate(User $user): string
    {
        $link = $this->inviteLinkService->getOrCreate($user);
        $url = $this->inviteLinkService->buildUrl($link);

        $qrPng = (new PngWriter)->write(
            new QrCode(
                data: $url,
                errorCorrectionLevel: ErrorCorrectionLevel::Medium,
                size: 400,
                margin: 16,
            )
        )->getString();

        $background = MessageTemplate::query()
            ->where('type', MessageTemplateType::Invite)
            ->value('image_url');

        if (empty($background)) {
            return $qrPng;
        }

        try {
            $manager = new ImageManager(GdDriver::class);
            $poster = $manager->decode($background);
            $qrImage = $manager->decode($qrPng);

            $poster->insert($qrImage, x: 24, y: 24, alignment: 'bottom-right');

            return $poster->encode()->toString();
        } catch (Throwable) {
            // 背景图缺失/损坏时不影响核心功能，退化为纯二维码
            return $qrPng;
        }
    }
}
