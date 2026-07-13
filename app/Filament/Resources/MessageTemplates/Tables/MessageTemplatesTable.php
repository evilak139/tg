<?php

namespace App\Filament\Resources\MessageTemplates\Tables;

use App\Enums\MessageTemplateType;
use App\Models\MessageTemplate;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class MessageTemplatesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('type')->label('类型')->badge(),
                TextColumn::make('title')->label('标题')->searchable(),
                ImageColumn::make('image_url')->label('配图'),
                TextColumn::make('updated_at')->label('最后更新')->dateTime()->sortable(),
                TextColumn::make('updated_by')->label('修改人'),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make()
                    ->visible(fn (MessageTemplate $record) => $record->type === MessageTemplateType::Custom),
            ]);
    }
}
