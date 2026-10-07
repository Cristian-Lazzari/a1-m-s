<?php

namespace App\Services\Whatsapp;

use App\Models\Tenant;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

/**
 * Link monouso alla pagina di collegamento: lo chiede il gestionale via API
 * (autenticato), quindi chi lo apre è per forza passato da lì.
 */
class OnboardingLinks
{
    public const TTL_MINUTES = 30;

    public function create(Tenant $tenant, ?string $returnUrl): string
    {
        $token = Str::random(48);

        Cache::put($this->key($token), [
            'tenant_id' => $tenant->id,
            'return' => $this->safeReturnUrl($returnUrl, $tenant),
        ], now()->addMinutes(self::TTL_MINUTES));

        return route('whatsapp.connect', ['token' => $token]);
    }

    /** Il link si usa una volta: aperto, sparisce. @return array{tenant_id:int, return:?string}|null */
    public function consume(string $token): ?array
    {
        return Cache::pull($this->key($token));
    }

    private function key(string $token): string
    {
        return 'wa_onboarding_link:' . hash('sha256', $token);
    }

    /** Si torna solo al gestionale di quel ristorante, mai a un indirizzo qualsiasi. */
    private function safeReturnUrl(?string $url, Tenant $tenant): ?string
    {
        $host = parse_url((string) $url, PHP_URL_HOST);
        $expected = parse_url($tenant->app_url, PHP_URL_HOST);

        return $host && $expected && strcasecmp($host, $expected) === 0 && str_starts_with((string) $url, 'https://')
            ? $url
            : null;
    }
}
