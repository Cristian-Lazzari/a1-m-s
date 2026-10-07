<?php

namespace Tests\Feature\Hub;

use App\Models\Tenant;
use App\Models\WaContact;
use App\Models\WaMessage;
use Illuminate\Support\Facades\Http;

class TenantApiTest extends HubTestCase
{
    private array $notification = [
        'channel' => 'platform',
        'to' => '393271622244',
        'message' => [
            'type' => 'interactive',
            'interactive' => [
                'type' => 'button',
                'body' => ['text' => 'Nuova prenotazione'],
                'action' => ['buttons' => [['type' => 'reply', 'reply' => ['id' => 'confirm', 'title' => 'Conferma']]]],
            ],
        ],
        'fallback' => [
            'name' => 'notifica_prenotazione',
            'language' => ['code' => 'it'],
            'components' => [],
        ],
        'ref' => 'res:12',
    ];

    public function test_requests_without_a_valid_key_are_refused(): void
    {
        $this->postJson('/api/v1/messages', $this->notification)->assertUnauthorized();
        $this->withToken('fp_wrong')->postJson('/api/v1/messages', $this->notification)->assertUnauthorized();

        [$tenant, $key] = $this->tenant();
        $tenant->update(['active' => false]);
        $this->withToken($key)->postJson('/api/v1/messages', $this->notification)->assertUnauthorized();
    }

    public function test_only_the_hash_of_the_key_is_stored(): void
    {
        [$tenant, $key] = $this->tenant();

        $this->assertStringStartsWith('fp_', $key);
        $this->assertSame(hash('sha256', $key), $tenant->api_key_hash);
        $this->assertStringNotContainsString($tenant->private_token, json_encode(\DB::table('tenants')->first()));
    }

    public function test_with_the_window_closed_the_hub_sends_the_template(): void
    {
        Http::fake(['graph.facebook.com/*' => Http::response(['messages' => [['id' => 'wamid.t1']]])]);
        [, $key] = $this->tenant();

        $this->withToken($key)->postJson('/api/v1/messages', $this->notification)
            ->assertOk()
            ->assertJson(['ok' => true, 'wa_message_id' => 'wamid.t1', 'used' => 'fallback']);

        Http::assertSent(fn ($request) => str_ends_with($request->url(), '/' . self::PLATFORM_ID . '/messages')
            && $request->hasHeader('Authorization', 'Bearer platform-token')
            && $request['type'] === 'template'
            && $request['template']['name'] === 'notifica_prenotazione');

        $this->assertSame('res:12', WaMessage::where('wa_message_id', 'wamid.t1')->first()->ref);
    }

    public function test_with_the_window_open_the_hub_sends_the_free_message(): void
    {
        Http::fake(['graph.facebook.com/*' => Http::response(['messages' => [['id' => 'wamid.i1']]])]);
        [, $key] = $this->tenant();
        WaContact::create(['phone_number_id' => self::PLATFORM_ID, 'wa_id' => '393271622244', 'last_inbound_at' => now()->subHours(2)]);

        $this->withToken($key)->postJson('/api/v1/messages', $this->notification)
            ->assertOk()
            ->assertJson(['used' => 'message']);

        Http::assertSent(fn ($request) => $request['type'] === 'interactive');
    }

    public function test_if_meta_says_the_window_is_closed_the_hub_falls_back_to_the_template(): void
    {
        Http::fake(['graph.facebook.com/*' => Http::sequence()
            ->push(['error' => ['code' => 131047, 'message' => 'Re-engagement message']], 400)
            ->push(['messages' => [['id' => 'wamid.t2']]])]);
        [, $key] = $this->tenant();
        WaContact::create(['phone_number_id' => self::PLATFORM_ID, 'wa_id' => '393271622244', 'last_inbound_at' => now()]);

        $this->withToken($key)->postJson('/api/v1/messages', $this->notification)
            ->assertOk()
            ->assertJson(['wa_message_id' => 'wamid.t2', 'used' => 'fallback']);

        $this->assertSame(0, WaContact::count(), 'La finestra sbagliata va dimenticata');
    }

    public function test_restaurant_channel_uses_the_restaurant_number_and_token(): void
    {
        Http::fake(['graph.facebook.com/*' => Http::response(['messages' => [['id' => 'wamid.r1']]])]);
        [$tenant, $key] = $this->tenant();

        $payload = [
            'channel' => 'restaurant',
            'to' => '+39 333 1112233',
            'fallback' => ['name' => 'promemoria_prenotazione', 'language' => ['code' => 'it'], 'components' => []],
            'ref' => 'reminder:5',
        ];

        $this->withToken($key)->postJson('/api/v1/messages', $payload)
            ->assertStatus(409)
            ->assertJson(['error' => 'restaurant_number_not_connected']);

        $this->account($tenant, '1111');

        $this->withToken($key)->postJson('/api/v1/messages', $payload)->assertOk();

        Http::assertSent(fn ($request) => str_ends_with($request->url(), '/1111/messages')
            && $request->hasHeader('Authorization', 'Bearer restaurant-token-1111')
            && $request['to'] === '393331112233');
    }

    public function test_a_restaurant_cannot_send_from_another_restaurants_number(): void
    {
        Http::fake(['graph.facebook.com/*' => Http::response(['messages' => [['id' => 'wamid.x']]])]);
        [$owner] = $this->tenant('Uno', 'https://uno.test');
        [, $otherKey] = $this->tenant('Due', 'https://due.test');
        $this->account($owner, '1111');

        $this->withToken($otherKey)->postJson('/api/v1/messages', [
            'channel' => 'restaurant',
            'to' => '393331112233',
            'message' => ['type' => 'text', 'text' => ['body' => 'ciao']],
        ])->assertStatus(409);

        Http::assertNothingSent();
    }

    public function test_bad_payloads_are_rejected(): void
    {
        [, $key] = $this->tenant();

        $this->withToken($key)->postJson('/api/v1/messages', ['channel' => 'platform', 'to' => '3933'])->assertStatus(422);
        $this->withToken($key)->postJson('/api/v1/messages', ['channel' => 'sms', 'to' => '393331112233', 'message' => ['type' => 'text']])->assertStatus(422);
        $this->withToken($key)->postJson('/api/v1/messages', ['channel' => 'platform', 'to' => '393331112233', 'message' => ['type' => 'image']])->assertStatus(422);
    }

    public function test_account_status(): void
    {
        [$tenant, $key] = $this->tenant();

        $this->withToken($key)->getJson('/api/v1/account')->assertOk()->assertJson(['connected' => false]);

        $this->account($tenant);

        $this->withToken($key)->getJson('/api/v1/account')
            ->assertOk()
            ->assertJson(['connected' => true, 'display_phone' => '+39 06 1234567'])
            ->assertJsonMissing(['token' => 'restaurant-token-1111']);
    }

    public function test_tenant_create_command_shows_the_key_once_and_stores_only_its_hash(): void
    {
        $this->artisan('tenant:create', ['name' => 'Da Mario', 'app_url' => 'https://damario.test'])
            ->expectsQuestion('PRIVATE_REPORT_TOKEN del gestionale (non viene mostrato mentre scrivi)', str_repeat('s', 40))
            ->expectsOutputToContain('WA_HUB_API_KEY=fp_')
            ->assertSuccessful();

        $tenant = Tenant::where('name', 'Da Mario')->first();
        $this->assertSame(str_repeat('s', 40), $tenant->private_token);
        $this->assertSame(64, strlen($tenant->api_key_hash));
    }

    public function test_tenant_create_refuses_a_short_private_token(): void
    {
        $this->artisan('tenant:create', ['name' => 'Da Mario', 'app_url' => 'https://damario.test'])
            ->expectsQuestion('PRIVATE_REPORT_TOKEN del gestionale (non viene mostrato mentre scrivi)', 'corto')
            ->assertFailed();

        $this->assertSame(0, Tenant::count());
    }
}
