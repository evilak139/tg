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
use UnitEnum;

/**
 * 独立的"机器人菜单"配置页（从"积分配置"页面拆分出来）：
 * - 主菜单四个内联按钮（邀请/签到/提现/我的）文案，回调式按钮，callback_data固定不受影响；
 * - 扩展菜单按钮：点击直接跳转外部链接（url按钮），后台可自由新增/删除/排序，不限于固定几个；
 * - /start 命令在Telegram命令菜单里显示的说明文案。
 *
 * 内联按钮文案和扩展菜单按钮改完立即生效（下一次发消息就用新内容，见MainMenu::keyboard()）；
 * 只有"/start命令说明"需要去"机器人配置"页面点一次"一键部署"才会同步给Telegram。
 */
class ManageBotMenu extends Page
{
    protected string $view = 'filament.pages.manage-bot-menu';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBars3;

    protected static ?string $navigationLabel = '机器人菜单';

    protected static ?string $title = '机器人菜单';

    protected static UnitEnum|string|null $navigationGroup = '系统管理';

    /** @var array<string, mixed>|null */
    public ?array $data = [];

    protected const SCALAR_KEYS = [
        'menu_button_invite',
        'menu_button_checkin',
        'menu_button_withdraw',
        'menu_button_profile',
        'start_command_description',
    ];

    protected const EXTRA_BUTTONS_KEY = 'bot_extra_menu_buttons';

    public function mount(): void
    {
        $this->form->fill($this->loadCurrentValues());
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('主菜单按钮文案')
                    ->description('回调式按钮（邀请/签到/提现/我的），点击后由机器人内部逻辑处理，只能改文案，不能改跳转目标。')
                    ->schema([
                        TextInput::make('menu_button_invite')->label('"邀请"按钮文案')->required(),
                        TextInput::make('menu_button_checkin')->label('"签到"按钮文案')->required(),
                        TextInput::make('menu_button_withdraw')->label('"提现"按钮文案')->required(),
                        TextInput::make('menu_button_profile')->label('"我的"按钮文案')->required(),
                    ])
                    ->columns(2),
                Section::make('扩展菜单按钮')
                    ->description('链接式按钮，点击直接跳转到配置的网址，显示在主菜单下方，一行一个按钮，按下方顺序从上到下排列，可自由新增、删除、拖拽排序。')
                    ->schema([
                        Repeater::make('extra_buttons')
                            ->label('')
                            ->schema([
                                TextInput::make('label')->label('按钮文案')->required(),
                                TextInput::make('url')->label('跳转链接')->url()->required(),
                            ])
                            ->columns(2)
                            ->addActionLabel('新增按钮')
                            ->reorderableWithButtons()
                            ->defaultItems(0),
                    ]),
                Section::make('Telegram命令菜单说明')
                    ->description('对应/start命令在Telegram聊天输入框旁命令列表里显示的说明文字。改完需要去"机器人配置"页面点一次"一键部署"才会同步给Telegram。')
                    ->schema([
                        TextInput::make('start_command_description')->label('/start 命令说明')->required(),
                    ]),
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
            ['key' => self::EXTRA_BUTTONS_KEY],
            ['value' => json_encode($state['extra_buttons'] ?? [])]
        );

        Notification::make()->title('机器人菜单已保存')->success()->send();
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

        $data['extra_buttons'] = json_decode($rows->get(self::EXTRA_BUTTONS_KEY, '[]'), true) ?? [];

        return $data;
    }
}
