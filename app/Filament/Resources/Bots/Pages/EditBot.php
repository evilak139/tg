<?php

namespace App\Filament\Resources\Bots\Pages;

use App\Filament\Resources\Bots\BotResource;
use App\Filament\Resources\Bots\Concerns\ValidatesBotToken;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditBot extends EditRecord
{
    use ValidatesBotToken;

    protected static string $resource = BotResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        if (array_key_exists('token', $data) && filled($data['token'])) {
            $data['bot_username'] = $this->validateTokenAndFetchUsername($data['token']);
        }

        return $data;
    }
}
