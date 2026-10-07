<?php

namespace App\Console\Commands;

use App\Models\Tenant;
use Illuminate\Console\Command;

/**
 * Registra un ristorante sull'hub.
 *
 * I segreti non si passano come argomenti (finirebbero nella cronologia della
 * shell): il token privato del ristorante si incolla quando lo chiede, la
 * chiave API dell'hub si vede una volta sola e va messa nel .env del gestionale.
 */
class TenantCreate extends Command
{
    protected $signature = 'tenant:create {name : Nome del locale} {app_url : Indirizzo del gestionale, es. https://dashboard.locale.it}';

    protected $description = 'Registra un ristorante e genera la sua chiave API per l\'hub';

    public function handle(): int
    {
        $appUrl = rtrim((string) $this->argument('app_url'), '/');

        if (! str_starts_with($appUrl, 'https://') && ! app()->environment('local', 'testing')) {
            $this->error('L\'indirizzo del gestionale deve essere https://');

            return self::FAILURE;
        }

        $privateToken = (string) $this->secret('PRIVATE_REPORT_TOKEN del gestionale (non viene mostrato mentre scrivi)');

        if (strlen($privateToken) < 32) {
            $this->error('Il token privato deve avere almeno 32 caratteri: generane uno con  php -r "echo bin2hex(random_bytes(32));"');

            return self::FAILURE;
        }

        [$apiKey, $hash] = Tenant::generateApiKey();

        $tenant = Tenant::create([
            'name' => (string) $this->argument('name'),
            'app_url' => $appUrl,
            'api_key_hash' => $hash,
            'private_token' => $privateToken,
        ]);

        $this->info("Ristorante #{$tenant->id} registrato.");
        $this->line('');
        $this->line('Metti queste righe nel .env del gestionale (la chiave non sarà più visibile):');
        $this->line('');
        $this->line('WA_HUB_URL=' . rtrim((string) config('app.url'), '/'));
        $this->line('WA_HUB_API_KEY=' . $apiKey);
        $this->line('');

        return self::SUCCESS;
    }
}
