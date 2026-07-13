<?php

namespace App\Filament\Resources\Domains\Schemas;

use App\Enums\EnableStatus;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

/**
 * 对应03.9文档"域名配置"。last_check_time/last_check_result由04文档"域名健康检测"
 * 定时任务写入，表单里只展示不可编辑。
 */
class DomainForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('domain')->label('域名')->required(),
                Select::make('status')
                    ->label('状态')
                    ->options(EnableStatus::class)
                    ->default(EnableStatus::Enabled)
                    ->required(),
                DateTimePicker::make('last_check_time')->label('最近检测时间')->disabled(),
                TextInput::make('last_check_result')->label('最近检测结果')->disabled(),
            ]);
    }
}
