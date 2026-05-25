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
            // Usa un mailer separato con la config SMTP di A1MS (non quella del ristorante)
            config()->set('mail.mailers.a1ms_alert', [
                'transport'  => 'smtp',
                'host'       => env('MAIL_HOST', 'localhost'),
                'port'       => (int) env('MAIL_PORT', 587),
                'encryption' => env('MAIL_ENCRYPTION', 'tls'),
                'username'   => env('MAIL_USERNAME'),
                'password'   => env('MAIL_PASSWORD'),
            ]);

            Mail::mailer('a1ms_alert')
                ->to(self::RECIPIENT)
                ->send(new WaFailureAlertMail($alert));
        } catch (Throwable $mailException) {
            Log::error('(WaFailureAlertService) Invio mail di allerta fallito', [
                'flow'           => $flow,
                'original_error' => $alert['error']['message'],
                'mail_error'     => $mailException->getMessage(),
            ]);
        }
    }

    private function flowLabel(string $flow): string
    {
        return match ($flow) {
            'wa_order'       => 'WA Ordine',
            'wa_reservation' => 'WA Prenotazione',
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
