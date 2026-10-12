<?php

namespace App\Console\Commands;

use App\Models\WaAccount;
use App\Models\WaTemplate;
use App\Services\Whatsapp\TemplateManager;
use Illuminate\Console\Command;

/**
 * Rete di sicurezza per gli stati dei template: se il webhook di Meta non
 * arriva, l'approvazione si scopre comunque qui. Di norma si guardano solo i
 * numeri con versioni in attesa; --all li controlla tutti (e importa i template
 * creati prima che l'hub li tenesse in tabella).
 */
class SyncTemplates extends Command
{
    protected $signature = 'wa:sync-templates {--all : controlla tutti i numeri, non solo quelli in attesa}';

    protected $description = 'Allinea lo stato dei template del promemoria con Meta';

    public function handle(TemplateManager $templates): int
    {
        $accounts = WaAccount::query()
            ->where('status', WaAccount::STATUS_ACTIVE)
            ->when(! $this->option('all'), fn ($q) => $q->whereHas('templates', fn ($t) => $t->where('status', WaTemplate::PENDING)))
            ->get();

        foreach ($accounts as $account) {
            try {
                $templates->sync($account);
                $this->line("ok  {$account->display_phone} ({$account->templates()->count()} versioni)");
            } catch (\Throwable $e) {
                $this->error("err {$account->display_phone}: {$e->getMessage()}");
            }
        }

        return self::SUCCESS;
    }
}
