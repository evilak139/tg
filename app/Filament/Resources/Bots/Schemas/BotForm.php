<?php

namespace App\Filament\Resources\Bots\Schemas;

use App\Enums\EnableStatus;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

/**
 * 对应03.8文档"机器人配置"：新增时输入Token，保存前调用getMe校验（见Pages/CreateBot、
 * EditBot），成功后自动回填bot_username，因此这里bot_username设为禁用不可手填。
 * is_active（当前生效）不在表单里，通过列表的"设为当前生效"操作单独处理。
 */
class BotForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('token')
                    ->label('Bot Token')
                    ->password()
                    ->revealable()
                    ->required(fn (string $operation) => $operation === 'create')
                    ->dehydrated(fn (?string $state) => filled($state))
                    ->helperText('编辑时留空表示不修改Token')
                    ->columnSpanFull(),
                TextInput::make('bot_username')
                    ->label('Bot用户名（自动获取）')
                    ->disabled()
                    ->dehydrated(false),
                Select::make('status')
                    ->label('状态')
                    ->options(EnableStatus::class)
                    ->default(EnableStatus::Enabled)
                    ->required(),
            ]);
    }
}
