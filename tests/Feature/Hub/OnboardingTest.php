<?php

namespace Tests\Feature\Hub;

use App\Models\WaAccount;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

class OnboardingTest extends HubTestCase
{
    public function test_the_connect_page_opens_only_with_a_link_requested_by_the_restaurant(): void
    {
        [, $key] = $this->tenant();

        $this->get('/whatsapp/connect?token=made-up')->assertForbidden();

        $url = $this->withToken($key)
            ->postJson('/api/v1/onboarding-links', ['return_url' => 'https://trattoria.test/admin/whatsapp'])
            ->assertOk()
            ->json('url');

        $this->get($url)->assertOk()->assertSee('Collega il WhatsApp di Trattoria Test');

        // Monouso: da un'altra sessione lo stesso link non vale più.
        $this->flushSession();
        $this->get($url)->assertForbidden();
    }

    public function test_return_url_must_point_to_the_restaurant_dashboard(): void
    {
        [, $key] = $this->tenant();

        $url = $this->withToken($key)
            ->postJson('/api/v1/onboarding-links', ['return_url' => 'https://evil.test/phish'])
            ->json('url');

        $this->get($url)->assertOk()->assertDontSee('evil.test');
    }

    public function test_complete_connects_the_number_and_keeps_the_token_in_the_hub(): void
    {
        Http::fake([
            'graph.facebook.com/*/oauth/access_token*' => Http::response(['access_token' => 'biz-token']),
            'graph.facebook.com/*/555/subscribed_apps' => Http::response(['success' => true]),
            'graph.facebook.com/*/777/register' => Http::response(['success' => true]),
            'graph.facebook.com/*/555/message_templates' => Http::response(['id' => 'tpl', 'status' => 'PENDING']),
            'graph.facebook.com/*/777*' => Http::response(['display_phone_number' => '+39 06 1234567', 'verified_name' => 'Trattoria Test']),
            'trattoria.test/*' => Http::response(['success' => true]),
        ]);
        [$tenant, $key] = $this->tenant();

        $this->get($this->withToken($key)->postJson('/api/v1/onboarding-links')->json('url'))->assertOk();

        $this->postJson('/whatsapp/connect/complete', [
            'code' => 'abc',
            'waba_id' => '555',
            'phone_number_id' => '777',
            'business_id' => '999',
        ])->assertOk()->assertJson(['ok' => true, 'display_phone' => '+39 06 1234567']);

        $account = WaAccount::where('phone_number_id', '777')->first();
        $this->assertSame('active', $account->status);
        $this->assertSame('biz-token', $account->token);
        $this->assertMatchesRegularExpression('/^\d{6}$/', $account->pin);
        $this->assertStringNotContainsString('biz-token', (string) DB::table('wa_accounts')->value('token'));

        Http::assertSent(fn ($request) => str_ends_with($request->url(), '/555/message_templates') && $request['language'] === 'it');
        Http::assertSent(fn ($request) => str_ends_with($request->url(), '/555/message_templates') && $request['language'] === 'en');

        // Il gestionale viene avvisato, ma il token non esce dall'hub.
        Http::assertSent(fn ($request) => $request->url() === 'https://trattoria.test/api/private/wa/account'
            && $request['connected'] === true
            && ! isset($request['token']));
    }

    public function test_a_number_of_another_restaurant_cannot_be_taken(): void
    {
        Http::fake();
        [$owner] = $this->tenant('Uno', 'https://uno.test');
        [, $key] = $this->tenant('Due', 'https://due.test');
        $this->account($owner, '777');

        $this->get($this->withToken($key)->postJson('/api/v1/onboarding-links')->json('url'))->assertOk();

        $this->postJson('/whatsapp/connect/complete', [
            'code' => 'abc', 'waba_id' => '555', 'phone_number_id' => '777',
        ])->assertStatus(409);

        $this->assertSame($owner->id, WaAccount::where('phone_number_id', '777')->first()->tenant_id);
        Http::assertNothingSent();
    }

    public function test_complete_without_a_session_is_refused(): void
    {
        $this->postJson('/whatsapp/connect/complete', [
            'code' => 'abc', 'waba_id' => '555', 'phone_number_id' => '777',
        ])->assertStatus(419);
    }

    public function test_meta_errors_are_saved_and_shown(): void
    {
        Http::fake([
            'graph.facebook.com/*/oauth/access_token*' => Http::response(['access_token' => 'biz-token']),
            'graph.facebook.com/*/555/subscribed_apps' => Http::response(['error' => ['message' => 'Permission denied']], 403),
        ]);
        [, $key] = $this->tenant();
        $this->get($this->withToken($key)->postJson('/api/v1/onboarding-links')->json('url'));

        $this->postJson('/whatsapp/connect/complete', ['code' => 'abc', 'waba_id' => '555', 'phone_number_id' => '777'])
            ->assertStatus(422)
            ->assertJsonFragment(['ok' => false]);

        $account = WaAccount::first();
        $this->assertSame('error', $account->status);
        $this->assertStringContainsString('Permission denied', $account->last_error);
    }
}
