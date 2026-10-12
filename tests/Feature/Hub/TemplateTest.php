<?php

namespace Tests\Feature\Hub;

use App\Models\WaAccount;
use App\Models\WaDelivery;
use App\Models\WaTemplate;
use App\Services\Whatsapp\ReminderTemplate;
use Illuminate\Support\Facades\Http;

class TemplateTest extends HubTestCase
{
    public function test_validation_catches_what_meta_would_reject(): void
    {
        $ok = ReminderTemplate::DEFAULTS['it'];
        $this->assertSame([], ReminderTemplate::validate($ok['body'], $ok['buttons']));

        $errors = fn (string $body, array $buttons = ['Sì', 'No']) => implode(' | ', ReminderTemplate::validate($body, $buttons));

        $this->assertStringContainsString('mancano {ora}', $errors('Ciao Luca, ci vediamo {data}.'));
        $this->assertStringContainsString('iniziare con una variabile', $errors('{nome}, ci vediamo {data} alle {ora}.'));
        $this->assertStringContainsString('finire con una variabile', $errors('Ciao, ci vediamo {data} alle {ora}'));
        $this->assertStringContainsString('attaccate', $errors('Ciao, ci vediamo {data} {ora} grazie.'));
        $this->assertSame('', $errors('Ciao, ci vediamo {data}, alle {ora}, {persone} persone.'));
        $this->assertStringContainsString('{tavolo}', $errors('Ciao, il {tavolo} è pronto {data} alle {ora}.'));
        $this->assertStringContainsString('una volta sola', $errors('Ciao {nome}, {data} alle {ora}, ciao {nome}.'));
        $this->assertStringContainsString('testi diversi', $errors('Ciao, {data} alle {ora}.', ['Ok', 'ok']));
        $this->assertStringContainsString('troppo lungo', $errors('Ciao, {data} alle {ora}.', ['Ok', str_repeat('x', 26)]));
    }

    public function test_body_is_converted_to_meta_placeholders_in_order(): void
    {
        $body = "Ciao { Nome }, ti aspettiamo {data} alle {ORA}.\n\n\n\nA presto da {locale}!";

        $this->assertSame("Ciao {{1}}, ti aspettiamo {{2}} alle {{3}}.\n\nA presto da {{4}}!", ReminderTemplate::toMeta($body));
        $this->assertSame(['nome', 'data', 'ora', 'locale'], ReminderTemplate::params(ReminderTemplate::normalize($body)));

        $definition = ReminderTemplate::definition('x', 'it', $body, ['Ci sarò', 'Disdico']);
        $this->assertSame([['Luca', 'sabato 12 ottobre', '20:30', 'Trattoria da Mario']], $definition['components'][0]['example']['body_text']);
    }

    public function test_a_new_text_waits_for_approval_while_the_current_one_keeps_working(): void
    {
        [$tenant, $key] = $this->tenant();
        $account = $this->account($tenant, '555');
        $current = $this->template($account, ReminderTemplate::BASE_NAME, 'it', WaTemplate::APPROVED, current: true);

        Http::fake([
            'graph.facebook.com/*/w555/message_templates' => Http::response(['id' => 'm-new', 'status' => 'PENDING', 'category' => 'UTILITY']),
            'trattoria.test/*' => Http::response(['ok' => true]),
        ]);

        $this->withToken($key)->postJson('/api/v1/templates', [
            'language' => 'it',
            'body' => 'Ciao {nome}, a stasera alle {ora} ({data}) da {locale}. Grazie!',
            'buttons' => ['Ci sarò', 'Non vengo'],
        ])->assertOk()->assertJsonPath('template.status', 'PENDING')->assertJsonPath('template.name', 'promemoria_prenotazione_it_2');

        Http::assertSent(fn ($r) => str_ends_with($r->url(), '/w555/message_templates')
            && $r['components'][0]['text'] === 'Ciao {{1}}, a stasera alle {{2}} ({{3}}) da {{4}}. Grazie!'
            && $r['components'][1]['buttons'][1]['text'] === 'Non vengo');

        // Finché Meta non approva, si usa ancora la versione vecchia.
        $this->assertTrue($current->fresh()->current);

        $new = WaTemplate::where('name', 'promemoria_prenotazione_it_2')->first();
        $this->assertSame(['nome', 'ora', 'data', 'locale'], $new->params);

        // Approvata dal webhook: prende il posto della vecchia e il gestionale lo sa.
        $this->templateWebhook('w555', ['event' => 'APPROVED', 'message_template_id' => 'm-new', 'message_template_name' => $new->name, 'message_template_language' => 'it'])
            ->assertOk();

        $this->assertTrue($new->fresh()->current);
        $this->assertFalse($current->fresh()->current);
        $this->assertTrue(WaDelivery::where('endpoint', 'templates')->exists());
    }

    public function test_rejection_keeps_the_old_text_and_explains_why(): void
    {
        [$tenant] = $this->tenant();
        $account = $this->account($tenant, '555');
        $current = $this->template($account, ReminderTemplate::BASE_NAME, 'it', WaTemplate::APPROVED, current: true);
        $proposal = $this->template($account, 'promemoria_prenotazione_it_2', 'it', WaTemplate::PENDING, metaId: 'm2');

        Http::fake(['trattoria.test/*' => Http::response(['ok' => true])]);

        $this->templateWebhook('w555', ['event' => 'REJECTED', 'message_template_id' => 'm2', 'reason' => 'INCORRECT_CATEGORY'])->assertOk();

        $this->assertSame('REJECTED', $proposal->fresh()->status);
        $this->assertStringContainsString('pubblicitario', $proposal->fresh()->reason);
        $this->assertTrue($current->fresh()->current);
    }

    public function test_invalid_text_is_refused_before_reaching_meta(): void
    {
        [$tenant, $key] = $this->tenant();
        $this->account($tenant, '555');
        Http::fake();

        $this->withToken($key)->postJson('/api/v1/templates', [
            'language' => 'it',
            'body' => '{nome} ci vediamo domani',
            'buttons' => ['Sì', 'No'],
        ])->assertStatus(422)->assertJsonPath('ok', false);

        Http::assertNothingSent();
    }

    public function test_sync_imports_templates_created_before_and_picks_the_approved_one(): void
    {
        [$tenant] = $this->tenant();
        $account = $this->account($tenant, '555');

        Http::fake([
            'graph.facebook.com/*/w555/message_templates?*' => Http::response(['data' => [[
                'id' => 'old-it',
                'name' => 'promemoria_prenotazione',
                'language' => 'it',
                'status' => 'APPROVED',
                'category' => 'UTILITY',
                'components' => [
                    ['type' => 'BODY', 'text' => 'Ciao {{1}}, prenotazione da {{2}} per {{3}} alle {{4}}, {{5}} persone.'],
                    ['type' => 'BUTTONS', 'buttons' => [['type' => 'QUICK_REPLY', 'text' => 'Confermo'], ['type' => 'QUICK_REPLY', 'text' => 'Devo disdire']]],
                ],
            ]]]),
            'trattoria.test/*' => Http::response(['ok' => true]),
        ]);

        $this->artisan('wa:sync-templates', ['--all' => true])->assertSuccessful();

        $template = $account->templates()->first();
        $this->assertTrue($template->current);
        $this->assertSame('Ciao {nome}, prenotazione da {locale} per {data} alle {ora}, {persone} persone.', $template->body);
        Http::assertSent(fn ($r) => $r->url() === 'https://trattoria.test/api/private/wa/templates' && $r['templates'][0]['current'] === true);
    }

    private function template(WaAccount $account, string $name, string $language, string $status, bool $current = false, ?string $metaId = null): WaTemplate
    {
        $text = ReminderTemplate::DEFAULTS[$language];

        return WaTemplate::create([
            'wa_account_id' => $account->id,
            'name' => $name,
            'language' => $language,
            'meta_id' => $metaId,
            'status' => $status,
            'body' => $text['body'],
            'params' => ReminderTemplate::params($text['body']),
            'buttons' => $text['buttons'],
            'current' => $current,
        ]);
    }

    private function templateWebhook(string $wabaId, array $value)
    {
        return $this->signedWebhook(['entry' => [['id' => $wabaId, 'changes' => [[
            'field' => 'message_template_status_update',
            'value' => $value,
        ]]]]]);
    }
}
