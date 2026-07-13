<?php

namespace App\Filament\Resources\PointsLedgers\Tables;

use App\Enums\PointsChangeType;
use App\Filament\Exports\PointsLedgerExporter;
use Filament\Actions\ExportAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TextInput;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class PointsLedgersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')->label('ID')->sortable(),
                TextColumn::make('user.nickname')->label('会员')->searchable(),
                TextColumn::make('change_type')->label('变动类型')->badge(),
                TextColumn::make('amount')->label('变动数量')->numeric()->sortable()
                    ->color(fn (int $state) => $state >= 0 ? 'success' : 'danger'),
                TextColumn::make('balance_after')->label('变动后余额')->numeric()->sortable(),
                TextColumn::make('relatedUser.nickname')->label('关联下级'),
                TextColumn::make('operator')->label('操作人'),
                TextColumn::make('created_at')->label('时间')->dateTime('Y-m-d H:i:s')->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('change_type')->label('变动类型')->options(PointsChangeType::class),
                Filter::make('user_id')
                    ->schema([
                        TextInput::make('user_id')->label('会员用户ID')->numeric(),
                    ])
                    ->query(fn (Builder $query, array $data) => $query->when(
                        $data['user_id'] ?? null,
                        fn (Builder $q, $id) => $q->where('user_id', $id)
                    )),
                Filter::make('created_at')
                    ->schema([
                        DatePicker::make('from')->label('时间从'),
                        DatePicker::make('until')->label('时间到'),
                    ])
                    ->query(fn (Builder $query, array $data) => $query
                        ->when($data['from'] ?? null, fn (Builder $q, $date) => $q->whereDate('created_at', '>=', $date))
                        ->when($data['until'] ?? null, fn (Builder $q, $date) => $q->whereDate('created_at', '<=', $date))),
            ])
            ->headerActions([
                ExportAction::make()->exporter(PointsLedgerExporter::class)->label('导出CSV'),
            ]);
    }
}
