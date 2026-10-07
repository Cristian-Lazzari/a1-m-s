<?php

namespace App\Services\Whatsapp;

use App\Models\Tenant;
use App\Models\WaMessage;
use InvalidArgumentException;

/**
 * Invia per conto di un ristorante, dal numero di piattaforma o dal suo.
 *
 * Il ristorante passa il messaggio "libero" (testo o pulsanti) e, se vuole,
 * un template di riserva: l'hub sceglie in base alla finestra di 24 ore e,
 * se Meta rifiuta il libero perché la finestra è chiusa, ripiega da solo.
 */
class MessageSender
{
    public function __construct(private CloudApi $api, private ServiceWindow $window)
    {
    }

    /**
     * @param  array  $message   oggetto messaggio Meta senza "to" (es. ['type' => 'text', 'text' => [...]])
     * @param  ?array $fallback  oggetto template Meta (['name' => ..., 'language' => ..., 'components' => ...])
     * @return array{ok: bool, wa_message_id: ?string, used: ?string, error: ?string}
     */
    public function send(
        Tenant $tenant,
        string $channel,
        string $to,
        ?array $message,
        ?array $fallback = null,
        ?string $ref = null,
        ?string $contextMessageId = null,
    ): array {
        [$phoneNumberId, $token] = $this->credentials($tenant, $channel);
        $to = ServiceWindow::digits($to);

        if ($message === null && $fallback === null) {
            throw new InvalidArgumentException('Serve almeno un messaggio o un template.');
        }

        $attempts = [];
        $windowOpen = $this->window->isOpen($phoneNumberId, $to);

        if ($message !== null && ($windowOpen || $fallback === null)) {
            $attempts[] = ['used' => 'message', 'payload' => $message];
        }

        if ($fallback !== null) {
            $attempts[] = ['used' => 'fallback', 'payload' => ['type' => 'template', 'template' => $fallback]];
        }

        $result = ['ok' => false, 'wa_message_id' => null, 'used' => null, 'error' => 'nothing_sent'];

        foreach ($attempts as $attempt) {
            $payload = ['to' => $to] + $attempt['payload'];

            if ($contextMessageId) {
                $payload['context'] = ['message_id' => $contextMessageId];
            }

            $sent = $this->api->send($phoneNumberId, $token, $payload);
            $result = ['ok' => $sent['ok'], 'wa_message_id' => $sent['wa_message_id'], 'used' => $attempt['used'], 'error' => $sent['error']];

            if ($sent['ok']) {
                break;
            }

            if (in_array($sent['code'], CloudApi::WINDOW_ERRORS, true)) {
                $this->window->close($phoneNumberId, $to);
            }
        }

        WaMessage::create([
            'tenant_id' => $tenant->id,
            'channel' => $channel,
            'phone_number_id' => $phoneNumberId,
            'recipient' => $to,
            'wa_message_id' => $result['wa_message_id'],
            'type' => $result['used'] === 'fallback' ? 'template' : (string) ($message['type'] ?? 'template'),
            'ref' => $ref,
            'status' => $result['ok'] ? 'sent' : 'failed',
            'error' => $result['ok'] ? null : $result['error'],
        ]);

        return $result;
    }

    /** @return array{0: string, 1: string} */
    private function credentials(Tenant $tenant, string $channel): array
    {
        if ($channel === WaMessage::CHANNEL_PLATFORM) {
            $phoneNumberId = config('whatsapp.platform.phone_number_id');
            $token = config('whatsapp.platform.token');

            if (! $phoneNumberId || ! $token) {
                throw new MessageSenderException('platform_not_configured');
            }

            return [(string) $phoneNumberId, (string) $token];
        }

        if ($channel === WaMessage::CHANNEL_RESTAURANT) {
            $account = $tenant->activeAccount();

            if (! $account) {
                throw new MessageSenderException('restaurant_number_not_connected');
            }

            return [$account->phone_number_id, $account->token];
        }

        throw new InvalidArgumentException("Canale sconosciuto: {$channel}");
    }
}
