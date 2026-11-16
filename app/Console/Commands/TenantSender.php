<?php

namespace App\Console\Commands;

use App\Models\Tenant;
use App\Models\WaSender;
use Illuminate\Console\Command;

/**
 * Sceglie da quale numero di piattaforma un ristorante avvisa il ristoratore:
 * quello di un partner (white label) o, con --clear, quello di Future Plus.
 */
class TenantSender extends Command
{
    protected $signature = 'tenant:sender
        {tenant : ID del ristorante}
        {sender? : ID del numero del partner (sender:create)}
        {--clear : Torna al numero di piattaforma di Future Plus}';

    protected $description = 'Collega un ristorante al numero WhatsApp del suo partner';

    public function handle(): int
    {
        $tenant = Tenant::find($this->argument('tenant'));

        if (! $tenant) {
            $this->error('Ristorante non trovato.');

            return self::FAILURE;
        }

        if ($this->option('clear')) {
            $tenant->update(['wa_sender_id' => null]);
            $this->info("{$tenant->name}: avvisi dal numero di piattaforma di Future Plus.");

            return self::SUCCESS;
        }

        $sender = WaSender::find($this->argument('sender'));

        if (! $sender) {
            $this->error('Numero del partner non trovato: indica un ID di sender:create oppure usa --clear.');

            return self::FAILURE;
        }

        $tenant->update(['wa_sender_id' => $sender->id]);
        $this->info("{$tenant->name}: avvisi dal numero di {$sender->name}" . ($sender->display_phone ? " ({$sender->display_phone})" : '') . '.');

        return self::SUCCESS;
    }
}
