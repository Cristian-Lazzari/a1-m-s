<?php

namespace Tests\Feature;

use App\Http\Controllers\Webhooks\WaController;
use App\Mail\confermaOrdineAdmin;
use App\Mail\WaFailureAlertMail;
use App\Models\Source;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/**
 * Dopo che il ristoratore ha premuto Conferma o Annulla su WhatsApp lo stato è
 * già cambiato: da lì in poi la mail al cliente è l'unica risposta che arriva.
 * Se non parte, deve partire almeno l'avviso a info@future-plus.it.
 */
class WaCustomerMailTest extends TestCase
{
    public function test_manda_la_mail_al_cliente(): void
    {
        Mail::fake();

        $this->send($this->source(), 'cliente@example.test');

        Mail::assertSent(confermaOrdineAdmin::class, fn ($mail) => $mail->hasTo('cliente@example.test'));
        Mail::assertNotSent(WaFailureAlertMail::class);
    }

    public function test_destinatario_non_valido_fa_scattare_l_avviso(): void
    {
        Mail::fake();

        $this->send($this->source(), '');

        Mail::assertNotSent(confermaOrdineAdmin::class);
        Mail::assertSent(WaFailureAlertMail::class, fn ($mail) => $mail->hasTo('info@future-plus.it'));
    }

    public function test_credenziali_smtp_incomplete_fanno_scattare_l_avviso(): void
    {
        Mail::fake();

        // Prima si arrivava fino a Symfony, che rispondeva con un TypeError su
        // host null: nel log non si capiva di che ristorante si trattasse.
        $this->send($this->source(['host' => '']), 'cliente@example.test');

        Mail::assertNotSent(confermaOrdineAdmin::class);
        Mail::assertSent(WaFailureAlertMail::class);
    }

    private function send(Source $source, string $recipient): void
    {
        $controller = new class extends WaController {
            public function sendForTest(array $bodymail, $source, string $lang, $recipient): void
            {
                $this->sendCustomerMail($bodymail, $source, $lang, $recipient, 'wa_reservation', ['res_id' => 7], null, null);
            }
        };

        $controller->sendForTest($this->bodymail(), $source, 'it', $recipient);
    }

    private function source(array $override = []): Source
    {
        $source = new Source();
        $source->forceFill(array_merge([
            'id' => 1,
            'db_name' => 'tenant_demo',
            'host' => 'mail.ristorante.test',
            'username' => 'info@ristorante.test',
            'token' => 'segreta',
            'app_url' => 'https://ristorante.test',
            'app_domain' => 'https://ristorante.test',
            'app_name' => 'Ristorante Demo',
        ], $override));

        return $source;
    }

    private function bodymail(): array
    {
        return [
            'type' => 'res',
            'to' => 'user',
            'lang' => 'it',
            'title' => 'Prenotazione confermata',
            'subtitle' => '',
            'res_id' => 7,
            'name' => 'Mario',
            'surname' => 'Rossi',
            'email' => 'cliente@example.test',
            'date_slot' => '21/08/2026 20:30',
            'message' => '',
            'sala' => 0,
            'phone' => '3331112233',
            'admin_phone' => '0102030405',
            'app_domain' => 'https://ristorante.test',
            'app_url' => 'https://ristorante.test',
            'app_name' => 'Ristorante Demo',
            'whatsapp_message_id' => 'wamid.res.1',
            'n_person' => 2,
            'status' => 1,
            'property_adv' => [],
        ];
    }
}
