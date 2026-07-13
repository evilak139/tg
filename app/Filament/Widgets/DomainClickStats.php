<?php

namespace App\Filament\Widgets;

use App\Models\Domain;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;

/**
 * 对应03.1文档"短链访问量统计（按域名拆分）：来自invite_links.click_count按domain_id聚合"。
 */
class DomainClickStats extends TableWidget
{
    protected static ?string $heading = '短链访问量统计（按域名）';

    public function table(Table $table): Table
    {
        return $table
            ->query(fn (): Builder => Domain::query()->withSum('inviteLinks', 'click_count'))
            ->columns([
                TextColumn::make('domain')->label('域名'),
                TextColumn::make('status')->label('状态')->badge(),
                TextColumn::make('invite_links_sum_click_count')->label('累计访问量')->numeric()->sortable()->default(0),
            ])
            ->defaultSort('invite_links_sum_click_count', 'desc')
            ->paginated(false);
    }
}
