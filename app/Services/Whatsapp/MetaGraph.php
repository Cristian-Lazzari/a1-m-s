<?php

namespace App\Services\Whatsapp;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

/**
 * Le chiamate server-to-server verso Meta sui numeri dei ristoranti:
 * chiusura dell'Embedded Signup e gestione dei template.
 * Ogni metodo lancia MetaGraphException col messaggio di Meta se qualcosa va storto.
 */
class MetaGraph
{
    private function url(string $path): string
    {
        return 'https://graph.facebook.com/' . config('whatsapp.graph_version') . '/' . ltrim($path, '/');
    }

    /** Il code del popup vale 30 secondi: va scambiato subito col token del ristorante. */
    public function exchangeCode(string $code): string
    {
        $response = Http::acceptJson()->timeout(15)->get($this->url('oauth/access_token'), [
            'client_id' => config('whatsapp.app_id'),
            'client_secret' => config('whatsapp.app_secret'),
            'code' => $code,
        ]);

        $token = data_get($this->ok($response, 'scambio del codice'), 'access_token');

        if (! $token) {
            throw new MetaGraphException('Meta non ha restituito il token del ristorante.');
        }

        return (string) $token;
    }

    /** Senza questa chiamata i webhook del numero del ristorante non arrivano a /webhook/wa. */
    public function subscribeApp(string $wabaId, string $token): void
    {
        $this->ok(Http::withToken($token)->acceptJson()->timeout(15)->post($this->url($wabaId . '/subscribed_apps')), 'iscrizione ai webhook');
    }

    public function registerPhone(string $phoneNumberId, string $pin, string $token): void
    {
        $this->ok(Http::withToken($token)->acceptJson()->timeout(20)->post($this->url($phoneNumberId . '/register'), [
            'messaging_product' => 'whatsapp',
            'pin' => $pin,
        ]), 'registrazione del numero');
    }

    /** @return array{display_phone: ?string, verified_name: ?string} */
    public function phoneInfo(string $phoneNumberId, string $token): array
    {
        $json = $this->ok(Http::withToken($token)->acceptJson()->timeout(15)->get($this->url($phoneNumberId), [
            'fields' => 'display_phone_number,verified_name',
        ]), 'lettura del numero');

        return [
            'display_phone' => $json['display_phone_number'] ?? null,
            'verified_name' => $json['verified_name'] ?? null,
        ];
    }

    /**
     * I template appartengono al WABA del ristorante: vanno creati su ognuno.
     *
     * @return ?array{id: ?string, status: ?string, category: ?string} null se
     *         esiste già un template con questo nome e lingua (ricollegamento)
     */
    public function createTemplate(string $wabaId, array $template, string $token): ?array
    {
        $response = Http::withToken($token)->acceptJson()->timeout(15)
            ->post($this->url($wabaId . '/message_templates'), $template);

        if ($response->successful()) {
            return [
                'id' => isset($response['id']) ? (string) $response['id'] : null,
                'status' => $response['status'] ?? null,
                'category' => $response['category'] ?? null,
            ];
        }

        $message = (string) data_get($response->json(), 'error.error_user_msg', data_get($response->json(), 'error.message', ''));

        // 2388023/2388024: esiste già un template con questo nome e lingua.
        if (in_array((int) data_get($response->json(), 'error.error_subcode'), [2388023, 2388024], true)
            || str_contains(strtolower($message), 'already exists')) {
            return null;
        }

        $this->ok($response, 'creazione del template ' . ($template['name'] ?? '') . ' (' . ($template['language'] ?? '') . ')');

        return null;
    }

    /**
     * I template del WABA il cui nome inizia con $prefix, con stato e testo.
     *
     * @return array<int, array<string, mixed>>
     */
    public function templates(string $wabaId, string $token, string $prefix): array
    {
        $json = $this->ok(Http::withToken($token)->acceptJson()->timeout(20)->get($this->url($wabaId . '/message_templates'), [
            'fields' => 'id,name,language,status,category,components,rejected_reason',
            'name' => $prefix,
            'limit' => 100,
        ]), 'lettura dei template');

        return array_values(array_filter(
            (array) ($json['data'] ?? []),
            fn ($template) => str_starts_with((string) ($template['name'] ?? ''), $prefix),
        ));
    }

    /** Un template ancora in attesa che si sostituisce: si toglie da Meta, se c'è. */
    public function deleteTemplate(string $wabaId, string $name, ?string $metaId, string $token): void
    {
        $query = array_filter(['name' => $name, 'hsm_id' => $metaId]);

        $this->ok(Http::withToken($token)->acceptJson()->timeout(15)
            ->delete($this->url($wabaId . '/message_templates') . '?' . http_build_query($query)), 'eliminazione del template ' . $name);
    }

    private function ok(Response $response, string $step): array
    {
        if ($response->successful()) {
            return (array) $response->json();
        }

        $message = data_get($response->json(), 'error.error_user_msg')
            ?? data_get($response->json(), 'error.message')
            ?? 'HTTP ' . $response->status();

        throw new MetaGraphException("Errore Meta durante {$step}: {$message}");
    }
}
