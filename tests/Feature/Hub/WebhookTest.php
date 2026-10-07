<?php

namespace Tests\Feature\Hub;

use App\Models\WaContact;
use App\Models\WaDelivery;
use App\Models\WaMessage;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;

class WebhookTest extends HubTestCase
{
    public function test_verification_handshake_needs_the_configured_token(): void
    {
        $this->get('/webhook/whatsapp?hub.mode=subscribe&hub.verify_token=wrong&hub.challenge=42')->assertForbidden();
        $this->get('/webhook/whatsapp?hub.mode=subscribe&hub.verify_token=verify-me&hub.challenge=42')
            ->assertOk()
            ->assertSee('42');

        config(['whatsapp.webhook_verify_token' => '']);
        $this->get('/webhook/whatsapp?hub.mode=subscribe&hub.verify_token=&hub.challenge=42')->assertForbidden();
    }

    public function test_unsigned_or_forged_webhooks_are_rejected(): void
    {
        Http::fake();
        [$tenant] = $this->tenant();
        $this->account($tenant);
        $payload = $this->change('1111', ['messages' => [['from' => '393331112233', 'id' => 'wamid.1', 'type' => 'text', 'text' => ['body' => 'ciao']]]]);

        $this->signedWebhook($payload, null)->assertForbidden();
        $this->signedWebhook($payload, 'not-the-secret')->assertForbidden();

        $this->assertSame(0, WaDelivery::count());
        Http::assertNothingSent();
    }

    public function test_without_app_secret_everything_is_refused(): void
    {
        config(['whatsapp.app_secret' => '']);

        $this->signedWebhook($this->change('1111', []), 'anything')->assertStatus(503);
    }

    public function test_events_of_a_restaurant_number_reach_only_that_restaurant(): void
    {
        Http::fake(['*' => Http::response(['success' => true])]);
        [$tenant] = $this->tenant('Uno', 'https://uno.test');
        [$other] = $this->tenant('Due', 'https://due.test');
        $this->account($tenant, '1111');
        $this->account($other, '2222');

        $this->signedWebhook($this->change('1111', [
            'contacts' => [['wa_id' => '393331112233', 'profile' => ['name' => 'Luca']]],
            'messages' => [['from' => '393331112233', 'id' => 'wamid.c1', 'type' => 'button', 'button' => ['payload' => 'CANCEL:5', 'text' => 'Devo disdire']]],
        ]))->assertOk();

        Http::assertSentCount(1);
        Http::assertSent(fn ($request) => $request->url() === 'https://uno.test/api/private/wa/inbound'
            && $request->hasHeader('Authorization', 'Bearer ' . $tenant->private_token)
            && $request['value']['messages'][0]['button']['payload'] === 'CANCEL:5');

        $this->assertSame(WaDelivery::STATUS_SENT, WaDelivery::first()->status);
        $this->assertTrue(WaContact::where('phone_number_id', '1111')->where('wa_id', '393331112233')->exists());
    }

    public function test_restaurateur_reply_goes_to_the_restaurant_that_sent_the_quoted_message(): void
    {
        Http::fake(['*' => Http::response(['success' => true])]);
        [$tenant] = $this->tenant('Uno', 'https://uno.test');
        $this->tenant('Due', 'https://due.test');

        WaMessage::create([
            'tenant_id' => $tenant->id,
            'channel' => WaMessage::CHANNEL_PLATFORM,
            'phone_number_id' => self::PLATFORM_ID,
            'recipient' => '393271622244',
            'wa_message_id' => 'wamid.notify',
            'type' => 'interactive',
            'ref' => 'res:77',
        ]);

        $this->signedWebhook($this->change(self::PLATFORM_ID, [
            'messages' => [[
                'from' => '393271622244',
                'id' => 'wamid.r1',
                'type' => 'interactive',
                'context' => ['id' => 'wamid.notify'],
                'interactive' => ['type' => 'button_reply', 'button_reply' => ['id' => 'confirm', 'title' => 'Conferma']],
            ]],
        ]))->assertOk();

        Http::assertSentCount(1);
        Http::assertSent(fn ($request) => $request->url() === 'https://uno.test/api/private/wa/platform-reply'
            && $request['ref'] === 'res:77'
            && $request['message']['interactive']['button_reply']['id'] === 'confirm');

        // Il ristoratore ha scritto: la sua finestra di 24 ore è aperta.
        $this->assertTrue(WaContact::where('phone_number_id', self::PLATFORM_ID)->where('wa_id', '393271622244')->exists());
    }

    public function test_unknown_numbers_and_free_platform_messages_go_nowhere(): void
    {
        Http::fake();
        $this->tenant();

        $this->signedWebhook($this->change('5555', ['messages' => [['from' => '39333', 'id' => 'x', 'type' => 'text', 'text' => ['body' => 'a']]]]))->assertOk();
        $this->signedWebhook($this->change(self::PLATFORM_ID, ['messages' => [['from' => '39333', 'id' => 'y', 'type' => 'text', 'text' => ['body' => 'ciao']]]]))->assertOk();

        Http::assertNothingSent();
        $this->assertSame(0, WaDelivery::count());
    }

    public function test_delivery_receipts_update_our_messages_without_going_backwards(): void
    {
        Http::fake();
        [$tenant] = $this->tenant();
        $message = WaMessage::create([
            'tenant_id' => $tenant->id,
            'channel' => WaMessage::CHANNEL_PLATFORM,
            'phone_number_id' => self::PLATFORM_ID,
            'recipient' => '393271622244',
            'wa_message_id' => 'wamid.n1',
            'type' => 'text',
        ]);

        $this->signedWebhook($this->change(self::PLATFORM_ID, ['statuses' => [['id' => 'wamid.n1', 'status' => 'read', 'recipient_id' => '393271622244']]]));
        $this->signedWebhook($this->change(self::PLATFORM_ID, ['statuses' => [['id' => 'wamid.n1', 'status' => 'delivered', 'recipient_id' => '393271622244']]]));

        $this->assertSame('read', $message->fresh()->status);
    }

    public function test_failed_platform_notification_raises_an_alert_and_closes_the_window(): void
    {
        Http::fake();
        [$tenant] = $this->tenant();
        WaContact::create(['phone_number_id' => self::PLATFORM_ID, 'wa_id' => '393271622244', 'last_inbound_at' => now()]);
        WaMessage::create([
            'tenant_id' => $tenant->id,
            'channel' => WaMessage::CHANNEL_PLATFORM,
            'phone_number_id' => self::PLATFORM_ID,
            'recipient' => '393271622244',
            'wa_message_id' => 'wamid.f1',
            'type' => 'text',
        ]);

        $this->signedWebhook($this->change(self::PLATFORM_ID, ['statuses' => [[
            'id' => 'wamid.f1',
            'status' => 'failed',
            'recipient_id' => '393271622244',
            'errors' => [['code' => 131047, 'title' => 'Re-engagement message']],
        ]]]));

        $this->assertSame('failed', WaMessage::where('wa_message_id', 'wamid.f1')->first()->status);
        $this->assertSame(0, WaContact::count());
        Mail::assertSent(\App\Mail\WaFailureAlertMail::class);
    }

    public function test_a_restaurant_that_is_down_gets_the_event_later(): void
    {
        [$tenant] = $this->tenant('Uno', 'https://uno.test');
        $this->account($tenant, '1111');

        Http::fake(['*' => Http::sequence()->push('down', 500)->push(['success' => true])]);

        $this->signedWebhook($this->change('1111', ['messages' => [['from' => '393331112233', 'id' => 'wamid.c9', 'type' => 'text', 'text' => ['body' => 'ciao']]]]))->assertOk();

        $delivery = WaDelivery::first();
        $this->assertSame(WaDelivery::STATUS_PENDING, $delivery->status);
        $this->assertSame(1, $delivery->attempts);
        $this->assertTrue($delivery->next_attempt_at->isFuture());

        // Il gestionale torna su: il cron consegna.
        $this->travel(2)->minutes();
        $this->artisan('wa:retry-deliveries')->assertSuccessful();

        $this->assertSame(WaDelivery::STATUS_SENT, $delivery->fresh()->status);
    }

    public function test_after_the_last_attempt_the_event_is_marked_failed_and_someone_is_told(): void
    {
        config(['whatsapp.delivery_max_attempts' => 2]);
        [$tenant] = $this->tenant('Uno', 'https://uno.test');
        $this->account($tenant, '1111');
        Http::fake(['*' => Http::response('down', 500)]);

        $this->signedWebhook($this->change('1111', ['messages' => [['from' => '393331112233', 'id' => 'wamid.c10', 'type' => 'text', 'text' => ['body' => 'ciao']]]]));
        $this->travel(2)->minutes();
        $this->artisan('wa:retry-deliveries');

        $this->assertSame(WaDelivery::STATUS_FAILED, WaDelivery::first()->status);
        Mail::assertSent(\App\Mail\WaFailureAlertMail::class);
    }
}
