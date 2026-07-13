<?php

namespace App\Filament\Resources\BroadcastTasks\Pages;

use App\Filament\Resources\BroadcastTasks\BroadcastTaskResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListBroadcastTasks extends ListRecords
{
    protected static string $resource = BroadcastTaskResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
