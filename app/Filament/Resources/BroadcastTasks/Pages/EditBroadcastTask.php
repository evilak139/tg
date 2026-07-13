<?php

namespace App\Filament\Resources\BroadcastTasks\Pages;

use App\Filament\Resources\BroadcastTasks\BroadcastTaskResource;
use App\Filament\Resources\BroadcastTasks\Concerns\HandlesTargetFilter;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditBroadcastTask extends EditRecord
{
    use HandlesTargetFilter;

    protected static string $resource = BroadcastTaskResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        return $this->unpackTargetFilter($data);
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        return $this->packTargetFilter($data);
    }
}
