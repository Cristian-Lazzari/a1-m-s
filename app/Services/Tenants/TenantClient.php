<?php

namespace App\Services\Tenants;

use App\Models\Tenant;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

/**
 * Chiamate alle /api/private/wa/* di un ristorante, col suo token privato.
 */
class TenantClient
{
    public function post(Tenant $tenant, string $endpoint, array $data): Response
    {
        return Http::withToken($tenant->private_token)
            ->acceptJson()
            ->timeout(20)
            ->post(rtrim($tenant->app_url, '/') . '/api/private/wa/' . ltrim($endpoint, '/'), $data);
    }
}
