<?php

namespace App\Filament\Resources\Domains\Tables;

use App\Enums\EnableStatus;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class DomainsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('domain')->label('域名')->searchable(),
                TextColumn::make('status')->label('状态')->badge(),
                TextColumn::make('invite_links_count')->label('已分配短链数')->counts('inviteLinks'),
                TextColumn::make('last_check_time')->label('最近检测时间')->dateTime('Y-m-d H:i:s')->sortable(),
                TextColumn::make('last_check_result')->label('最近检测结果'),
            ])
            ->filters([
                SelectFilter::make('status')->label('状态')->options(EnableStatus::class),
            ])
            ->recordActions([
                EditAction::make(),
            ]);
    }
}
