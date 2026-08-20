<?php

namespace App\Services;

use App\Mail\WaFailureAlertMail;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

class WaFailureAlertService
{
    private const RECIPIENT = 'info@future-plus.it';

    public function notify(string $flow, array $context = [], ?Throwable $exception = null): void
    {
        $source = $context['source'] ?? null;

        $alert = [
            'flow'        => $flow,
            'flow_label'  => $this->flowLabel($flow),
            'reported_at' => Carbon::now()->format('Y-m-d H:i:s'),
            'wa_id'       => $context['wa_id'] ?? null,
            'lang'        => $context['lang'] ?? 'it',
            'restaurant'  => [
                'name'       => $source?->app_name,
                'app_url'    => $source?->app_url,
                'app_domain' => $source?->app_domain,
                'db'         => $source?->db_name ?? null,
                'mail_from'  => $source?->username ?? null,
            ],
            'customer'    => $this->buildCustomerInfo($context),
            'resource'    => $context['resource'] ?? [],
            'error'       => [
                'type'            => $exception ? class_basename($exception) : ($context['error_type'] ?? 'unknown'),
                'message'         => $exception?->getMessage() ?? ($context['message'] ?? 'Errore sconosciuto'),
                'exception_class' => $exception ? get_class($exception) : null,
                'file'            => $exception?->getFile(),
                'line'            => $exception?->getLine(),
            ],
            'trace'       => $exception?->getTraceAsString(),
            'context_json' => json_encode($this->safeContext($context), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PARTIAL_OUTPUT_ON_ERROR),
        ];

        try {
            // L'avviso parte dal mailer 'alert', che nessuno riconfigura a
            // runtime: le credenziali del ristorante finiscono su 'tenant'.
            //
            // Prima qui si riscriveva 'mail.mailers.smtp' leggendo env(): in
            // produzione la config e' in cache, env() a runtime restituisce
            // null e il mailer nasceva senza host. Il risultato era un
            // TypeError su Dsn::__construct() catturato due righe piu' sotto:
            // l'avviso non e' mai partito, e il fallimento originale restava
            // solo nel log che non legge nessuno.
            Mail::mailer($this->alertMailer())
                ->to(self::RECIPIENT)
                ->send(new WaFailureAlertMail($alert));
        } catch (Throwable $mailException) {
            // Quando salta anche l'avviso, il log è l'unica cosa che resta:
            // si scrive come stringa JSON, perché passando un array Monolog
            // lo normalizza e taglia tutto oltre il nono livello.
            Log::critical('(WaFailureAlertService) Invio mail di allerta fallito: ' . json_encode([
                'flow'           => $flow,
                'wa_id'          => $alert['wa_id'] ?? null,
                'restaurant'     => $alert['restaurant'] ?? [],
                'customer'       => $alert['customer'] ?? [],
                'resource'       => $alert['resource'] ?? [],
                'original_error' => $alert['error']['message'],
                'original_file'  => $alert['error']['file'] ?? null,
                'original_line'  => $alert['error']['line'] ?? null,
                'original_trace' => collect(explode("\n", (string) ($alert['trace'] ?? '')))->take(8)->all(),
                'mail_error'     => $mailException->getMessage(),
                'mail_file'      => $mailException->getFile(),
                'mail_line'      => $mailException->getLine(),
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PARTIAL_OUTPUT_ON_ERROR));
        }
    }

    /**
     * Il mailer 'alert' vive in config/mail.php. Se la config in cache sul
     * server è più vecchia del deploy quel mailer non c'è ancora: meglio
     * mandare l'avviso con il mailer di default che non mandarlo affatto.
     */
    private function alertMailer(): ?string
    {
        return config('mail.mailers.alert') ? 'alert' : null;
    }

    private function flowLabel(string $flow): string
    {
        return match ($flow) {
            'wa_order'       => 'WA Ordine',
            'wa_reservation' => 'WA Prenotazione',
            'wa_delivery'    => 'WA Consegna fallita',
            'wa_reply'       => 'WA Risposta non lavorata',
            default          => ucfirst(str_replace('_', ' ', $flow)),
        };
    }

    private function buildCustomerInfo(array $context): array
    {
        $model = $context['order'] ?? $context['reservation'] ?? null;
        if (!$model) {
            return [];
        }

        return array_filter([
            'name'    => $model->name ?? null,
            'surname' => $model->surname ?? null,
            'email'   => $model->email ?? null,
            'phone'   => $model->phone ?? null,
        ], static fn ($v) => $v !== null && $v !== '');
    }

    private function safeContext(array $context): array
    {
        $safe = [];
        foreach ($context as $key => $value) {
            if (in_array($key, ['source', 'order', 'reservation'], true)) {
                $safe[$key] = '[object omitted]';
            } else {
                $safe[$key] = $value;
            }
        }
        return $safe;
    }
}
