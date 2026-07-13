<?php

namespace App\Filament\Resources\MessageTemplates\Pages;

use App\Enums\MessageTemplateType;
use App\Filament\Resources\MessageTemplates\MessageTemplateResource;
use Filament\Resources\Pages\CreateRecord;

class CreateMessageTemplate extends CreateRecord
{
    protected static string $resource = MessageTemplateResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['type'] = MessageTemplateType::Custom->value;
        $data['updated_by'] = auth()->user()?->username ?? 'system';

        return $data;
    }
}
