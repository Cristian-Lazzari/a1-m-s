<?php

namespace Tests\Feature\Hub;

use App\Models\Tenant;
use App\Models\WaAccount;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

abstract class HubTestCase extends TestCase
{
    use RefreshDatabase;

    protected const PLATFORM_ID = '9000';
    protected const APP_SECRET = 'test-app-secret';

    protected function setUp(): void
    {
        parent::setUp();

        Mail::fake();

        config([
            'whatsapp.app_id' => 'app-id',
            'whatsapp.app_secret' => self::APP_SECRET,
            'whatsapp.embedded_signup_config_id' => 'cfg',
            'whatsapp.webhook_verify_token' => 'verify-me',
            'whatsapp.platform.phone_number_id' => self::PLATFORM_ID,
            'whatsapp.platform.token' => 'platform-token',
        ]);
    }

    /** @return array{0: Tenant, 1: string} ristorante e sua chiave API in chiaro */
    protected function tenant(string $name = 'Trattoria Test', string $url = 'https://trattoria.test'): array
    {
        [$key, $hash] = Tenant::generateApiKey();

        $tenant = Tenant::create([
            'name' => $name,
            'app_url' => $url,
            'api_key_hash' => $hash,
            'private_token' => str_repeat('p', 40) . $name,
        ]);

        return [$tenant, $key];
    }

    protected function account(Tenant $tenant, string $phoneNumberId = '1111'): WaAccount
    {
        return WaAccount::create([
            'tenant_id' => $tenant->id,
            'waba_id' => 'w' . $phoneNumberId,
            'phone_number_id' => $phoneNumberId,
            'display_phone' => '+39 06 1234567',
            'token' => 'restaurant-token-' . $phoneNumberId,
            'status' => WaAccount::STATUS_ACTIVE,
        ]);
    }

    protected function signedWebhook(array $payload, ?string $secret = self::APP_SECRET): TestResponse
    {
        $body = json_encode($payload);
        $headers = ['CONTENT_TYPE' => 'application/json'];

        if ($secret !== null) {
            $headers['HTTP_X_HUB_SIGNATURE_256'] = 'sha256=' . hash_hmac('sha256', $body, $secret);
        }

        return $this->call('POST', '/webhook/whatsapp', [], [], [], $headers, $body);
    }

    protected function change(string $phoneNumberId, array $value): array
    {
        return ['entry' => [['id' => 'waba', 'changes' => [[
            'field' => 'messages',
            'value' => ['messaging_product' => 'whatsapp', 'metadata' => ['phone_number_id' => $phoneNumberId]] + $value,
        ]]]]];
    }
}
