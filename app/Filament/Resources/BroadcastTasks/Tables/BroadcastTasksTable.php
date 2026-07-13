<?php

namespace App\Filament\Resources\BroadcastTasks\Tables;

use App\Enums\BroadcastStatus;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class BroadcastTasksTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('template.title')->label('模板'),
                TextColumn::make('scheduled_time')->label('定时发送时间')->dateTime()->sortable(),
                TextColumn::make('status')->label('状态')->badge(),
                TextColumn::make('total_target_count')->label('目标人数')->numeric(),
                TextColumn::make('sent_count')->label('已发送')->numeric(),
                TextColumn::make('click_count')->label('点击数')->numeric(),
                TextColumn::make('created_by')->label('创建人'),
            ])
            ->defaultSort('scheduled_time', 'desc')
            ->filters([
                SelectFilter::make('status')->label('状态')->options(BroadcastStatus::class),
            ])
            ->recordActions([
                EditAction::make(),
            ]);
    }
}
