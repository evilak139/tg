<?php

namespace App\Filament\Resources\BroadcastTasks\Schemas;

use App\Enums\ActivityTag;
use App\Enums\IdentityLevel;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TagsInput;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;

/**
 * 对应03.6文档"群发消息系统"：选模板 + 筛选条件（活跃度层级/身份等级/自定义名单/全体）
 * + 定时发送时间。target_filter是一个JSON字段，这里用一个虚拟的target_scope单选
 * 加条件显示的字段来拼装，实际的JSON组装/拆解在Pages/CreateBroadcastTask、
 * EditBroadcastTask里做（target_scope等字段不是数据库列，不会被直接保存）。
 */
class BroadcastTaskForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('template_id')
                    ->label('消息模板')
                    ->relationship('template', 'title')
                    ->required(),
                Select::make('target_scope')
                    ->label('目标范围')
                    ->options([
                        'all' => '全体会员',
                        'activity_tag' => '按活跃度层级',
                        'identity_level' => '按身份等级',
                        'custom' => '自定义用户ID列表',
                    ])
                    ->default('all')
                    ->live()
                    ->required(),
                CheckboxList::make('target_activity_tags')
                    ->label('活跃度层级')
                    ->options(ActivityTag::class)
                    ->visible(fn (Get $get) => $get('target_scope') === 'activity_tag'),
                CheckboxList::make('target_identity_levels')
                    ->label('身份等级')
                    ->options(IdentityLevel::class)
                    ->visible(fn (Get $get) => $get('target_scope') === 'identity_level'),
                TagsInput::make('target_user_ids')
                    ->label('用户ID列表')
                    ->helperText('填写会员在系统里的内部ID（不是Telegram ID），回车分隔')
                    ->visible(fn (Get $get) => $get('target_scope') === 'custom'),
                DateTimePicker::make('scheduled_time')
                    ->label('定时发送时间')
                    ->required()
                    ->native(false),
            ]);
    }
}
