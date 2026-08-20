<?php

namespace Tests\Feature;

use App\Mail\WaFailureAlertMail;
use App\Services\WaFailureAlertService;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/**
 * L'avviso a info@future-plus.it è l'unica cosa che segnala che un cliente è
 * rimasto senza mail. Deve partire anche quando è proprio la casella del
 * ristorante a non funzionare, quindi non può condividere il mailer con lei.
 */
class WaFailureAlertServiceTest extends TestCase
{
    public function test_avviso_parte_anche_dopo_che_il_ristorante_ha_riconfigurato_il_suo_mailer(): void
    {
        $this->applyTenantMailerConfig();

        Mail::fake();

        app(WaFailureAlertService::class)->notify('wa_reservation', [
            'message' => 'Invio della mail al cliente fallito',
            'resource' => ['res_id' => 7],
        ], new \RuntimeException('Connection could not be established'));

        Mail::assertSent(WaFailureAlertMail::class);
    }

    public function test_le_credenziali_del_ristorante_non_toccano_il_mailer_di_sistema(): void
    {
        $systemHost = config('mail.mailers.alert_smtp.host');

        $this->applyTenantMailerConfig();

        $this->assertSame($systemHost, config('mail.mailers.alert_smtp.host'));
        $this->assertNotEmpty(config('mail.mailers.alert_smtp.host'));

        // Si risolve senza eccezioni: con host nullo Symfony lancerebbe un
        // TypeError su Dsn::__construct() e l'avviso resterebbe nel log.
        $this->assertNotNull(Mail::mailer('alert')->getSymfonyTransport());
    }

    /** Riproduce la riconfigurazione che WaController applica a ogni webhook. */
    private function applyTenantMailerConfig(): void
    {
        config()->set([
            'mail.mailers.tenant.transport'    => 'smtp',
            'mail.mailers.tenant.host'         => 'mail.ristorante.test',
            'mail.mailers.tenant.port'         => 465,
            'mail.mailers.tenant.username'     => 'info@ristorante.test',
            'mail.mailers.tenant.password'     => 'segreta',
            'mail.mailers.tenant.encryption'   => 'ssl',
            'mail.mailers.tenant.from.address' => 'info@ristorante.test',
            'mail.mailers.tenant.from.name'    => 'Ristorante Demo',
        ]);
        Mail::purge('tenant');
    }
}
