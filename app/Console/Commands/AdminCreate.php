<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;

class AdminCreate extends Command
{
    protected $signature = 'admin:create {email}';

    protected $description = 'Crea (o reimposta) un utente che può accedere a /admin';

    public function handle(): int
    {
        $email = (string) $this->argument('email');
        $password = (string) $this->secret('Password (almeno 12 caratteri)');

        if (strlen($password) < 12) {
            $this->error('Password troppo corta.');

            return self::FAILURE;
        }

        User::updateOrCreate(
            ['email' => $email],
            ['name' => $email, 'password' => Hash::make($password), 'email_verified_at' => now()],
        );

        $this->info("Utente {$email} pronto.");

        return self::SUCCESS;
    }
}
