<?php

namespace App\Services\Whatsapp;

use App\Models\Source;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

/**
 * Chiamate alle /api/private/wa/* di un ristorante (template-pro-be), col suo
 * PRIVATE_REPORT_TOKEN. La logica su prenotazioni e chat vive lì: qui si
 * smista e basta.
 */
class TenantApi
{
    public function post(Source $source, string $path, array $data): Response
    {
        if (! filled($source->private_token) || ! filled($source->app_url)) {
            throw new \RuntimeException("Il ristorante {$source->db_name} non ha app_url o token privato in sources.");
        }

        return Http::withToken($source->private_token)
            ->acceptJson()
            ->timeout(25)
            ->post(rtrim($source->app_url, '/') . '/api/private/wa/' . ltrim($path, '/'), $data);
    }
}
