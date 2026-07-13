<?php

namespace App\Filament\Resources\BroadcastTasks\Pages;

use App\Enums\BroadcastStatus;
use App\Filament\Resources\BroadcastTasks\BroadcastTaskResource;
use App\Filament\Resources\BroadcastTasks\Concerns\HandlesTargetFilter;
use Filament\Resources\Pages\CreateRecord;

class CreateBroadcastTask extends CreateRecord
{
    use HandlesTargetFilter;

    protected static string $resource = BroadcastTaskResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data = $this->packTargetFilter($data);
        $data['status'] = BroadcastStatus::Pending;
        $data['created_by'] = auth()->user()?->username ?? 'system';

        return $data;
    }
}
