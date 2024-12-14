<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('reservations', function (Blueprint $table) {
            $table->id();
            $table->string('date_slot', 16); 
            $table->tinyInteger('status'); //annullata confermata o in elaborazione

            $table->string('name', 50);
            $table->string('surname', 50);
            $table->string('email', 100);
            $table->string('phone', 20);
            
            $table->string('n_person'); // oggetto che ha chiave adult e child
            $table->string('sala')->nullable();
            $table->string('message', 500)->nullable();
            
            $table->string('whatsapp_message_id')->nullable();

            $table->boolean('take_away')->default(false);
            $table->boolean('news_letter');
            $table->boolean('notificated')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('reservations');
    }
};
