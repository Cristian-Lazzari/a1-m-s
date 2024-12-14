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
        Schema::create('orders', function (Blueprint $table) {
            $table->id();

            $table->string('date_slot', 16); 
            $table->tinyInteger('status'); //annullata confermata o in elaborazione

            $table->string('name', 50);
            $table->string('surname', 50);
            $table->string('email', 100);
            $table->string('phone', 20);

            $table->string('checkout_session_id')->nullable();

            $table->string('address', 120)->nullable();
            $table->string('address_n', 4)->nullable();
            $table->string('comune', 30)->nullable();
            $table->string('whatsapp_message_id')->nullable();
            
            $table->bigInteger('tot_price');
            $table->string('message', 500)->nullable();
            
            $table->boolean('news_letter');
            $table->boolean('notificated')->default(false);

            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('orders');
    }
};
