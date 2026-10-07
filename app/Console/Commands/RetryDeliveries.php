<?php

namespace App\Console\Commands;

use App\Services\Tenants\DeliveryService;
use Illuminate\Console\Command;

class RetryDeliveries extends Command
{
    protected $signature = 'wa:retry-deliveries {--limit=50}';

    protected $description = 'Riprova gli eventi WhatsApp che un ristorante non ha ancora ricevuto';

    public function handle(DeliveryService $deliveries): int
    {
        $count = $deliveries->retryDue(max(1, (int) $this->option('limit')));

        if ($count > 0) {
            $this->info("Ritentate {$count} consegne.");
        }

        return self::SUCCESS;
    }
}
