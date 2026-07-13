<?php

namespace App\Filament\Resources\BroadcastTasks\Tables;

use App\Enums\BroadcastStatus;
use App\Models\BroadcastTask;
use Filament\Actions\DeleteAction;
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
                TextColumn::make('scheduled_time')
                    ->label('发送方式 / 时间')
                    ->getStateUsing(fn (BroadcastTask $record) => self::describeSchedule($record))
                    ->sortable(),
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
                EditAction::make()
                    ->visible(fn (BroadcastTask $record) => $record->status === BroadcastStatus::Pending),
                DeleteAction::make(),
            ]);
    }

    /**
     * 创建时"立即发送"和"定时发送"最终都只是落库一个scheduled_time，没有单独存
     * 发送方式（提交时用完就丢了），这里用"计划时间是否非常接近创建时间"反推展示，
     * 避免列表里两种任务长得一模一样、看不出区别。
     */
    public static function describeSchedule(BroadcastTask $record): string
    {
        $formatted = $record->scheduled_time->format('Y-m-d H:i');

        if ($record->created_at !== null && abs($record->scheduled_time->diffInSeconds($record->created_at)) < 60) {
            return "立即发送（{$formatted}）";
        }

        return "定时：{$formatted}";
    }
}
