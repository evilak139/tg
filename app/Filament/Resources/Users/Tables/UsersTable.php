<?php

namespace App\Filament\Resources\Users\Tables;

use App\Enums\ActivityTag;
use App\Enums\IdentityLevel;
use App\Enums\PointsChangeType;
use App\Enums\UserStatus;
use App\Models\User;
use App\Services\PointsService;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class UsersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')->sortable(),
                TextColumn::make('tg_user_id')->label('Telegram ID')->searchable(),
                TextColumn::make('tg_username')->label('用户名')->searchable(),
                TextColumn::make('nickname')->label('昵称')->searchable(),
                TextColumn::make('points_balance')->label('当前积分')->numeric()->sortable(),
                TextColumn::make('identity_level')->label('身份等级')->badge(),
                TextColumn::make('activity_tag')->label('活跃度')->badge(),
                TextColumn::make('status')->label('状态')->badge()
                    ->color(fn (UserStatus $state) => $state === UserStatus::Blacklisted ? 'danger' : 'success'),
                TextColumn::make('checkin_streak')->label('连续签到')->numeric()->sortable(),
                TextColumn::make('register_time')->label('注册时间')->dateTime('Y-m-d H:i:s')->sortable(),
                TextColumn::make('last_active_time')->label('最近活跃')->dateTime('Y-m-d H:i:s')->sortable(),
            ])
            ->defaultSort('register_time', 'desc')
            ->filters([
                SelectFilter::make('identity_level')->label('身份等级')->options(IdentityLevel::class),
                SelectFilter::make('activity_tag')->label('活跃度层级')->options(ActivityTag::class),
                SelectFilter::make('status')->label('状态')->options(UserStatus::class),
                Filter::make('invited_by')
                    ->schema([
                        TextInput::make('inviter_id')->label('邀请人用户ID')->numeric(),
                    ])
                    ->query(fn (Builder $query, array $data) => $query->when(
                        $data['inviter_id'] ?? null,
                        fn (Builder $q, $id) => $q->where(function (Builder $q) use ($id) {
                            $q->where('invited_by_l1', $id)
                                ->orWhere('invited_by_l2', $id)
                                ->orWhere('invited_by_l3', $id);
                        })
                    )),
                Filter::make('register_time')
                    ->schema([
                        DatePicker::make('register_from')->label('注册时间从'),
                        DatePicker::make('register_until')->label('注册时间到'),
                    ])
                    ->query(fn (Builder $query, array $data) => $query
                        ->when(
                            $data['register_from'] ?? null,
                            fn (Builder $q, $date) => $q->whereDate('register_time', '>=', $date)
                        )
                        ->when(
                            $data['register_until'] ?? null,
                            fn (Builder $q, $date) => $q->whereDate('register_time', '<=', $date)
                        )),
            ])
            ->recordActions([
                self::adjustPointsAction(),
                self::toggleBlacklistAction(),
            ]);
    }

    /**
     * 对应03.2文档"手动调整积分（写入points_ledger，change_type=后台调整，需记录operator）"。
     */
    protected static function adjustPointsAction(): Action
    {
        return Action::make('adjustPoints')
            ->label('调整积分')
            ->icon('heroicon-o-adjustments-horizontal')
            ->schema([
                TextInput::make('amount')
                    ->label('调整数量（正数增加，负数扣减）')
                    ->numeric()
                    ->required(),
                Textarea::make('reason')->label('调整原因'),
            ])
            ->action(function (array $data, User $record, PointsService $pointsService) {
                $amount = (int) $data['amount'];
                $operator = auth()->user()?->username ?? 'system';

                if ($amount === 0) {
                    Notification::make()->title('调整数量不能为0')->danger()->send();

                    return;
                }

                if ($amount > 0) {
                    $pointsService->award($record, PointsChangeType::AdminAdjustment, $amount, operator: $operator);
                } else {
                    $pointsService->deduct($record, PointsChangeType::AdminAdjustment, abs($amount), operator: $operator);
                }

                Notification::make()->title('积分调整成功')->success()->send();
            });
    }

    /**
     * 对应03.2文档"拉黑/风控标记（更新users.status）"。
     */
    protected static function toggleBlacklistAction(): Action
    {
        return Action::make('toggleBlacklist')
            ->label(fn (User $record) => $record->status === UserStatus::Blacklisted ? '恢复正常' : '拉黑')
            ->icon(fn (User $record) => $record->status === UserStatus::Blacklisted ? 'heroicon-o-check-circle' : 'heroicon-o-no-symbol')
            ->color(fn (User $record) => $record->status === UserStatus::Blacklisted ? 'success' : 'danger')
            ->requiresConfirmation()
            ->action(function (User $record) {
                $record->update([
                    'status' => $record->status === UserStatus::Blacklisted ? UserStatus::Normal : UserStatus::Blacklisted,
                ]);

                Notification::make()->title('状态已更新')->success()->send();
            });
    }
}
