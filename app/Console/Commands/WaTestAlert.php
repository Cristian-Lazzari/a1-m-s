<?php

namespace App\Console\Commands;

use App\Services\WaFailureAlertService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;
use Throwable;

/**
 * Prova il canale di allerta senza aspettare un guasto vero.
 *
 * Serve a distinguere due situazioni che da fuori si somigliano: "non è mai
 * successo niente da segnalare" e "succede ma la mail di allerta non parte".
 * Senza questa prova si resta a indovinare.
 */
class WaTestAlert extends Command
{
    protected $signature = 'wa:test-alert';

    protected $description = 'Manda una mail di prova al destinatario degli avvisi e mostra la configurazione SMTP usata';

    public function handle(): int
    {
        $this->line('');
        $this->info('Configurazione SMTP che verrà usata per gli avvisi:');
        $this->table(['Voce', 'Valore'], [
            ['host', env('MAIL_HOST') ?: '(vuoto)'],
            ['port', env('MAIL_PORT', 465)],
            ['encryption', env('MAIL_ENCRYPTION', 'ssl')],
            ['username', env('MAIL_USERNAME') ?: '(vuoto)'],
            ['password', env('MAIL_PASSWORD') ? '(impostata, ' . strlen((string) env('MAIL_PASSWORD')) . ' caratteri)' : '(VUOTA)'],
            ['from', config('mail.from.address') ?: env('MAIL_FROM_ADDRESS') ?: '(vuoto)'],
            ['destinatario', 'info@future-plus.it'],
        ]);

        if (!env('MAIL_HOST') || !env('MAIL_USERNAME') || !env('MAIL_PASSWORD')) {
            $this->error('Mancano dei parametri SMTP nel file .env: nessun avviso potrà mai partire.');

            return self::FAILURE;
        }

        $this->line('');
        $this->info('Invio in corso...');

        // Prima il percorso reale, quello che usano gli avvisi veri.
        app(WaFailureAlertService::class)->notify('wa_test', [
            'error_type' => 'test',
            'message' => 'Messaggio di prova richiesto da wa:test-alert. Se lo stai leggendo, il canale di allerta funziona.',
            'resource' => ['origine' => 'comando manuale'],
        ]);

        // Poi lo stesso invio senza rete di protezione, per vedere l'errore
        // vero: il servizio lo cattura e lo scrive solo nel log.
        try {
            Mail::purge('smtp');

            Mail::mailer('smtp')
                ->to('info@future-plus.it')
                ->send(new \App\Mail\WaFailureAlertMail([
                    'flow' => 'wa_test',
                    'flow_label' => 'Prova canale di allerta',
                    'reported_at' => now()->format('Y-m-d H:i:s'),
                    'restaurant' => ['name' => 'A1MS'],
                    'error' => ['type' => 'test', 'message' => 'Prova diretta del canale SMTP.'],
                ]));

            $this->line('');
            $this->info('Il server SMTP ha accettato il messaggio.');
            $this->line('Se su info@future-plus.it non arriva nulla, il problema è a valle:');
            $this->line('casella piena, filtro antispam, o dominio mittente non autorizzato.');

            return self::SUCCESS;
        } catch (Throwable $e) {
            $this->line('');
            $this->error('Invio rifiutato: ' . $e->getMessage());
            $this->line('File: ' . $e->getFile() . ':' . $e->getLine());
            $this->line('');
            $this->line('È questo il motivo per cui non ricevi gli avvisi.');

            return self::FAILURE;
        }
    }
}
