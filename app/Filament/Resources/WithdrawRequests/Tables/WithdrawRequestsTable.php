<?php

namespace App\Filament\Resources\WithdrawRequests\Tables;

use App\Enums\WithdrawStatus;
use App\Models\WithdrawRequest;
use App\Services\WithdrawService;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use RuntimeException;

class WithdrawRequestsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')->label('ID')->sortable(),
                TextColumn::make('user.nickname')->label('会员')->searchable(),
                TextColumn::make('points_amount')->label('申请积分数')->numeric()->sortable(),
                TextColumn::make('exchange_amount')->label('折算金额')->money('CNY')->sortable(),
                TextColumn::make('status')->label('状态')->badge(),
                IconColumn::make('risk_flag')->label('风控标记')->boolean()
                    ->trueColor('danger')->falseColor('gray'),
                TextColumn::make('applied_at')->label('申请时间')->dateTime('Y-m-d H:i:s')->sortable(),
                TextColumn::make('completed_at')->label('完成时间')->dateTime('Y-m-d H:i:s')->sortable(),
                TextColumn::make('operator')->label('操作人'),
            ])
            ->defaultSort('applied_at', 'desc')
            ->filters([
                SelectFilter::make('status')->label('状态')->options(WithdrawStatus::class),
                TernaryFilter::make('risk_flag')->label('风控标记'),
            ])
            ->recordActions([
                self::completeAction(),
            ]);
    }

    /**
     * 对应03.6/03.7文档："标记提现完成"客服和超级管理员可操作。
     */
    protected static function completeAction(): Action
    {
        return Action::make('complete')
            ->label('标记完成')
            ->icon('heroicon-o-check-circle')
            ->color('success')
            ->visible(fn (WithdrawRequest $record) => $record->status === WithdrawStatus::Pending
                && (auth()->user()?->canManageWithdrawals() ?? false))
            ->requiresConfirmation()
            ->modalDescription('确认已通过客服线下核实兑换完成？此操作会立即按FIFO扣减该会员的积分。')
            ->action(function (WithdrawRequest $record, WithdrawService $withdrawService) {
                try {
                    $withdrawService->complete($record, auth()->user()?->username ?? 'system');
                    Notification::make()->title('已标记完成')->success()->send();
                } catch (RuntimeException $e) {
                    Notification::make()->title($e->getMessage())->danger()->send();
                }
            });
    }
}
