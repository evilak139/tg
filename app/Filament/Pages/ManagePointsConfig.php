<?php

namespace App\Filament\Pages;

use App\Models\PointsConfig;
use BackedEnum;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

/**
 * 对应03.4文档"积分配置"：points_config表的可视化编辑界面，替代直接改数据库/Seeder的方式。
 * 涵盖签到积分、邀请1/2/3级积分、里程碑奖励、积分有效期、兑换比例、提现门槛、
 * 新账号提现限制天数、活跃度层级阈值、排行榜奖励覆盖前N名，对应01文档points_config表。
 */
class ManagePointsConfig extends Page
{
    protected string $view = 'filament.pages.manage-points-config';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCog6Tooth;

    protected static ?string $navigationLabel = '积分配置';

    protected static ?string $title = '积分配置';

    /** @var array<string, mixed>|null */
    public ?array $data = [];

    /** 按标量（非JSON）方式直接存取的key */
    protected const SCALAR_KEYS = [
        'checkin_base_points',
        'invite_l1_points',
        'invite_l2_points',
        'invite_l3_points',
        'new_account_gift_points',
        'milestone_5_bonus',
        'milestone_20_bonus',
        'milestone_100_bonus',
        'points_expire_months',
        'exchange_rate',
        'withdraw_min_threshold',
        'new_account_withdraw_limit_days',
        'leaderboard_top_n',
        'leaderboard_reward_points',
        'customer_service_contact',
    ];

    public function mount(): void
    {
        $this->form->fill($this->loadCurrentValues());
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('签到')
                    ->schema([
                        TextInput::make('checkin_base_points')->label('签到基础积分')->numeric()->required(),
                        Repeater::make('checkin_streak_bonus_rule')
                            ->label('连续签到额外奖励规则')
                            ->schema([
                                TextInput::make('streak')->label('连续天数')->numeric()->required(),
                                TextInput::make('bonus')->label('当日额外加分')->numeric()->required(),
                            ])
                            ->columns(2)
                            ->addActionLabel('新增一档'),
                    ]),
                Section::make('邀请返佣')
                    ->schema([
                        TextInput::make('invite_l1_points')->label('一级返佣积分')->numeric()->required(),
                        TextInput::make('invite_l2_points')->label('二级返佣积分')->numeric()->required(),
                        TextInput::make('invite_l3_points')->label('三级返佣积分')->numeric()->required(),
                        TextInput::make('new_account_gift_points')->label('新人礼包积分')->numeric()->required(),
                    ])
                    ->columns(2),
                Section::make('邀请里程碑奖励（5/20/100人档位固定，金额可调）')
                    ->schema([
                        TextInput::make('milestone_5_bonus')->label('满5人奖励')->numeric()->required(),
                        TextInput::make('milestone_20_bonus')->label('满20人奖励')->numeric()->required(),
                        TextInput::make('milestone_100_bonus')->label('满100人奖励')->numeric()->required(),
                    ])
                    ->columns(3),
                Section::make('积分有效期与兑换')
                    ->schema([
                        TextInput::make('points_expire_months')->label('积分有效期（月）')->numeric()->required(),
                        TextInput::make('exchange_rate')->label('兑换比例（多少积分兑1元）')->numeric()->required(),
                        TextInput::make('withdraw_min_threshold')->label('最低提现门槛（积分）')->numeric()->required(),
                        TextInput::make('new_account_withdraw_limit_days')->label('新账号提现风控天数')->numeric()->required(),
                        TextInput::make('customer_service_contact')->label('客服联系方式')->required(),
                    ])
                    ->columns(2),
                Section::make('活跃度层级阈值（天）')
                    ->schema([
                        TextInput::make('activity_tag_thresholds.active')->label('活跃 ≤')->numeric()->required(),
                        TextInput::make('activity_tag_thresholds.dormant')->label('待唤醒 ≤')->numeric()->required(),
                        TextInput::make('activity_tag_thresholds.churned')->label('流失 ≤（超过则为深度流失）')->numeric()->required(),
                    ])
                    ->columns(3),
                Section::make('排行榜')
                    ->schema([
                        TextInput::make('leaderboard_top_n')->label('奖励覆盖前N名')->numeric()->required(),
                        TextInput::make('leaderboard_reward_points')->label('每个上榜名次的奖励积分')->numeric()->required(),
                    ])
                    ->columns(2),
            ])
            ->statePath('data');
    }

    public function save(): void
    {
        $state = $this->form->getState();

        foreach (self::SCALAR_KEYS as $key) {
            PointsConfig::query()->updateOrCreate(['key' => $key], ['value' => (string) $state[$key]]);
        }

        PointsConfig::query()->updateOrCreate(
            ['key' => 'checkin_streak_bonus_rule'],
            ['value' => json_encode($state['checkin_streak_bonus_rule'] ?? [])]
        );

        PointsConfig::query()->updateOrCreate(
            ['key' => 'activity_tag_thresholds'],
            ['value' => json_encode($state['activity_tag_thresholds'] ?? [])]
        );

        Notification::make()->title('积分配置已保存')->success()->send();
    }

    /**
     * @return array<string, mixed>
     */
    protected function loadCurrentValues(): array
    {
        $rows = PointsConfig::query()->pluck('value', 'key');

        $data = [];

        foreach (self::SCALAR_KEYS as $key) {
            $data[$key] = $rows->get($key);
        }

        $data['checkin_streak_bonus_rule'] = json_decode($rows->get('checkin_streak_bonus_rule', '[]'), true) ?? [];
        $data['activity_tag_thresholds'] = json_decode($rows->get('activity_tag_thresholds', '{}'), true) ?? [];

        return $data;
    }
}
