<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Schema dell'hub WhatsApp (v2).
 *
 * L'hub non entra mai nei database dei ristoranti: ognuno è un "tenant" con
 * cui si parla solo via API, in entrambe le direzioni, con due segreti diversi.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tenants', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            // Indirizzo del gestionale del ristorante (template-pro-be).
            $table->string('app_url');
            // Chiave con cui il ristorante chiama l'hub: si salva solo l'hash,
            // la chiave in chiaro si vede una volta sola alla creazione.
            $table->string('api_key_hash', 64)->unique();
            // PRIVATE_REPORT_TOKEN del ristorante, con cui l'hub chiama le sue
            // /api/private/wa/*: serve in chiaro, quindi è cifrato con l'APP_KEY.
            $table->text('private_token');
            $table->boolean('active')->default(true);
            $table->timestamps();
        });

        Schema::create('wa_accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
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

        // Ogni messaggio mandato dall'hub: serve a capire di chi è una
        // risposta (context.id) e a seguire le ricevute di consegna.
        Schema::create('wa_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->nullable()->constrained()->nullOnDelete();
            $table->string('channel', 20);          // platform | restaurant
            $table->string('phone_number_id', 64);  // numero che ha inviato
            $table->string('recipient', 20);
            $table->string('wa_message_id', 128)->nullable()->unique();
            $table->string('type', 20);             // text | interactive | template
            // Riferimento scelto dal ristorante, es. "res:123", "order:45", "conv:12".
            $table->string('ref', 64)->nullable()->index();
            $table->string('status', 20)->default('sent'); // sent | delivered | read | failed
            $table->text('error')->nullable();
            $table->timestamps();
        });

        // Ultimo messaggio ricevuto da ogni persona su ogni nostro numero: la
        // finestra di 24 ore per scrivere testo libero (gratis) parte da qui.
        Schema::create('wa_contacts', function (Blueprint $table) {
            $table->id();
            $table->string('phone_number_id', 64);
            $table->string('wa_id', 20);
            $table->timestamp('last_inbound_at');
            $table->timestamps();

            $table->unique(['phone_number_id', 'wa_id']);
        });

        // Eventi da consegnare ai ristoranti. Si salvano prima di provarci:
        // se il gestionale non risponde, il cron li riprova invece di perderli.
        Schema::create('wa_deliveries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->string('endpoint', 40);
            $table->json('payload');
            $table->string('status', 20)->default('pending')->index(); // pending | sent | failed
            $table->unsignedTinyInteger('attempts')->default(0);
            $table->timestamp('next_attempt_at')->nullable();
            $table->text('last_error')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wa_deliveries');
        Schema::dropIfExists('wa_contacts');
        Schema::dropIfExists('wa_messages');
        Schema::dropIfExists('wa_accounts');
        Schema::dropIfExists('tenants');
    }
};
