<?php

namespace Tests\Feature;

use App\Http\Controllers\Webhooks\WaController;
use App\Models\Source;
use App\Models\WaAccount;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class WaRestaurantNumberTest extends TestCase
{
    use RefreshDatabase;

    private int $sourceId;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'configurazione.WA_ID' => '9999',
            'configurazione.WA_TO' => 'Bearer platform',
            'whatsapp.app_id' => 'app',
            'whatsapp.app_secret' => 'secret',
            'whatsapp.embedded_signup_config_id' => 'cfg',
        ]);

        $this->sourceId = DB::table('sources')->insertGetId([
            'db_name' => 'tenant_demo',
            'host' => '127.0.0.1',
            'username' => 'tenant_user',
            'token' => 'smtp',
            'private_token' => 'tenant-private',
            'app_url' => 'https://tenant.example.test',
            'app_domain' => 'tenant.example.test',
            'app_name' => 'Tenant Demo',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function test_events_of_a_restaurant_number_go_to_its_dashboard_and_skip_the_platform_flow(): void
    {
        WaAccount::create([
            'source_id' => $this->sourceId,
            'waba_id' => 'w1',
            'phone_number_id' => '1111',
            'token' => 't',
            'status' => 'active',
        ]);

        Http::fake(['tenant.example.test/*' => Http::response(['success' => true])]);

        // Il flusso del numero di piattaforma non deve partire.
        $controller = \Mockery::mock(WaController::class)->makePartial();
        $controller->shouldAllowMockingProtectedMethods();
        $controller->shouldNotReceive('handle_p2');
        $this->app->instance(WaController::class, $controller);

        $value = [
            'metadata' => ['phone_number_id' => '1111'],
            'messages' => [[
                'from' => '393331112233',
                'id' => 'wamid.c1',
                'type' => 'button',
                'context' => ['id' => 'wamid.reminder'],
                'button' => ['payload' => 'CANCEL:5', 'text' => 'Devo disdire'],
            ]],
        ];

        $this->postJson('/webhook/wa', ['entry' => [['id' => 'w1', 'changes' => [['field' => 'messages', 'value' => $value]]]]])
            ->assertOk();

        Http::assertSent(fn ($request) => $request->url() === 'https://tenant.example.test/api/private/wa/inbound'
            && $request->hasHeader('Authorization', 'Bearer tenant-private')
            && $request['value']['messages'][0]['button']['payload'] === 'CANCEL:5');
    }

    public function test_unknown_numbers_keep_the_platform_flow(): void
    {
        DB::table('messages')->insert([
            'wa_id' => 'wamid.notify',
            'source' => $this->sourceId,
            'type' => '0',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $controller = \Mockery::mock(WaController::class)->makePartial();
        $controller->shouldAllowMockingProtectedMethods();
        $controller->shouldReceive('handle_p2')->once()->andReturnNull();
        $this->app->instance(WaController::class, $controller);

        $this->postJson('/webhook/wa', ['entry' => [['changes' => [['value' => [
            'metadata' => ['phone_number_id' => '9999'],
            'messages' => [[
                'from' => '393271622244',
                'type' => 'interactive',
                'context' => ['id' => 'wamid.notify'],
                'interactive' => ['button_reply' => ['id' => 'Conferma', 'title' => 'Conferma']],
            ]],
        ]]]]]])->assertOk();
    }

    public function test_restaurateur_reply_quoting_a_customer_notice_is_relayed_to_the_customer(): void
    {
        DB::table('messages')->insert([
            'wa_id' => 'wamid.inbox',
            'source' => $this->sourceId,
            'type' => 'inbox',
            'ref' => 'conv:12',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        Http::fake([
            'tenant.example.test/*' => Http::response(['success' => true]),
            'graph.facebook.com/*' => Http::response(['messages' => [['id' => 'wamid.ack']]]),
        ]);

        // Il database del ristorante non c'è nei test: niente finestra 24h da aggiornare.
        $controller = \Mockery::mock(WaController::class)->makePartial();
        $controller->shouldAllowMockingProtectedMethods();
        $controller->shouldReceive('connectToTenant')->andThrow(new \RuntimeException('no tenant db'));
        $this->app->instance(WaController::class, $controller);

        $this->postJson('/webhook/wa', ['entry' => [['changes' => [['value' => [
            'metadata' => ['phone_number_id' => '9999'],
            'messages' => [[
                'from' => '393271622244',
                'id' => 'wamid.r1',
                'type' => 'text',
                'context' => ['id' => 'wamid.inbox'],
                'text' => ['body' => 'Certo, vi aspettiamo alle 21'],
            ]],
        ]]]]]])->assertOk();

        Http::assertSent(fn ($request) => $request->url() === 'https://tenant.example.test/api/private/wa/relay'
            && $request['ref'] === 'conv:12'
            && $request['text'] === 'Certo, vi aspettiamo alle 21');

        Http::assertSent(fn ($request) => str_contains($request->url(), '/9999/messages')
            && str_starts_with($request['text']['body'] ?? '', '✅'));
    }

    public function test_connect_page_requires_a_valid_signature(): void
    {
        $expires = now()->addMinutes(10)->getTimestamp();

        $this->get('/whatsapp/connect?' . http_build_query(['db' => 'tenant_demo', 'expires' => $expires, 'signature' => 'nope']))
            ->assertForbidden();

        $this->get('/whatsapp/connect?' . http_build_query([
            'db' => 'tenant_demo',
            'expires' => now()->subMinute()->getTimestamp(),
            'signature' => hash_hmac('sha256', 'tenant_demo|' . now()->subMinute()->getTimestamp(), 'tenant-private'),
        ]))->assertForbidden();

        $this->get($this->connectUrl())
            ->assertOk()
            ->assertSee('Collega il WhatsApp di Tenant Demo');
    }

    public function test_complete_onboarding_registers_the_number_creates_templates_and_tells_the_dashboard(): void
    {
        Http::fake([
            'graph.facebook.com/*/oauth/access_token*' => Http::response(['access_token' => 'biz-token']),
            'graph.facebook.com/*/w1/subscribed_apps' => Http::response(['success' => true]),
            'graph.facebook.com/*/p1/register' => Http::response(['success' => true]),
            'graph.facebook.com/*/w1/message_templates' => Http::response(['id' => 'tpl', 'status' => 'PENDING']),
            'graph.facebook.com/*/p1*' => Http::response(['display_phone_number' => '+39 06 1234567', 'verified_name' => 'Tenant Demo']),
            'tenant.example.test/*' => Http::response(['success' => true]),
        ]);

        $this->get($this->connectUrl())->assertOk();

        $this->postJson('/whatsapp/connect/complete', [
            'code' => 'abc',
            'waba_id' => 'w1',
            'phone_number_id' => 'p1',
            'business_id' => 'b1',
        ])->assertOk()->assertJson(['ok' => true, 'display_phone' => '+39 06 1234567']);

        $account = WaAccount::where('phone_number_id', 'p1')->first();
        $this->assertSame('active', $account->status);
        $this->assertSame('biz-token', $account->token);
        $this->assertMatchesRegularExpression('/^\d{6}$/', $account->pin);
        $this->assertStringNotContainsString('biz-token', DB::table('wa_accounts')->value('token'));

        Http::assertSent(fn ($request) => str_ends_with($request->url(), '/w1/message_templates') && $request['language'] === 'it');
        Http::assertSent(fn ($request) => str_ends_with($request->url(), '/w1/message_templates') && $request['language'] === 'en');
        Http::assertSent(fn ($request) => $request->url() === 'https://tenant.example.test/api/private/wa/account'
            && $request['token'] === 'biz-token'
            && $request['phone_number_id'] === 'p1');
    }

    public function test_complete_without_a_signed_session_is_refused(): void
    {
        $this->postJson('/whatsapp/connect/complete', [
            'code' => 'abc', 'waba_id' => 'w1', 'phone_number_id' => 'p1',
        ])->assertStatus(419);
    }

    private function connectUrl(): string
    {
        $expires = now()->addMinutes(10)->getTimestamp();

        return '/whatsapp/connect?' . http_build_query([
            'db' => 'tenant_demo',
            'expires' => $expires,
            'signature' => hash_hmac('sha256', 'tenant_demo|' . $expires, 'tenant-private'),
            'return' => 'https://tenant.example.test/admin/whatsapp',
        ]);
    }
}
