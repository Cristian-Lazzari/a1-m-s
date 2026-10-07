<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Numeri WhatsApp dei ristoranti collegati con l'Embedded Signup.
 *
 * Tutti i webhook arrivano a /webhook/wa: il phone_number_id dice se il
 * messaggio è per il numero di piattaforma (flusso di sempre) o per il numero
 * di un ristorante, e in quel caso di quale.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wa_accounts', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('source_id')->index();
            $table->string('waba_id', 64);
            $table->string('phone_number_id', 64)->unique();
            $table->string('business_id', 64)->nullable();
            $table->string('display_phone', 32)->nullable();
            $table->string('verified_name')->nullable();
            // Token del ristorante e PIN di registrazione, cifrati con l'APP_KEY.
            $table->text('token');
            $table->text('pin')->nullable();
            // pending = collegamento a metà | active | error
            $table->string('status', 20)->default('pending');
            $table->text('last_error')->nullable();
            $table->timestamps();
        });

        Schema::table('sources', function (Blueprint $table) {
            // PRIVATE_REPORT_TOKEN del ristorante: serve per chiamare le sue
            // /api/private/wa/* e per verificare il link di collegamento.
            $table->text('private_token')->nullable()->after('token');
        });

        Schema::table('messages', function (Blueprint $table) {
            // "conv:12" = avviso di un messaggio del cliente: se il ristoratore
            // risponde citandolo, il testo va al cliente della conversazione 12.
            $table->string('ref', 64)->nullable()->after('lang');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wa_accounts');

        Schema::table('sources', fn (Blueprint $table) => $table->dropColumn('private_token'));
        Schema::table('messages', fn (Blueprint $table) => $table->dropColumn('ref'));
    }
};
