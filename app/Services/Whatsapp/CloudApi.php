<?php

namespace App\Services\Whatsapp;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Chiamata POST /{phone_number_id}/messages. Non sa nulla di ristoranti o
 * finestre: manda e dice com'è andata.
 */
class CloudApi
{
    /**
     * Errori con cui Meta dice "qui serve un template": testo libero fuori
     * dalla finestra di 24 ore.
     */
    public const WINDOW_ERRORS = [131047, 131026, 470];

    /**
     * @return array{ok: bool, wa_message_id: ?string, error: ?string, code: ?int}
     */
    public function send(string $phoneNumberId, string $token, array $payload): array
    {
        $payload = ['messaging_product' => 'whatsapp', 'recipient_type' => 'individual'] + $payload;

        try {
            $response = Http::withToken($token)
                ->acceptJson()
                ->timeout(15)
                ->post('https://graph.facebook.com/' . config('whatsapp.graph_version') . '/' . $phoneNumberId . '/messages', $payload);
        } catch (\Throwable $e) {
            Log::error('(WA CloudApi) Richiesta non partita: ' . $e->getMessage());

            return ['ok' => false, 'wa_message_id' => null, 'error' => $e->getMessage(), 'code' => null];
        }

        $json = (array) $response->json();
        $waMessageId = data_get($json, 'messages.0.id');

        if ($response->successful() && $waMessageId) {
            return ['ok' => true, 'wa_message_id' => (string) $waMessageId, 'error' => null, 'code' => null];
        }

        $code = data_get($json, 'error.code');
        $message = data_get($json, 'error.error_data.details') ?? data_get($json, 'error.message') ?? 'HTTP ' . $response->status();

        Log::warning('(WA CloudApi) Invio rifiutato', [
            'phone_number_id' => $phoneNumberId,
            'type' => $payload['type'] ?? null,
            'code' => $code,
            'error' => $message,
        ]);

        return [
            'ok' => false,
            'wa_message_id' => null,
            'error' => trim(($code !== null ? "[{$code}] " : '') . $message),
            'code' => $code !== null ? (int) $code : null,
        ];
    }
}
