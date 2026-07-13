<?php

namespace App\Filament\Resources\MessageTemplates;

use App\Enums\MessageTemplateType;
use App\Filament\Resources\MessageTemplates\Pages\CreateMessageTemplate;
use App\Filament\Resources\MessageTemplates\Pages\EditMessageTemplate;
use App\Filament\Resources\MessageTemplates\Pages\ListMessageTemplates;
use App\Filament\Resources\MessageTemplates\Schemas\MessageTemplateForm;
use App\Filament\Resources\MessageTemplates\Tables\MessageTemplatesTable;
use App\Models\MessageTemplate;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

/**
 * 对应03.3文档"消息模板管理"。05文档里的7种触发型模板由安装向导/Seeder预先建好，
 * 每种type只能有一条记录（各触发点按type查`->first()`取模板，多条会导致取不确定）,
 * 所以这7种依然只能编辑、不能新建/删除。用户希望能新增模板（主要是给"群发消息"功能
 * 用的自定义文案），因此加了一个不挂任何自动触发点的type=自定义（Custom），
 * 新建只允许这个type，删除也只对这个type开放。
 */
class MessageTemplateResource extends Resource
{
    protected static ?string $model = MessageTemplate::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChatBubbleLeftRight;

    protected static ?string $navigationLabel = '消息模板';

    protected static ?string $modelLabel = '消息模板';

    public static function form(Schema $schema): Schema
    {
        return MessageTemplateForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return MessageTemplatesTable::configure($table);
    }

    public static function canDelete($record): bool
    {
        return $record->type === MessageTemplateType::Custom;
    }

    public static function getPages(): array
    {
        return [
            'index' => ListMessageTemplates::route('/'),
            'create' => CreateMessageTemplate::route('/create'),
            'edit' => EditMessageTemplate::route('/{record}/edit'),
        ];
    }
}
