<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Numeri di piattaforma dei partner (white label).
 *
 * Le notifiche ai ristoratori partono dal numero di piattaforma del .env
 * (WA_PLATFORM_*). Un ristorante venduto da un partner con il suo marchio può
 * invece avvisare dal numero WhatsApp Business del partner, collegato alla
 * nostra app Meta: qui il numero, sul tenant quale usare.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wa_senders', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('waba_id', 64)->nullable();
            $table->string('phone_number_id', 64)->unique();
            $table->string('display_phone', 32)->nullable();
            // Token del WABA del partner, cifrato con l'APP_KEY.
            $table->text('token');
            $table->timestamps();
        });

        Schema::table('tenants', function (Blueprint $table) {
            $table->foreignId('wa_sender_id')->nullable()->after('private_token')->constrained('wa_senders')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->dropConstrainedForeignId('wa_sender_id');
        });

        Schema::dropIfExists('wa_senders');
    }
};
