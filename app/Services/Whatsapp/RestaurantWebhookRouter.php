<?php

namespace App\Services\Whatsapp;

use App\Models\Source;
use App\Models\WaAccount;
use App\Services\WaFailureAlertService;
use Illuminate\Support\Facades\Log;

/**
 * Separa, dentro un webhook di Meta, gli eventi dei numeri dei ristoranti da
 * quelli del numero di piattaforma.
 *
 * Gli eventi di un ristorante (risposte al promemoria, messaggi dei clienti,
 * ricevute di consegna) vanno al suo template-pro-be. Quello che resta segue
 * il flusso di sempre di WaController.
 */
class RestaurantWebhookRouter
{
    public function __construct(private TenantApi $tenants)
    {
    }

    /**
     * @return array|null il payload senza gli eventi già smistati, null se non resta nulla
     */
    public function dispatch(array $payload): ?array
    {
        $entries = $payload['entry'] ?? null;

        if (! is_array($entries)) {
            return $payload;
        }

        $phoneIds = collect($entries)
            ->flatMap(fn ($entry) => $entry['changes'] ?? [])
            ->map(fn ($change) => data_get($change, 'value.metadata.phone_number_id'))
            ->filter()
            ->unique()
            ->values();

        if ($phoneIds->isEmpty()) {
            return $payload;
        }

        $accounts = WaAccount::whereIn('phone_number_id', $phoneIds->all())->get()->keyBy('phone_number_id');

        if ($accounts->isEmpty()) {
            return $payload;
        }

        $remaining = [];

        foreach ($entries as $entry) {
            $legacyChanges = [];

            foreach ($entry['changes'] ?? [] as $change) {
                $account = $accounts->get((string) data_get($change, 'value.metadata.phone_number_id'));

                if ($account) {
                    $this->forward($account, (array) ($change['value'] ?? []));
                } else {
                    $legacyChanges[] = $change;
                }
            }

            if ($legacyChanges !== []) {
                $remaining[] = ['changes' => $legacyChanges] + $entry;
            }
        }

        return $remaining === [] ? null : ['entry' => $remaining] + $payload;
    }

    private function forward(WaAccount $account, array $value): void
    {
        $source = Source::find($account->source_id);

        try {
            if (! $source) {
                throw new \RuntimeException("Nessun ristorante (source {$account->source_id}) per il numero {$account->phone_number_id}.");
            }

            $response = $this->tenants->post($source, 'inbound', ['value' => $value]);

            if (! $response->successful()) {
                throw new \RuntimeException('Il ristorante ha risposto HTTP ' . $response->status() . ': ' . mb_substr($response->body(), 0, 300));
            }
        } catch (\Throwable $e) {
            Log::error('(WA Router) Evento del numero del ristorante non consegnato', [
                'phone_number_id' => $account->phone_number_id,
                'source_id' => $account->source_id,
                'error' => $e->getMessage(),
            ]);

            // Messaggi dei clienti persi = disdette che il ristoratore non vede:
            // si avvisa come per le altre consegne fallite. Le sole ricevute no.
            if (! empty($value['messages'])) {
                app(WaFailureAlertService::class)->notify('wa_webhook', [
                    'source' => $source,
                    'error_type' => 'wa_restaurant_forward_failed',
                    'message' => $e->getMessage(),
                    'resource' => [
                        'phone_number_id' => $account->phone_number_id,
                        'message_ids' => collect($value['messages'])->pluck('id')->all(),
                    ],
                ], $e);
            }
        }
    }
}
