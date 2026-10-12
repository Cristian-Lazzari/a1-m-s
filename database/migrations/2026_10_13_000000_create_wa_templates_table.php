<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * I modelli del promemoria di ogni ristorante, con il loro stato su Meta.
 *
 * Un modello approvato non si modifica: un testo nuovo è una versione nuova
 * (nome diverso), e quella in uso resta tale finché Meta non approva la nuova.
 * "current" è la versione da usare per lingua; ce n'è al più una.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wa_templates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('wa_account_id')->constrained('wa_accounts')->cascadeOnDelete();
            $table->string('name', 512);
            $table->string('language', 15);
            $table->string('meta_id', 64)->nullable();
            // PENDING | APPROVED | REJECTED | PAUSED | DISABLED | SUPERSEDED
            $table->string('status', 20)->default('PENDING');
            $table->string('category', 20)->nullable();
            // Testo con le variabili per nome ({nome}, {ora}…): quello che vede e
            // scrive il ristoratore. Il formato di Meta ({{1}}…) si ricava da qui.
            $table->text('body');
            // Ordine delle variabili, cioè cosa va in {{1}}, {{2}}…
            $table->json('params');
            $table->json('buttons');
            $table->string('reason', 500)->nullable();
            $table->boolean('current')->default(false);
            $table->timestamps();

            $table->unique(['wa_account_id', 'name', 'language']);
            $table->index(['wa_account_id', 'language', 'current']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wa_templates');
    }
};
