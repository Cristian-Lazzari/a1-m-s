<?php

namespace Tests\Feature\Hub;

use App\Models\Tenant;
use App\Models\WaDelivery;
use App\Models\WaMessage;
use App\Models\WaSender;
use Illuminate\Support\Facades\Http;

/**
 * White label: i ristoranti di un partner avvisano il ristoratore dal numero
 * del partner, gli altri restano sul numero di piattaforma di Future Plus.
 */
class PartnerSenderTest extends HubTestCase
{
    private const PARTNER_ID = '7000';

    private array $notification = [
        'channel' => 'platform',
        'to' => '393271622244',
        'fallback' => ['name' => 'full_emoji', 'language' => ['code' => 'it'], 'components' => []],
        'ref' => 'res:1',
    ];

    private function partner(): WaSender
    {
        return WaSender::create([
            'name' => 'Cassa Online',
            'phone_number_id' => self::PARTNER_ID,
            'display_phone' => '+39 02 1234567',
            'token' => 'partner-token',
        ]);
    }

    public function test_a_partner_restaurant_notifies_from_the_partner_number(): void
    {
        Http::fake(['graph.facebook.com/*' => Http::response(['messages' => [['id' => 'wamid.p1']]])]);
        [$tenant, $key] = $this->tenant();
        $tenant->update(['wa_sender_id' => $this->partner()->id]);

        $this->withToken($key)->postJson('/api/v1/messages', $this->notification)->assertOk()->assertJson(['ok' => true]);

        Http::assertSent(fn ($request) => str_ends_with($request->url(), '/' . self::PARTNER_ID . '/messages')
            && $request->hasHeader('Authorization', 'Bearer partner-token'));
        $this->assertSame(self::PARTNER_ID, WaMessage::first()->phone_number_id);
    }

    public function test_other_restaurants_stay_on_the_future_plus_number(): void
    {
        Http::fake(['graph.facebook.com/*' => Http::response(['messages' => [['id' => 'wamid.f1']]])]);
        $this->partner();
        [, $key] = $this->tenant();

        $this->withToken($key)->postJson('/api/v1/messages', $this->notification)->assertOk();

        Http::assertSent(fn ($request) => str_ends_with($request->url(), '/' . self::PLATFORM_ID . '/messages')
            && $request->hasHeader('Authorization', 'Bearer platform-token'));
    }

    public function test_replies_on_the_partner_number_reach_the_restaurant(): void
    {
        Http::fake(['*' => Http::response(['success' => true])]);
        $sender = $this->partner();
        [$tenant] = $this->tenant('Uno', 'https://uno.test');
        $tenant->update(['wa_sender_id' => $sender->id]);

        WaMessage::create([
            'tenant_id' => $tenant->id,
            'channel' => WaMessage::CHANNEL_PLATFORM,
            'phone_number_id' => self::PARTNER_ID,
            'recipient' => '393271622244',
            'wa_message_id' => 'wamid.partner',
            'type' => 'template',
            'ref' => 'res:9',
        ]);

        $this->signedWebhook($this->change(self::PARTNER_ID, [
            'messages' => [[
                'from' => '393271622244',
                'id' => 'wamid.r9',
                'type' => 'button',
                'context' => ['id' => 'wamid.partner'],
                'button' => ['payload' => 'CONFIRM', 'text' => 'Conferma'],
            ]],
        ]))->assertOk();

        Http::assertSent(fn ($request) => $request->url() === 'https://uno.test/api/private/wa/platform-reply'
            && $request['ref'] === 'res:9');
        $this->assertSame(1, WaDelivery::count());
    }

    public function test_commands_register_the_partner_number_and_link_a_restaurant(): void
    {
        [$tenant] = $this->tenant();

        $this->artisan('sender:create', ['name' => 'Cassa Online', 'phone_number_id' => self::PARTNER_ID, '--display' => '+39 02 1234567'])
            ->expectsQuestion('Token del WABA del partner (non viene mostrato mentre scrivi)', str_repeat('t', 40))
            ->assertSuccessful();

        $sender = WaSender::firstOrFail();
        $this->assertSame(str_repeat('t', 40), $sender->token);
        $this->assertStringNotContainsString(str_repeat('t', 40), json_encode(\DB::table('wa_senders')->first()));

        $this->artisan('tenant:sender', ['tenant' => $tenant->id, 'sender' => $sender->id])->assertSuccessful();
        $this->assertSame($sender->id, $tenant->fresh()->wa_sender_id);

        $this->artisan('tenant:sender', ['tenant' => $tenant->id, '--clear' => true])->assertSuccessful();
        $this->assertNull($tenant->fresh()->wa_sender_id);
    }

    public function test_the_future_plus_number_cannot_be_registered_as_a_partner(): void
    {
        $this->artisan('sender:create', ['name' => 'Furbo', 'phone_number_id' => self::PLATFORM_ID])->assertFailed();

        $this->assertSame(0, WaSender::count());
    }
}
