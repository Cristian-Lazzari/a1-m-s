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
        Schema::create('dates', function (Blueprint $table) {
            $table->id();
            $table->smallInteger('year');
            $table->tinyInteger('month');
            $table->tinyInteger('day');
            $table->tinyInteger('day_w');
            $table->string('time');

            $table->string('date_slot');
            //array
            $table->text('reserving'); //{'cucina_1' : 3, 'cucina_2' : 2, 'domicilio' :0 , 'tavoli':0}
            $table->text('availability');
            $table->text('visible');
            
            $table->tinyInteger('status'); //1-7 combinazione di servizi

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
        Schema::dropIfExists('dates');
    }
};
