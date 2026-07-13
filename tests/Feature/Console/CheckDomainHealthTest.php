<?php

namespace Tests\Feature\Console;

use App\Enums\EnableStatus;
use App\Models\Domain;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class CheckDomainHealthTest extends TestCase
{
    use RefreshDatabase;

    public function test_healthy_domain_becomes_enabled_and_unhealthy_becomes_abnormal(): void
    {
        Http::fake([
            'https://good.example.com/*' => Http::response('ok', 200),
            'https://bad.example.com/*' => Http::response('', 500),
        ]);

        $good = Domain::create(['domain' => 'good.example.com', 'status' => EnableStatus::Abnormal]);
        $bad = Domain::create(['domain' => 'bad.example.com', 'status' => EnableStatus::Enabled]);

        $this->artisan('app:check-domain-health')->assertSuccessful();

        $good->refresh();
        $bad->refresh();

        $this->assertSame(EnableStatus::Enabled, $good->status);
        $this->assertSame(EnableStatus::Abnormal, $bad->status);
        $this->assertNotNull($good->last_check_time);
    }

    public function test_manually_disabled_domain_is_not_touched(): void
    {
        Http::fake(['*' => Http::response('ok', 200)]);

        $disabled = Domain::create(['domain' => 'disabled.example.com', 'status' => EnableStatus::Disabled]);

        $this->artisan('app:check-domain-health')->assertSuccessful();

        $disabled->refresh();
        $this->assertSame(EnableStatus::Disabled, $disabled->status);
        $this->assertNull($disabled->last_check_time);
    }
}
