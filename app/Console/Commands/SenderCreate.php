<?php

namespace App\Console\Commands;

use App\Models\WaSender;
use Illuminate\Console\Command;

/**
 * Registra il numero WhatsApp Business di un partner (white label), già
 * collegato alla nostra app Meta. Il token si incolla quando lo chiede, così
 * non finisce nella cronologia della shell.
 *
 * Sul WABA del partner devono esistere, approvati, gli stessi template del
 * numero di piattaforma (full_emoji, response_link, messaggio_cliente).
 */
class SenderCreate extends Command
{
    protected $signature = 'sender:create
        {name : Nome del partner, es. "Cassa Online"}
        {phone_number_id : ID del numero su Meta}
        {--waba= : ID del WABA del partner}
        {--display= : Numero come lo vede il ristoratore, es. "+39 02 1234567"}';

    protected $description = 'Registra il numero di piattaforma di un partner white label';

    public function handle(): int
    {
        $phoneNumberId = (string) $this->argument('phone_number_id');

        if (WaSender::where('phone_number_id', $phoneNumberId)->exists()
            || $phoneNumberId === (string) config('whatsapp.platform.phone_number_id')) {
            $this->error('Questo numero è già registrato come numero di piattaforma.');

            return self::FAILURE;
        }

        $token = (string) $this->secret('Token del WABA del partner (non viene mostrato mentre scrivi)');

        if (strlen($token) < 20) {
            $this->error('Token mancante o troppo corto.');

            return self::FAILURE;
        }

        $sender = WaSender::create([
            'name' => (string) $this->argument('name'),
            'waba_id' => $this->option('waba'),
            'phone_number_id' => $phoneNumberId,
            'display_phone' => $this->option('display'),
            'token' => $token,
        ]);

        $this->info("Numero del partner #{$sender->id} ({$sender->name}) registrato.");
        $this->line("Per usarlo su un ristorante:  php artisan tenant:sender <id ristorante> {$sender->id}");

        return self::SUCCESS;
    }
}
