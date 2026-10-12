<?php

namespace App\Services\Whatsapp;

use App\Models\Tenant;
use App\Models\WaAccount;
use App\Models\WaMessage;
use App\Models\WaTemplate;
use App\Services\Tenants\DeliveryService;
use App\Services\WaFailureAlertService;
use Illuminate\Support\Facades\Log;

/**
 * Smista un webhook di Meta.
 *
 *  - numero di un ristorante  → tutto il blocco va al suo gestionale (/inbound)
 *  - numero di piattaforma    → la risposta del ristoratore va al gestionale
 *                               che aveva mandato il messaggio citato (/platform-reply)
 *
 * In entrambi i casi l'hub aggiorna la finestra di 24 ore e lo stato dei
 * messaggi che ha mandato. Nessuna logica su prenotazioni o ordini: quella è
 * del ristorante.
 */
class WebhookProcessor
{
    public function __construct(
        private ServiceWindow $window,
        private DeliveryService $deliveries,
        private TemplateManager $templates,
    ) {
    }

    /** @return int[] id delle consegne create, da tentare dopo aver risposto a Meta */
    public function process(array $payload): array
    {
        $deliveryIds = [];

        foreach ((array) ($payload['entry'] ?? []) as $entry) {
            foreach ((array) ($entry['changes'] ?? []) as $change) {
                $field = $change['field'] ?? 'messages';

                if (in_array($field, ['message_template_status_update', 'template_category_update'], true)) {
                    if ($id = $this->templateUpdate((string) ($entry['id'] ?? ''), $field, (array) ($change['value'] ?? []))) {
                        $deliveryIds[] = $id;
                    }

                    continue;
                }

                if ($field !== 'messages') {
                    continue;
                }

                $value = (array) ($change['value'] ?? []);
                $phoneNumberId = (string) data_get($value, 'metadata.phone_number_id', '');

                if ($phoneNumberId === '') {
                    continue;
                }

                foreach ((array) ($value['messages'] ?? []) as $message) {
                    if (! empty($message['from'])) {
                        $this->window->touch($phoneNumberId, (string) $message['from']);
                    }
                }

                $this->trackStatuses($phoneNumberId, (array) ($value['statuses'] ?? []));

                if ($phoneNumberId === (string) config('whatsapp.platform.phone_number_id')) {
                    array_push($deliveryIds, ...$this->platform($value));

                    continue;
                }

                $account = WaAccount::with('tenant')->where('phone_number_id', $phoneNumberId)->first();

                if (! $account || ! $account->tenant) {
                    Log::warning('(WA Webhook) Evento per un numero che non conosciamo', ['phone_number_id' => $phoneNumberId]);

                    continue;
                }

                $deliveryIds[] = $this->deliveries->queue($account->tenant, 'inbound', ['value' => $value])->id;
            }
        }

        return $deliveryIds;
    }

    /**
     * Meta ha approvato, rifiutato o ricategorizzato un template di un
     * ristorante. entry.id è il WABA. Template che non sono nostri: ignorati.
     */
    private function templateUpdate(string $wabaId, string $field, array $value): ?int
    {
        $metaId = (string) ($value['message_template_id'] ?? '');

        $template = WaTemplate::query()
            ->whereHas('account', fn ($q) => $q->where('waba_id', $wabaId))
            ->where(fn ($q) => $q->where('meta_id', $metaId)->orWhere(fn ($q) => $q
                ->where('name', (string) ($value['message_template_name'] ?? ''))
                ->where('language', (string) ($value['message_template_language'] ?? ''))))
            ->first();

        if (! $template || ($field === 'message_template_status_update' && empty($value['event']))) {
            return null;
        }

        if ($metaId !== '' && ! $template->meta_id) {
            $template->update(['meta_id' => $metaId]);
        }

        $delivery = $field === 'template_category_update'
            ? $this->templates->applyStatus($template, $template->status, (string) ($value['new_category'] ?? ''))
            : $this->templates->applyStatus($template, (string) ($value['event'] ?? ''), null, $value['reason'] ?? null);

        return $delivery?->id;
    }

    /** @return int[] */
    private function platform(array $value): array
    {
        $ids = [];
        $names = collect($value['contacts'] ?? [])
            ->mapWithKeys(fn ($contact) => [(string) ($contact['wa_id'] ?? '') => data_get($contact, 'profile.name')]);

        foreach ((array) ($value['messages'] ?? []) as $message) {
            $contextId = data_get($message, 'context.id');
            $sent = $contextId ? WaMessage::with('tenant')->where('wa_message_id', $contextId)->first() : null;

            // Il ristoratore ha scritto senza citare un nostro messaggio: non
            // c'è nulla da fare oltre ad aprire la finestra (fatto sopra).
            if (! $sent || ! $sent->tenant) {
                Log::info('(WA Webhook) Messaggio sul numero di piattaforma senza riferimento', [
                    'from' => $message['from'] ?? null,
                    'context_id' => $contextId,
                ]);

                continue;
            }

            $ids[] = $this->deliveries->queue($sent->tenant, 'platform-reply', [
                'ref' => $sent->ref,
                'replied_to' => $contextId,
                'from' => (string) ($message['from'] ?? ''),
                'name' => $names[(string) ($message['from'] ?? '')] ?? null,
                'message' => $message,
            ])->id;
        }

        return $ids;
    }

    private function trackStatuses(string $phoneNumberId, array $statuses): void
    {
        foreach ($statuses as $status) {
            $waMessageId = $status['id'] ?? null;
            $state = $status['status'] ?? null;

            if (! $waMessageId || ! in_array($state, ['sent', 'delivered', 'read', 'failed'], true)) {
                continue;
            }

            $sent = WaMessage::with('tenant')->where('wa_message_id', $waMessageId)->first();

            if (! $sent) {
                continue;
            }

            if ($state !== 'failed') {
                // Le ricevute possono arrivare in disordine: non si torna indietro.
                $order = ['sent' => 1, 'delivered' => 2, 'read' => 3];

                if (($order[$state] ?? 0) > ($order[$sent->status] ?? 0)) {
                    $sent->update(['status' => $state]);
                }

                continue;
            }

            $errors = (array) ($status['errors'] ?? []);
            $sent->update(['status' => 'failed', 'error' => json_encode($errors)]);

            foreach ($errors as $error) {
                if (in_array((int) data_get($error, 'code'), CloudApi::WINDOW_ERRORS, true)) {
                    $this->window->close($phoneNumberId, (string) ($status['recipient_id'] ?? ''));
                }
            }

            // Le notifiche ai ristoratori sono il cuore del servizio: se non
            // arrivano, lo deve sapere qualcuno.
            if ($sent->channel === WaMessage::CHANNEL_PLATFORM) {
                app(WaFailureAlertService::class)->notify('wa_delivery', [
                    'tenant' => $sent->tenant,
                    'wa_id' => $waMessageId,
                    'error_type' => 'whatsapp_delivery_failed',
                    'message' => collect($errors)->map(fn ($e) => trim('[' . data_get($e, 'code') . '] ' . (data_get($e, 'title') ?? data_get($e, 'message'))))->implode(' | ') ?: 'Consegna fallita',
                    'resource' => ['recipient' => $status['recipient_id'] ?? null, 'ref' => $sent->ref],
                ]);
            }
        }
    }
}
