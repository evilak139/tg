<?php

namespace App\Filament\Resources\Bots\Pages;

use App\Filament\Resources\Bots\BotResource;
use App\Filament\Resources\Bots\Concerns\ValidatesBotToken;
use Filament\Resources\Pages\CreateRecord;

class CreateBot extends CreateRecord
{
    use ValidatesBotToken;

    protected static string $resource = BotResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['bot_username'] = $this->validateTokenAndFetchUsername($data['token']);

        return $data;
    }
}
