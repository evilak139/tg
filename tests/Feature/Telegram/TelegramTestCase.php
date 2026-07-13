<?php

namespace Tests\Feature\Telegram;

use App\Enums\EnableStatus;
use App\Models\Domain;
use Database\Seeders\MessageTemplateSeeder;
use Database\Seeders\PointsConfigSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use SergiX44\Nutgram\Nutgram;
use SergiX44\Nutgram\Telegram\Types\Chat\Chat;
use SergiX44\Nutgram\Telegram\Types\User\User as TelegramUser;
use SergiX44\Nutgram\Testing\FakeNutgram;
use Tests\TestCase;

abstract class TelegramTestCase extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(PointsConfigSeeder::class);
        $this->seed(MessageTemplateSeeder::class);

        Domain::create([
            'domain' => 'go.example.com',
            'status' => EnableStatus::Enabled,
        ]);
    }

    protected function bot(): FakeNutgram
    {
        /** @var FakeNutgram $bot */
        $bot = app(Nutgram::class);

        return $bot;
    }

    protected function telegramUser(int $id, string $firstName = 'Test', ?string $username = null): TelegramUser
    {
        $user = new TelegramUser;
        $user->id = $id;
        $user->is_bot = false;
        $user->first_name = $firstName;
        $user->username = $username;

        return $user;
    }

    protected function chatFor(int $id): Chat
    {
        $chat = new Chat;
        $chat->id = $id;
        $chat->type = 'private';

        return $chat;
    }

    protected function start(int $tgUserId, ?string $payload = null, string $firstName = 'Test'): void
    {
        $bot = $this->bot();
        $bot->setCommonUser($this->telegramUser($tgUserId, $firstName));
        $bot->setCommonChat($this->chatFor($tgUserId));

        $text = $payload !== null ? "/start {$payload}" : '/start';

        $bot->hearText($text)->run();
    }

    protected function pressButton(int $tgUserId, string $data): void
    {
        $bot = $this->bot();
        $bot->setCommonUser($this->telegramUser($tgUserId));
        $bot->setCommonChat($this->chatFor($tgUserId));

        $bot->hearCallbackQueryData($data)->run();
    }
}
