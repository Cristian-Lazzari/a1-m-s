<?php

namespace App\Console\Commands;

use App\Models\Tenant;
use Illuminate\Console\Command;

class TenantRotateKey extends Command
{
    protected $signature = 'tenant:rotate-key {tenant : ID del ristorante} {--private-token : Aggiorna anche il token privato del gestionale}';

    protected $description = 'Genera una nuova chiave API per un ristorante (la vecchia smette subito di funzionare)';

    public function handle(): int
    {
        $tenant = Tenant::find($this->argument('tenant'));

        if (! $tenant) {
            $this->error('Ristorante non trovato.');

            return self::FAILURE;
        }

        if (! $this->confirm("Nuova chiave per \"{$tenant->name}\": la vecchia smette subito di funzionare. Procedo?")) {
            return self::SUCCESS;
        }

        [$apiKey, $hash] = Tenant::generateApiKey();
        $tenant->api_key_hash = $hash;

        if ($this->option('private-token')) {
            $privateToken = (string) $this->secret('Nuovo PRIVATE_REPORT_TOKEN del gestionale');

            if (strlen($privateToken) < 32) {
                $this->error('Il token privato deve avere almeno 32 caratteri.');

                return self::FAILURE;
            }

            $tenant->private_token = $privateToken;
        }

        $tenant->save();

        $this->line('WA_HUB_API_KEY=' . $apiKey);

        return self::SUCCESS;
    }
}
